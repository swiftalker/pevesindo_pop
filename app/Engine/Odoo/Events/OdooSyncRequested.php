<?php

namespace App\Engine\Odoo\Events;

use App\Engine\Odoo\Enums\Direction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when any service needs to sync data with Odoo.
 *
 * Usage:
 *   // Push (default)
 *   OdooSyncRequested::dispatch('sale.order', 'push_draft', $intent);
 *
 *   // Pull
 *   OdooSyncRequested::dispatch('res.partner', 'pull', $domain, direction: Direction::Pull);
 *
 *   // Report
 *   OdooSyncRequested::dispatch('sale.order', 'report', $order, direction: Direction::Report);
 */
class OdooSyncRequested
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  string  $model  Odoo model name (e.g. 'sale.order')
     * @param  string  $action  Sync action (e.g. 'push_draft', 'pull', 'report')
     * @param  mixed  $record  The local record or domain data to sync
     * @param  Direction  $direction  Push, Pull, or Report — defaults to Push
     * @param  string|null  $popAppRef  Unique Pop-App reference for idempotency
     */
    public function __construct(
        public string $model,
        public string $action,
        public mixed $record,
        public Direction $direction = Direction::Push,
        public ?string $popAppRef = null,
    ) {}
}
