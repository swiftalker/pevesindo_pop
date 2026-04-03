<?php

namespace App\Engine\Odoo\Adapters\Sales;

use App\Engine\Odoo\Gateway;
use App\Exceptions\Odoo\OdooApiException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Event broadcasting helper for Odoo sync processes.
 *
 * Sync workers dispatch lifecycle events (start/finish), and Livewire components
 * listen via Laravel Echo to automatically refresh data without page reload.
 *
 * Channels follow the pattern: "odoo.sync.{domain}".
 */
class SaleOrders
{
    /** @var string */
    protected const MODEL = 'sale.order';

    /** @var array<int, string> */
    protected const READ_FIELDS = [
        'name',
        'state',
        'partner_id',
        'user_id',
        'company_id',
        'pricelist_id',
        'date_order',
        'validity_date',
        'commitment_date',
        'note',
        'amount_untaxed',
        'amount_tax',
        'amount_total',
        'order_line',
    ];

    public function __construct(
        protected Gateway $gateway,
    ) {}

    /**
     * Push a local record to Odoo as a draft/quotation.
     *
     * @return array<string, mixed>
     *
     * @throws OdooApiException
     */
    public function pushDraft(Model $record): array
    {
        Log::info("SaleOrders: Pushing draft for record #{$record->getKey()}");

        $values = $this->buildOrderValues($record);
        $popAppRef = $this->buildPopAppRef($record);

        $odooId = $this->gateway->create(self::MODEL, $values, $popAppRef);

        return $this->fetchAndUpdateLocal($record, $odooId);
    }

    /**
     * Push a confirmation action to Odoo.
     *
     * @return array<string, mixed>
     *
     * @throws OdooApiException
     */
    public function pushConfirm(Model $record): array
    {
        $odooId = $this->resolveOdooId($record);

        $this->gateway->call(self::MODEL, 'action_confirm', ['ids' => [$odooId]]);

        Log::info("SaleOrders: Confirmed record #{$record->getKey()} (Odoo #{$odooId})");

        return $this->fetchAndUpdateLocal($record, $odooId);
    }

    /**
     * Push a cancellation action to Odoo.
     *
     * @return array<string, mixed>
     *
     * @throws OdooApiException
     */
    public function pushCancel(Model $record): array
    {
        $odooId = $this->resolveOdooId($record);

        $this->gateway->call(self::MODEL, 'action_cancel', ['ids' => [$odooId]]);

        Log::info("SaleOrders: Cancelled record #{$record->getKey()} (Odoo #{$odooId})");

        $record->update(['synced_at' => now()]);

        return ['odoo_id' => $odooId, 'action' => 'cancelled'];
    }

    /**
     * Update an existing Odoo sale order with current local data.
     *
     * @return array<string, mixed>
     *
     * @throws OdooApiException
     */
    public function pushUpdate(Model $record): array
    {
        $odooId = $this->resolveOdooId($record);

        $values = $this->buildOrderValues($record);

        $this->gateway->write(self::MODEL, [$odooId], $values);

        Log::info("SaleOrders: Updated record #{$record->getKey()} (Odoo #{$odooId})");

        return $this->fetchAndUpdateLocal($record, $odooId);
    }

    /**
     * Pull a sale order from Odoo by its Odoo ID.
     *
     * @return array<string, mixed>
     */
    public function pull(int $odooId): array
    {
        $records = $this->gateway->read(self::MODEL, [$odooId], self::READ_FIELDS);

        if (empty($records)) {
            throw new OdooApiException("Sale order not found in Odoo: #{$odooId}");
        }

        return $records[0];
    }

    /**
     * Fetch the Odoo record after push and update local data.
     *
     * @return array<string, mixed>
     */
    protected function fetchAndUpdateLocal(Model $record, int $odooId): array
    {
        try {
            $odooRecords = $this->gateway->read(self::MODEL, [$odooId], self::READ_FIELDS);

            if (! empty($odooRecords)) {
                $odooData = $odooRecords[0];
                $odooName = $odooData['name'] ?? null;

                $record->update(array_filter([
                    'odoo_id' => $odooId,
                    'odoo_name' => $odooName,
                    'odoo_data' => $odooData,
                    'synced_at' => now(),
                ]));

                return ['odoo_id' => $odooId, 'odoo_name' => $odooName, 'action' => 'synced'];
            }
        } catch (\Throwable $e) {
            Log::warning("SaleOrders: Could not read-back Odoo #{$odooId}: {$e->getMessage()}");
        }

        $record->update([
            'odoo_id' => $odooId,
            'synced_at' => now(),
        ]);

        return ['odoo_id' => $odooId, 'action' => 'created'];
    }

    /**
     * Build Odoo-compatible values from a local record.
     *
     * @return array<string, mixed>
     */
    protected function buildOrderValues(Model $record): array
    {
        $values = [];

        if ($partnerId = $this->resolveCustomerOdooId($record)) {
            $values['partner_id'] = $partnerId;
        }

        if ($record->notes ?? null) {
            $values['note'] = $record->notes;
        }

        return $values;
    }

    /**
     * Generate a unique Pop-App reference for idempotency.
     */
    protected function buildPopAppRef(Model $record): string
    {
        return 'SO-'.$record->getKey().'-'.now()->timestamp;
    }

    /**
     * Resolve the Odoo ID from a local record.
     *
     * @throws OdooApiException
     */
    protected function resolveOdooId(Model $record): int
    {
        $odooId = $record->odoo_id ?? null;

        if (! $odooId) {
            throw new OdooApiException("Record #{$record->getKey()} has no Odoo ID — push draft first.");
        }

        return $odooId;
    }

    /**
     * Resolve the Odoo partner ID from a record's customer relation.
     */
    protected function resolveCustomerOdooId(Model $record): ?int
    {
        if (method_exists($record, 'customer') && $record->customer?->odoo_id) {
            return $record->customer->odoo_id;
        }

        return null;
    }
}
