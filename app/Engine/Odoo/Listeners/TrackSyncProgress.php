<?php

namespace App\Engine\Odoo\Listeners;

use App\Engine\Odoo\Events\OdooSyncCompleted;
use App\Engine\Odoo\Events\OdooSyncFailed;
use App\Engine\Odoo\Events\OdooSyncStarted;
use App\Engine\Odoo\Trackers\SyncTracker;
use Illuminate\Support\Facades\Log;

/**
 * Updates the SyncTracker when sync events are dispatched.
 *
 * Register in EventServiceProvider:
 *   OdooSyncStarted::class => [TrackSyncProgress::class],
 *   OdooSyncCompleted::class => [TrackSyncProgress::class],
 *   OdooSyncFailed::class => [TrackSyncProgress::class],
 */
class TrackSyncProgress
{
    public function handle(OdooSyncStarted|OdooSyncCompleted|OdooSyncFailed $event): void
    {
        match (true) {
            $event instanceof OdooSyncStarted => SyncTracker::markStarted($event->topic),
            default => SyncTracker::markCompleted($event->topic),
        };

        $eventClass = $event::class;

        Log::info("Odoo Sync Tracker: {$eventClass} for topic '{$event->topic}'", [
            'task_id' => $event->task->id,
        ]);
    }
}
