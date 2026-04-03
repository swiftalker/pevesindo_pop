<?php

namespace App\Listeners\Odoo;

use App\Enums\Odoo\SyncTaskStatus;
use App\Enums\Odoo\SyncTaskType;
use App\Events\Odoo\OdooSyncRequested;
use App\Jobs\Odoo\Sales\SaleOrderPush;
use App\Models\Odoo\Sync\OdooSyncTask;
use Illuminate\Support\Facades\Log;

/**
 * Listens to OdooSyncRequested and dispatches the appropriate domain Job.
 *
 * Creates an OdooSyncTask record for tracking and dispatches the job to the 'odoo' queue.
 */
class DispatchOdooSyncJob
{
    /**
     * Handle the event.
     */
    public function handle(OdooSyncRequested $event): void
    {
        $task = OdooSyncTask::create([
            'type' => SyncTaskType::Push,
            'model' => $event->model,
            'pop_app_ref' => $event->popAppRef,
            'payload' => $this->extractPayload($event),
            'status' => SyncTaskStatus::Pending,
        ]);

        Log::info("Odoo Sync: Created task #{$task->id} for {$event->model}/{$event->action}", [
            'pop_app_ref' => $event->popAppRef,
        ]);

        $job = $this->resolveJob($event, $task);

        if ($job) {
            dispatch($job);
        } else {
            Log::warning("Odoo Sync: No job registered for {$event->model}/{$event->action}");
            $task->markFailed("No job handler for {$event->model}/{$event->action}");
        }
    }

    /**
     * Resolve the domain-specific job class based on the model and action.
     */
    protected function resolveJob(OdooSyncRequested $event, OdooSyncTask $task): ?object
    {
        return match ($event->model) {
            'sale.order' => new SaleOrderPush($task, $event->action, $event->record),
            default => null,
        };
    }

    /**
     * Extract payload from the event record for storage.
     *
     * @return array<string, mixed>
     */
    protected function extractPayload(OdooSyncRequested $event): array
    {
        if (is_array($event->record)) {
            return $event->record;
        }

        if (is_object($event->record) && method_exists($event->record, 'toArray')) {
            return $event->record->toArray();
        }

        return ['record_id' => $event->record->id ?? null];
    }
}
