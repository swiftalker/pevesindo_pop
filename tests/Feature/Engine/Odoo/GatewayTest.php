<?php

use App\Engine\Odoo\Gateway;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'odoo.base_url' => 'https://test.odoo.com',
        'odoo.api_key' => 'test-key',
        'odoo.database' => 'test-db',
    ]);
});

it('delegates searchRead to OdooClient', function () {
    Http::fake([
        '*/json/2/res.partner/search_read' => Http::response([
            ['id' => 1, 'name' => 'Test'],
        ], 200),
    ]);

    $gateway = app(Gateway::class);
    $result = $gateway->searchRead('res.partner', [], ['name']);

    expect($result)->toHaveCount(1);
    Http::assertSentCount(1);
});

it('delegates create to OdooClient', function () {
    Http::fake([
        '*/json/2/sale.order/create' => Http::response([55], 200),
    ]);

    $gateway = app(Gateway::class);
    $odooId = $gateway->create('sale.order', ['partner_id' => 1]);

    expect($odooId)->toBe(55);
});

it('delegates write to OdooClient', function () {
    Http::fake([
        '*/json/2/sale.order/write' => Http::response('true', 200, ['Content-Type' => 'application/json']),
    ]);

    $gateway = app(Gateway::class);
    $result = $gateway->write('sale.order', [1], ['note' => 'Updated']);

    expect($result)->toBeTrue();
});

it('delegates read to OdooClient', function () {
    Http::fake([
        '*/json/2/res.partner/read' => Http::response([
            ['id' => 1, 'name' => 'A'],
        ], 200),
    ]);

    $gateway = app(Gateway::class);
    $records = $gateway->read('res.partner', [1], ['name']);

    expect($records)->toHaveCount(1);
});

it('resolves as singleton', function () {
    $a = app(Gateway::class);
    $b = app(Gateway::class);

    expect($a)->toBe($b);
});
