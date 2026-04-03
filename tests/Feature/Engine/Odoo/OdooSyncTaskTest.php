<?php

use App\Enums\Odoo\SyncTaskStatus;
use App\Enums\Odoo\SyncTaskType;
use App\Models\Odoo\Sync\OdooSyncTask;

it('can create a sync task with pending status', function () {
    $task = OdooSyncTask::create([
        'type' => SyncTaskType::Push,
        'model' => 'sale.order',
        'pop_app_ref' => 'SO-TEST-001',
        'payload' => ['name' => 'Test Order'],
        'status' => SyncTaskStatus::Pending,
    ]);

    expect($task)->toBeInstanceOf(OdooSyncTask::class)
        ->and($task->type)->toBe(SyncTaskType::Push)
        ->and($task->status)->toBe(SyncTaskStatus::Pending)
        ->and($task->model)->toBe('sale.order')
        ->and($task->pop_app_ref)->toBe('SO-TEST-001')
        ->and($task->payload)->toBe(['name' => 'Test Order']);
});

it('can transition from pending to syncing', function () {
    $task = OdooSyncTask::create([
        'type' => SyncTaskType::Push,
        'model' => 'sale.order',
        'pop_app_ref' => 'SO-TEST-002',
        'status' => SyncTaskStatus::Pending,
    ]);

    $task->markSyncing();

    expect($task->fresh())
        ->status->toBe(SyncTaskStatus::Syncing)
        ->attempts->toBe(1)
        ->last_attempted_at->not->toBeNull();
});

it('can mark a task as completed with odoo id', function () {
    $task = OdooSyncTask::create([
        'type' => SyncTaskType::Push,
        'model' => 'sale.order',
        'pop_app_ref' => 'SO-TEST-003',
        'status' => SyncTaskStatus::Syncing,
    ]);

    $task->markCompleted(42, ['action' => 'created']);

    $fresh = $task->fresh();
    expect($fresh)
        ->status->toBe(SyncTaskStatus::Completed)
        ->odoo_id->toBe(42)
        ->completed_at->not->toBeNull()
        ->error_log->toBeNull();
});

it('can mark a task as failed with error log', function () {
    $task = OdooSyncTask::create([
        'type' => SyncTaskType::Push,
        'model' => 'sale.order',
        'pop_app_ref' => 'SO-TEST-004',
        'status' => SyncTaskStatus::Syncing,
    ]);

    $task->markFailed('Connection timeout');

    expect($task->fresh())
        ->status->toBe(SyncTaskStatus::Failed)
        ->error_log->toBe('Connection timeout');
});

it('can restart a failed task', function () {
    $task = OdooSyncTask::create([
        'type' => SyncTaskType::Push,
        'model' => 'sale.order',
        'pop_app_ref' => 'SO-TEST-005',
        'status' => SyncTaskStatus::Failed,
        'error_log' => 'Previous error',
    ]);

    $task->restart();

    expect($task->fresh())
        ->status->toBe(SyncTaskStatus::Pending)
        ->error_log->toBeNull();
});

it('can duplicate a task as a new pending task', function () {
    $task = OdooSyncTask::create([
        'type' => SyncTaskType::Push,
        'model' => 'sale.order',
        'pop_app_ref' => 'SO-TEST-006',
        'payload' => ['partner_id' => 1],
        'status' => SyncTaskStatus::Failed,
    ]);

    $duplicate = $task->duplicate();

    expect($duplicate)
        ->id->not->toBe($task->id)
        ->status->toBe(SyncTaskStatus::Pending)
        ->model->toBe('sale.order')
        ->payload->toBe(['partner_id' => 1])
        ->pop_app_ref->toStartWith('SO-TEST-006-dup-');
});

it('correctly reports canRetry based on attempts vs max_attempts', function () {
    $task = OdooSyncTask::create([
        'type' => SyncTaskType::Pull,
        'model' => 'res.partner',
        'status' => SyncTaskStatus::Failed,
        'attempts' => 3,
        'max_attempts' => 5,
    ]);

    expect($task->canRetry())->toBeTrue();

    $task->update(['attempts' => 5]);

    expect($task->canRetry())->toBeFalse();
});

it('scopes readyToProcess to pending tasks ordered by created_at', function () {
    $old = OdooSyncTask::create([
        'type' => SyncTaskType::Pull,
        'model' => 'res.partner',
        'status' => SyncTaskStatus::Pending,
        'created_at' => now()->subMinutes(10),
    ]);

    $new = OdooSyncTask::create([
        'type' => SyncTaskType::Pull,
        'model' => 'res.company',
        'status' => SyncTaskStatus::Pending,
        'created_at' => now(),
    ]);

    OdooSyncTask::create([
        'type' => SyncTaskType::Push,
        'model' => 'sale.order',
        'status' => SyncTaskStatus::Completed,
    ]);

    $ready = OdooSyncTask::readyToProcess()->get();

    expect($ready)->toHaveCount(2)
        ->and($ready->first()->id)->toBe($old->id);
});

it('soft deletes a task', function () {
    $task = OdooSyncTask::create([
        'type' => SyncTaskType::Push,
        'model' => 'sale.order',
        'status' => SyncTaskStatus::Failed,
    ]);

    $task->delete();

    expect(OdooSyncTask::find($task->id))->toBeNull()
        ->and(OdooSyncTask::withTrashed()->find($task->id))->not->toBeNull();
});
