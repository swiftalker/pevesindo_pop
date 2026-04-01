<?php

use App\Engine\Odoo\SyncEvents;
use Illuminate\Broadcasting\Channel;

it('has all required topic constants', function () {
    expect(SyncEvents::TOPIC_SALE_ORDERS)->toBe('odoo.sync.sale-orders')
        ->and(SyncEvents::TOPIC_CUSTOMERS)->toBe('odoo.sync.customers')
        ->and(SyncEvents::TOPIC_PRODUCTS)->toBe('odoo.sync.products')
        ->and(SyncEvents::TOPIC_COMPANIES)->toBe('odoo.sync.companies')
        ->and(SyncEvents::TOPIC_EMPLOYEES)->toBe('odoo.sync.employees')
        ->and(SyncEvents::TOPIC_PRICELISTS)->toBe('odoo.sync.pricelists')
        ->and(SyncEvents::TOPIC_JOURNALS)->toBe('odoo.sync.journals')
        ->and(SyncEvents::TOPIC_INVENTORY)->toBe('odoo.sync.inventory');
});

it('returns human-readable labels for known topics', function (string $topic, string $expected) {
    expect(SyncEvents::label($topic))->toBe($expected);
})->with([
    'sale orders' => [SyncEvents::TOPIC_SALE_ORDERS, 'Sale Orders'],
    'customers' => [SyncEvents::TOPIC_CUSTOMERS, 'Customers'],
    'products' => [SyncEvents::TOPIC_PRODUCTS, 'Products'],
    'companies' => [SyncEvents::TOPIC_COMPANIES, 'Companies'],
    'employees' => [SyncEvents::TOPIC_EMPLOYEES, 'Employees'],
    'inventory' => [SyncEvents::TOPIC_INVENTORY, 'Inventory'],
]);

it('returns Data for unknown topics', function () {
    expect(SyncEvents::label('odoo.sync.unknown'))->toBe('Data');
});

it('returns a broadcast channel for a topic', function () {
    $channel = SyncEvents::channel(SyncEvents::TOPIC_SALE_ORDERS);

    expect($channel)->toBeInstanceOf(Channel::class);
});

it('provides a complete topic labels map', function () {
    $labels = SyncEvents::topicLabels();

    expect($labels)->toBeArray()
        ->toHaveKeys([
            SyncEvents::TOPIC_SALE_ORDERS,
            SyncEvents::TOPIC_CUSTOMERS,
            SyncEvents::TOPIC_PRODUCTS,
            SyncEvents::TOPIC_COMPANIES,
            SyncEvents::TOPIC_EMPLOYEES,
        ]);
});
