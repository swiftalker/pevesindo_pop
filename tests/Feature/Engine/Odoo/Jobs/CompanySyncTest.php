<?php

use App\Engine\Odoo\Gateway;
use App\Jobs\Odoo\Core\CompanySync;
use App\Models\Odoo\Core\Company;
use App\Models\Odoo\Core\Partner;

it('creates and resolves companies with parents and partners from odoo data', function () {
    // Mock the Gateway
    $gateway = Mockery::mock(Gateway::class);
    $gateway->shouldReceive('searchRead')
        ->once()
        ->with('res.company', [], ['name', 'parent_id', 'currency_id', 'partner_id', 'active'])
        ->andReturn([
            [
                'id' => 10,
                'name' => 'HQ Company',
                'parent_id' => false,
                'currency_id' => [1, 'USD'],
                'partner_id' => [100, 'HQ Partner'],
                'active' => true,
            ],
            [
                'id' => 11,
                'name' => 'Branch Company',
                'parent_id' => [10, 'HQ Company'],
                'currency_id' => false,
                'partner_id' => [101, 'Branch Partner'],
                'active' => true,
            ],
        ]);

    // Dispatch job synchronously
    $job = new CompanySync;
    $job->handle($gateway);

    // Verify companies were created
    expect(Company::count())->toBe(2);

    $hq = Company::where('odoo_id', 10)->first();
    $branch = Company::where('odoo_id', 11)->first();

    expect($hq)->not->toBeNull()
        ->and($hq->name)->toBe('HQ Company')
        ->and($hq->currency)->toBe('USD')
        ->and($hq->parent_id)->toBeNull();

    expect($branch)->not->toBeNull()
        ->and($branch->name)->toBe('Branch Company')
        ->and($branch->currency)->toBe('IDR') // Default when false
        ->and($branch->parent_id)->toBe($hq->id);

    // Verify partners were created and linked
    expect(Partner::count())->toBe(2);

    $hqPartner = Partner::where('odoo_id', 100)->first();
    $branchPartner = Partner::where('odoo_id', 101)->first();

    expect($hq->partner_id)->toBe($hqPartner->id)
        ->and($branch->partner_id)->toBe($branchPartner->id)
        ->and($hqPartner->company_id)->toBe($hq->id)
        ->and($branchPartner->company_id)->toBe($branch->id);
});

it('links to existing partner if already present', function () {
    $existingPartner = Partner::factory()->create([
        'odoo_id' => 200,
        'name' => 'Pre-existing Partner',
    ]);

    $gateway = Mockery::mock(Gateway::class);
    $gateway->shouldReceive('searchRead')
        ->once()
        ->andReturn([
            [
                'id' => 20,
                'name' => 'New Company',
                'parent_id' => false,
                'currency_id' => [1, 'IDR'],
                'partner_id' => [200, 'Pre-existing Partner'],
                'active' => true,
            ],
        ]);

    $job = new CompanySync;
    $job->handle($gateway);

    $company = Company::where('odoo_id', 20)->first();

    expect($company->partner_id)->toBe($existingPartner->id);

    // Should not create a new duplicate partner
    expect(Partner::where('odoo_id', 200)->count())->toBe(1);
});
