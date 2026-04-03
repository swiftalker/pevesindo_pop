<?php

namespace App\Engine\Odoo\Jobs\System;

use App\Engine\Odoo\Client\OdooGateway;
use App\Engine\Odoo\Contracts\SyncJobInterface;
use App\Models\Odoo\Core\Company;
use App\Models\Odoo\Core\Partner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCompaniesJob implements ShouldQueue, SyncJobInterface
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function handle(OdooGateway $gateway): void
    {
        Log::info('CompanySync: Starting company sync from Odoo');

        $fields = ['name', 'parent_id', 'currency_id', 'partner_id', 'active'];

        $records = $gateway->searchRead('res.company', [], $fields);

        Log::info('CompanySync: Received '.count($records).' companies from Odoo');

        $now = now();

        // First pass: upsert companies (without parent or partner yet)
        foreach ($records as $record) {
            Company::updateOrCreate(
                ['odoo_id' => $record['id']],
                [
                    'name' => $record['name'],
                    'currency' => $this->extractCurrency($record['currency_id']),
                    'is_active' => $record['active'] ?? true,
                ]
            );
        }

        // Second pass: resolve parent_id references
        foreach ($records as $record) {
            $parentOdooId = $this->extractId($record['parent_id']);

            if ($parentOdooId) {
                $parent = Company::where('odoo_id', $parentOdooId)->first();
                $company = Company::where('odoo_id', $record['id'])->first();

                if ($parent && $company) {
                    $company->update(['parent_id' => $parent->id]);
                }
            }
        }

        // Third pass: resolve partner_id references
        foreach ($records as $record) {
            $partnerOdooId = $this->extractId($record['partner_id']);

            if ($partnerOdooId) {
                $company = Company::where('odoo_id', $record['id'])->first();

                if ($company) {
                    $this->resolveAndLinkPartner($company, $partnerOdooId, $record);
                }
            }
        }

        Log::info('CompanySync: Completed successfully');
    }

    private function resolveAndLinkPartner(Company $company, int $partnerOdooId, array $record): void
    {
        $partner = Partner::where('odoo_id', $partnerOdooId)->first();

        if ($partner) {
            $company->update(['partner_id' => $partner->id]);
        } else {
            // Create a stub partner
            $partner = Partner::create([
                'odoo_id' => $partnerOdooId,
                'name' => $record['name'],
                'is_company' => true,
                'company_id' => $company->id,
            ]);

            $company->update(['partner_id' => $partner->id]);
        }
    }

    private function extractCurrency(mixed $field): string
    {
        if (is_array($field) && count($field) === 2) {
            return $field[1];
        }

        return 'IDR';
    }

    private function extractId(mixed $field): ?int
    {
        if (is_array($field) && count($field) === 2 && is_numeric($field[0])) {
            return (int) $field[0];
        }

        return null;
    }
}
