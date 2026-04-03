<?php

namespace App\Engine\Odoo\Jobs\System;

use App\Engine\Odoo\Client\OdooGateway;
use App\Engine\Odoo\Contracts\SyncJobInterface;
use App\Engine\Odoo\Pipeline\Pipeline;
use App\Engine\Odoo\SyncEvents;
use App\Models\Odoo\Core\Company;
use App\Models\Odoo\Core\Partner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncContactsJob implements ShouldQueue, SyncJobInterface
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        protected ?int $notifiableUserId = null,
    ) {}

    public function handle(OdooGateway $gateway): void
    {
        Pipeline::make()
            ->model('res.partner')
            ->notifiable($this->notifiableUserId)
            ->topic(SyncEvents::TOPIC_CUSTOMERS)
            ->label('Contacts Sync')
            ->run(function () use ($gateway) {
                $ids = $gateway->search('res.partner', [['active', '=', true]]);

                if (empty($ids)) {
                    return ['synced' => 0];
                }

                $records = $gateway->read('res.partner', $ids, [
                    'name', 'email', 'phone', 'is_company',
                    'company_id', 'customer_rank', 'supplier_rank', 'active',
                ]);

                $now = now();
                $count = 0;

                foreach ($records as $record) {
                    $companyId = $this->resolveCompanyId($record['company_id'] ?? null);

                    Partner::updateOrCreate(
                        ['odoo_id' => $record['id']],
                        [
                            'name' => $record['name'] ?? null,
                            'email' => $record['email'] ?? null,
                            'phone' => $record['phone'] ?? null,
                            'is_company' => $record['is_company'] ?? false,
                            'company_id' => $companyId,
                            'customer_rank' => $record['customer_rank'] ?? 0,
                            'supplier_rank' => $record['supplier_rank'] ?? 0,
                            'is_active' => $record['active'] ?? true,
                            'source' => 'odoo',
                            'odoo_data' => $record,
                            'synced_at' => $now,
                        ]
                    );

                    $count++;
                }

                return ['synced' => $count];
            });
    }

    protected function resolveCompanyId(mixed $field): ?int
    {
        $odooId = $this->extractId($field);

        if ($odooId) {
            $company = Company::where('odoo_id', $odooId)->first();

            return $company?->id;
        }

        return null;
    }

    protected function extractId(mixed $field): ?int
    {
        if (is_array($field) && count($field) === 2 && is_numeric($field[0])) {
            return (int) $field[0];
        }

        return null;
    }
}
