<?php

namespace App\Engine\Odoo;

use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Facades\Log;

/**
 * Event broadcasting helper for Odoo sync processes.
 *
 * Sync workers dispatch lifecycle events (start/finish), and Livewire components
 * listen via Laravel Echo to automatically refresh data without page reload.
 *
 * Channels follow the pattern: "odoo.sync.{domain}".
 */
class SyncEvents
{
    /** @var string */
    public const TOPIC_SALE_ORDERS = 'odoo.sync.sale-orders';

    /** @var string */
    public const TOPIC_CUSTOMERS = 'odoo.sync.customers';

    /** @var string */
    public const TOPIC_PRODUCTS = 'odoo.sync.products';

    /** @var string */
    public const TOPIC_PRODUCT_VARIANTS = 'odoo.sync.product-variants';

    /** @var string */
    public const TOPIC_COMPANIES = 'odoo.sync.companies';

    /** @var string */
    public const TOPIC_EMPLOYEES = 'odoo.sync.employees';

    /** @var string */
    public const TOPIC_PRICELISTS = 'odoo.sync.pricelists';

    /** @var string */
    public const TOPIC_JOURNALS = 'odoo.sync.journals';

    /** @var string */
    public const TOPIC_INVENTORY = 'odoo.sync.inventory';

    /**
     * Get the Reverb broadcast channel for a sync topic.
     */
    public static function channel(string $topic): Channel
    {
        return new Channel($topic);
    }

    /**
     * Human-readable label for a topic.
     *
     * @return array<string, string>
     */
    public static function topicLabels(): array
    {
        return [
            self::TOPIC_SALE_ORDERS => 'Sale Orders',
            self::TOPIC_CUSTOMERS => 'Customers',
            self::TOPIC_PRODUCTS => 'Products',
            self::TOPIC_PRODUCT_VARIANTS => 'Product Variants',
            self::TOPIC_COMPANIES => 'Companies',
            self::TOPIC_EMPLOYEES => 'Employees',
            self::TOPIC_PRICELISTS => 'Pricelists',
            self::TOPIC_JOURNALS => 'Journals',
            self::TOPIC_INVENTORY => 'Inventory',
        ];
    }

    /**
     * Get the human-readable label for a topic.
     */
    public static function label(string $topic): string
    {
        return self::topicLabels()[$topic] ?? 'Data';
    }

    /**
     * Log a sync started event.
     */
    public static function logStarted(string $topic): void
    {
        Log::info("Odoo Sync: Started [{$topic}]");
    }

    /**
     * Log a sync completed event.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function logCompleted(string $topic, array $payload = []): void
    {
        $count = $payload['synced'] ?? $payload['pushed'] ?? 0;

        Log::info("Odoo Sync: Completed [{$topic}]", [
            'count' => $count,
            'payload' => $payload,
        ]);
    }
}
