<?php

namespace App\Engine\Odoo\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Generic contract for any domain that can be pushed to Odoo.
 *
 * Domain integrations implement this to define how records are pushed.
 * Example: OrderIntegration, ProductIntegration, etc.
 *
 * @template TRecord of \Illuminate\Database\Eloquent\Model
 */
interface Pushable
{
    /**
     * The Odoo model name (e.g. 'sale.order', 'product.product').
     */
    public function odooModel(): string;

    /**
     * Build values for creating a new record via Odoo's external API.
     *
     * @param  Model  $record
     * @return array<string, mixed>
     */
    public function buildCreateValues($record): array;

    /**
     * Build values for updating an existing Odoo record.
     *
     * @param  Model  $record
     * @return array<string, mixed>
     */
    public function buildUpdateValues($record): array;

    /**
     * Resolve the Odoo ID from the local record.
     *
     * @throws \InvalidArgumentException
     */
    public function resolveOdooId($record): int;
}
