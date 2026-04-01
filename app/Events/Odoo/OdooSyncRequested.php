<?php

namespace App\Events\Odoo;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when any service needs to sync data with Odoo.
 *
 * Usage:
 *   OdooSyncRequested::dispatch('sale.order', 'push_draft', $intent, 'SO-001');
 */
class OdooSyncRequested
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  string  $model  Odoo model name (e.g. 'sale.order')
     * @param  string  $action  Sync action (e.g. 'push_draft', 'push_confirm', 'pull')
     * @param  mixed  $record  The local record to sync
     * @param  string|null  $popAppRef  Unique Pop-App reference for idempotency
     */
    public function __construct(
        public string $model,
        public string $action,
        public mixed $record,
        public ?string $popAppRef = null,
    ) {}
}
