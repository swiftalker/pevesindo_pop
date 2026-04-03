<?php

use App\Engine\Odoo\Adapters\Sales\SaleOrders;
use App\Enums\Odoo\SyncTaskStatus;
use App\Enums\Odoo\SyncTaskType;
use App\Events\Odoo\OdooSyncCompleted;
use App\Events\Odoo\OdooSyncFailed;
use App\Jobs\Odoo\Sales\SaleOrderPush;
use App\Models\Odoo\Sync\OdooSyncTask;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

it('processes a push_draft and marks task completed', function () {
    Event::fake([OdooSyncCompleted::class, OdooSyncFailed::class]);

    Http::fake([
        '*/json/2/sale.order/create' => Http::response([42], 200),
        '*/json/2/sale.order/read' => Http::response([
            ['id' => 42, 'name' => 'S00042', 'state' => 'draft'],
        ], 200),
    ]);

    $task = OdooSyncTask::create([
        'type' => SyncTaskType::Push,
        'model' => 'sale.order',
        'pop_app_ref' => 'SO-JOB-001',
        'payload' => ['partner_id' => 1],
        'status' => SyncTaskStatus::Pending,
    ]);

    $record = new class extends Model
    {
        protected $table = 'odoo_sync_tasks';

        protected $guarded = [];

        public $timestamps = false;
    };
    $record->id = 1;

    $job = new SaleOrderPush($task, 'push_draft', $record);
    $job->handle(app(SaleOrders::class));

    expect($task->fresh())
        ->status->toBe(SyncTaskStatus::Completed)
        ->odoo_id->toBe(42);

    Event::assertDispatched(OdooSyncCompleted::class);
});

it('fires OdooSyncFailed when Odoo returns an error', function () {
    Event::fake([OdooSyncCompleted::class, OdooSyncFailed::class]);

    Http::fake([
        '*/json/2/sale.order/create' => Http::response('Server Error', 500),
    ]);

    $task = OdooSyncTask::create([
        'type' => SyncTaskType::Push,
        'model' => 'sale.order',
        'pop_app_ref' => 'SO-FAIL-001',
        'payload' => ['name' => 'Will Fail'],
        'status' => SyncTaskStatus::Pending,
        'max_attempts' => 1,
        'attempts' => 0,
    ]);

    $record = new class extends Model
    {
        protected $table = 'odoo_sync_tasks';

        protected $guarded = [];

        public $timestamps = false;
    };
    $record->id = 1;

    $job = new SaleOrderPush($task, 'push_draft', $record);
    $job->handle(app(SaleOrders::class));

    expect($task->fresh())
        ->status->toBe(SyncTaskStatus::Failed)
        ->error_log->not->toBeNull();

    Event::assertDispatched(OdooSyncFailed::class);
});

it('dispatches the job on the odoo queue', function () {
    $task = OdooSyncTask::create([
        'type' => SyncTaskType::Push,
        'model' => 'sale.order',
        'pop_app_ref' => 'SO-QUEUE-001',
        'status' => SyncTaskStatus::Pending,
    ]);

    $record = new stdClass;
    $job = new SaleOrderPush($task, 'push_draft', $record);

    expect($job->queue)->toBe(config('odoo.queue_name', 'odoo'));
});
