<?php

use App\Enums\Odoo\SyncTaskStatus;
use App\Events\Odoo\OdooSyncRequested;
use App\Jobs\Odoo\Sales\SaleOrderPush;
use App\Listeners\Odoo\DispatchOdooSyncJob;
use App\Models\Odoo\Sync\OdooSyncTask;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

it('dispatches OdooSyncRequested event', function () {
    Event::fake([OdooSyncRequested::class]);

    OdooSyncRequested::dispatch('sale.order', 'push_draft', new stdClass, 'SO-EVT-001');

    Event::assertDispatched(OdooSyncRequested::class, function ($event) {
        return $event->model === 'sale.order'
            && $event->action === 'push_draft'
            && $event->popAppRef === 'SO-EVT-001';
    });
});

it('listener creates OdooSyncTask and dispatches job', function () {
    Queue::fake();

    $event = new OdooSyncRequested(
        model: 'sale.order',
        action: 'push_draft',
        record: new stdClass,
        popAppRef: 'SO-LST-001',
    );

    $listener = new DispatchOdooSyncJob;
    $listener->handle($event);

    $task = OdooSyncTask::where('pop_app_ref', 'SO-LST-001')->first();

    expect($task)
        ->not->toBeNull()
        ->model->toBe('sale.order')
        ->status->toBe(SyncTaskStatus::Pending);

    Queue::assertPushed(SaleOrderPush::class);
});
