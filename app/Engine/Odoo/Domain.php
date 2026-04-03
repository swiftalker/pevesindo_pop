<?php

namespace App\Engine\Odoo;

use App\Engine\Odoo\Jobs\Sales\PushOrderJob;
use App\Engine\Odoo\Listeners\DispatchOdooSyncJob;

/**
 * Bootstrap Odoo domain jobs and integrations.
 *
 * Registers model + action -> job mappings in the registry.
 * Called once from AppServiceProvider.
 */
class Domain
{
    /**
     * Register all domain job listeners.
     */
    public static function bootEventListeners(): void
    {
        // Sale Order push actions
        DispatchOdooSyncJob::register('sale.order', 'push_draft', fn ($event, $task) => new PushOrderJob($task, $event->action, $event->record));

        DispatchOdooSyncJob::register('sale.order', 'push_confirm', fn ($event, $task) => new PushOrderJob($task, $event->action, $event->record));

        DispatchOdooSyncJob::register('sale.order', 'push_cancel', fn ($event, $task) => new PushOrderJob($task, $event->action, $event->record));

        DispatchOdooSyncJob::register('sale.order', 'push_update', fn ($event, $task) => new PushOrderJob($task, $event->action, $event->record));

        // Add more domain registrations here as you build them:
        // DispatchOdooSyncJob::register('res.partner', 'push_draft', fn ($event, $task) =>
        //     new PushPartnerJob($task, $event->action, $event->record));
    }
}
