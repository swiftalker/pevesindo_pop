<?php

namespace App\Engine\Odoo\Services;

use App\Engine\Odoo\Client\OdooGateway;
use App\Engine\Odoo\Contracts\Pushable;
use App\Engine\Odoo\Exceptions\OdooApiException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Generic push service — orchestrates pushing any local model to Odoo.
 *
 * Works with any Pushable integration. Domain classes implement
 * Pushable (buildCreateValues, buildUpdateValues, resolveOdooId).
 *
 * Usage:
 *   $pusher = app(OrderIntegration::class);
 *   app(PushService::class)->create($order, $pusher);
 *   app(PushService::class)->update($order, $pusher);
 *   app(PushService::class)->action($odooIds, $pusher, 'action_confirm');
 */
class PushService
{
    public function __construct(
        protected OdooGateway $gateway,
    ) {}

    /**
     * Create a new record in Odoo.
     *
     * @param  Model  $record  Local model
     * @param  Pushable  $pushable  Domain integration
     * @param  string|null  $popAppRef  Idempotency reference
     * @return array<string, mixed> Result with odoo_id
     *
     * @throws OdooApiException
     */
    public function create(Model $record, Pushable $pushable, ?string $popAppRef = null): array
    {
        $model = $pushable->odooModel();
        $values = $pushable->buildCreateValues($record);

        Log::info("Odoo Push [create] {$model}", [
            'local_id' => $record->getKey(),
        ]);

        $odooId = $this->gateway->create($model, $values, $popAppRef);

        return $this->readBack($model, $odooId, $record, $pushable);
    }

    /**
     * Update an existing Odoo record.
     *
     * @param  Model  $record  Local model
     * @param  Pushable  $pushable  Domain integration
     * @return array<string, mixed> Result with odoo_id
     *
     * @throws OdooApiException
     */
    public function update(Model $record, Pushable $pushable): array
    {
        $model = $pushable->odooModel();
        $odooId = $pushable->resolveOdooId($record);
        $values = $pushable->buildUpdateValues($record);

        Log::info("Odoo Push [update] {$model} #{$odooId}", [
            'local_id' => $record->getKey(),
        ]);

        $this->gateway->write($model, [$odooId], $values);

        $record->update(['synced_at' => now()]);

        return ['odoo_id' => $odooId, 'action' => 'updated'];
    }

    /**
     * Call an Odoo action method on a record.
     *
     * @param  int  $odooId  Odoo record ID
     * @param  Pushable  $pushable  Domain integration
     * @param  string  $method  Odoo method name (e.g. 'action_confirm')
     * @param  array  $params  Extra params for the call
     * @return array<string, mixed>
     *
     * @throws OdooApiException
     */
    public function action(int $odooId, Pushable $pushable, string $method, array $params = []): array
    {
        $model = $pushable->odooModel();

        Log::info("Odoo Push [action] {$model}/{$method} #{$odooId}");

        $this->gateway->call($model, $method, array_merge([
            'ids' => [$odooId],
        ], $params));

        return ['odoo_id' => $odooId, 'action' => $method];
    }

    /**
     * Pull a single record back from Odoo after push for verification.
     *
     * @param  string  $model  Odoo model
     * @param  int  $odooId  Odoo record ID
     * @param  Model  $record  Local model to update
     * @param  array<int, string>  $fields  Fields to read
     * @return array<string, mixed>
     */
    public function readBack(string $model, int $odooId, Model $record, array $fields = ['id', 'name']): array
    {
        try {
            $result = $this->gateway->read($model, [$odooId], $fields);

            if (! empty($result)) {
                $data = $result[0];
                $record->update(array_filter([
                    'odoo_id' => $odooId,
                    'odoo_name' => $data['name'] ?? null,
                    'odoo_data' => $data,
                    'synced_at' => now(),
                ]));

                return ['odoo_id' => $odooId, 'odoo_name' => $data['name'] ?? null, 'action' => 'synced'];
            }
        } catch (\Throwable $e) {
            Log::warning("Odoo Push [read-back] failed #{$odooId}: {$e->getMessage()}");
        }

        $record->update(['odoo_id' => $odooId, 'synced_at' => now()]);

        return ['odoo_id' => $odooId, 'action' => 'created'];
    }
}
