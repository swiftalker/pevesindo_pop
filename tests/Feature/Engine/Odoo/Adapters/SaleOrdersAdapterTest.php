<?php

use App\Engine\Odoo\Adapters\Sales\SaleOrders;
use App\Exceptions\Odoo\OdooApiException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'odoo.base_url' => 'https://test.odoo.com',
        'odoo.api_key' => 'test-key',
        'odoo.database' => 'test-db',
    ]);
});

function makeStubRecord(array $attributes = []): Model
{
    $record = new class extends Model
    {
        protected $table = 'odoo_sync_tasks';

        protected $guarded = [];

        public $timestamps = false;
    };

    foreach ($attributes as $key => $value) {
        $record->{$key} = $value;
    }

    return $record;
}

it('pushDraft creates in Odoo and returns odoo_id', function () {
    Http::fake([
        '*/json/2/sale.order/create' => Http::response([88], 200),
        '*/json/2/sale.order/read' => Http::response([
            ['id' => 88, 'name' => 'S00088', 'state' => 'draft'],
        ], 200),
    ]);

    $record = makeStubRecord(['id' => 1]);

    $adapter = app(SaleOrders::class);
    $result = $adapter->pushDraft($record);

    expect($result)
        ->toBeArray()
        ->toHaveKey('odoo_id', 88);

    Http::assertSent(fn ($r) => str_contains($r->url(), 'sale.order/create'));
});

it('pushConfirm calls action_confirm on Odoo', function () {
    Http::fake([
        '*/json/2/sale.order/action_confirm' => Http::response('true', 200, ['Content-Type' => 'application/json']),
        '*/json/2/sale.order/read' => Http::response([
            ['id' => 42, 'name' => 'S00042', 'state' => 'sale'],
        ], 200),
    ]);

    $record = makeStubRecord(['id' => 1, 'odoo_id' => 42]);

    $adapter = app(SaleOrders::class);
    $result = $adapter->pushConfirm($record);

    expect($result)->toHaveKey('odoo_id', 42);

    Http::assertSent(fn ($r) => str_contains($r->url(), 'action_confirm'));
});

it('pushCancel calls action_cancel on Odoo', function () {
    Http::fake([
        '*/json/2/sale.order/action_cancel' => Http::response('true', 200, ['Content-Type' => 'application/json']),
    ]);

    $record = makeStubRecord(['id' => 1, 'odoo_id' => 42]);

    $adapter = app(SaleOrders::class);
    $result = $adapter->pushCancel($record);

    expect($result)
        ->toHaveKey('odoo_id', 42)
        ->toHaveKey('action', 'cancelled');

    Http::assertSent(fn ($r) => str_contains($r->url(), 'action_cancel'));
});

it('pushUpdate writes values to existing Odoo record', function () {
    Http::fake([
        '*/json/2/sale.order/write' => Http::response('true', 200, ['Content-Type' => 'application/json']),
        '*/json/2/sale.order/read' => Http::response([
            ['id' => 42, 'name' => 'S00042', 'state' => 'draft'],
        ], 200),
    ]);

    $record = makeStubRecord(['id' => 1, 'odoo_id' => 42]);

    $adapter = app(SaleOrders::class);
    $result = $adapter->pushUpdate($record);

    expect($result)->toHaveKey('odoo_id', 42);

    Http::assertSent(fn ($r) => str_contains($r->url(), 'sale.order/write'));
});

it('pull reads a record from Odoo', function () {
    Http::fake([
        '*/json/2/sale.order/read' => Http::response([
            ['id' => 42, 'name' => 'S00042', 'state' => 'sale', 'amount_total' => 1500.0],
        ], 200),
    ]);

    $adapter = app(SaleOrders::class);
    $data = $adapter->pull(42);

    expect($data)
        ->toHaveKey('id', 42)
        ->toHaveKey('name', 'S00042')
        ->toHaveKey('amount_total', 1500.0);
});

it('pull throws when record not found', function () {
    Http::fake([
        '*/json/2/sale.order/read' => Http::response([], 200),
    ]);

    $adapter = app(SaleOrders::class);
    $adapter->pull(9999);
})->throws(OdooApiException::class);

it('pushConfirm throws when no odoo_id', function () {
    $record = makeStubRecord(['id' => 1]);

    $adapter = app(SaleOrders::class);
    $adapter->pushConfirm($record);
})->throws(OdooApiException::class, 'has no Odoo ID');

it('pushDraft throws when Odoo API fails', function () {
    Http::fake([
        '*/json/2/sale.order/create' => Http::response('Error', 500),
    ]);

    $record = makeStubRecord(['id' => 1]);

    $adapter = app(SaleOrders::class);
    $adapter->pushDraft($record);
})->throws(OdooApiException::class);
