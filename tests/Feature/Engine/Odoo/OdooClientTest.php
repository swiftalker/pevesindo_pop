<?php

use App\Engine\Odoo\Exceptions\OdooApiException;
use App\Engine\Odoo\Models\OdooSyncTask;
use App\Engine\Odoo\OdooClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'odoo.base_url' => 'https://test.odoo.com',
        'odoo.api_key' => 'test-key',
        'odoo.database' => 'test-db',
    ]);
});

it('makes a successful JSON-2 API call', function () {
    Http::fake([
        '*/json/2/res.partner/search_read' => Http::response([
            ['id' => 1, 'name' => 'Partner A'],
        ], 200),
    ]);

    $client = app(OdooClient::class);
    $result = $client->searchRead('res.partner', [['customer_rank', '>', 0]], ['name']);

    expect($result)->toBeArray()
        ->toHaveCount(1)
        ->and($result[0]['name'])->toBe('Partner A');

    Http::assertSentCount(1);
});

it('throws OdooApiException on 429 rate limit', function () {
    Http::fake([
        '*/json/2/sale.order/create' => Http::response('Rate Limited', 429),
    ]);

    $client = app(OdooClient::class);
    $client->call('sale.order', 'create', ['vals_list' => [['name' => 'test']]]);
})->throws(OdooApiException::class, 'Rate limited');

it('throws OdooApiException on 500 server error', function () {
    Http::fake([
        '*/json/2/sale.order/write' => Http::response('Internal Server Error', 500),
    ]);

    $client = app(OdooClient::class);
    $client->call('sale.order', 'write', ['ids' => [1], 'vals' => ['name' => 'x']]);
})->throws(OdooApiException::class, 'Odoo API error');

it('creates a record and returns the odoo id', function () {
    Http::fake([
        '*/json/2/sale.order/create' => Http::response([99], 200),
    ]);

    $client = app(OdooClient::class);
    $odooId = $client->create('sale.order', ['partner_id' => 1]);

    expect($odooId)->toBe(99);
});

it('returns existing odoo id on idempotent create', function () {
    OdooSyncTask::create([
        'model' => 'sale.order',
        'type' => 'push',
        'pop_app_ref' => 'SO-IDEM-001',
        'odoo_id' => 77,
        'status' => 'completed',
    ]);

    Http::fake();

    $client = app(OdooClient::class);
    $odooId = $client->create('sale.order', ['partner_id' => 1], 'SO-IDEM-001');

    expect($odooId)->toBe(77);
    Http::assertNothingSent();
});

it('writes to existing records and returns true', function () {
    Http::fake([
        '*/json/2/sale.order/write' => Http::response('true', 200, ['Content-Type' => 'application/json']),
    ]);

    $client = app(OdooClient::class);
    $result = $client->write('sale.order', [42], ['note' => 'Updated']);

    expect($result)->toBeTrue();
});

it('reads records by ids', function () {
    Http::fake([
        '*/json/2/res.partner/read' => Http::response([
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
        ], 200),
    ]);

    $client = app(OdooClient::class);
    $records = $client->read('res.partner', [1, 2], ['name']);

    expect($records)->toHaveCount(2);
});

it('counts records with searchCount', function () {
    Http::fake([
        '*/json/2/res.partner/search_count' => Http::response(42, 200),
    ]);

    $client = app(OdooClient::class);
    $count = $client->searchCount('res.partner', [['active', '=', true]]);

    expect($count)->toBe(42);
});

it('sends correct auth headers', function () {
    Http::fake([
        '*/json/2/res.partner/search' => Http::response([], 200),
    ]);

    $client = app(OdooClient::class);
    $client->search('res.partner', []);

    Http::assertSent(function ($request) {
        return str_contains($request->header('Authorization')[0], 'Bearer test-key')
            && $request->header('X-Odoo-Database')[0] === 'test-db';
    });
});

it('injects language context into params', function () {
    Http::fake([
        '*/json/2/res.partner/search' => Http::response([], 200),
    ]);

    $client = app(OdooClient::class);
    $client->search('res.partner', []);

    Http::assertSent(function ($request) {
        $body = $request->body();

        return str_contains($body, 'context');
    });
});
