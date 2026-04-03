<?php

namespace App\Engine\Odoo\Jobs\System;

use App\Engine\Odoo\Client\OdooGateway;
use App\Engine\Odoo\Contracts\SyncJobInterface;
use App\Engine\Odoo\Pipeline\Pipeline;
use App\Engine\Odoo\SyncEvents;
use App\Models\Odoo\Core\Company;
use App\Models\Odoo\Core\Partner;
use App\Models\Odoo\Finance\BankAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncBankAccountsJob implements ShouldQueue, SyncJobInterface
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
            ->model('res.partner.bank')
            ->notifiable($this->notifiableUserId)
            ->topic(SyncEvents::TOPIC_JOURNALS)
            ->label('Bank Accounts Sync')
            ->run(function () use ($gateway) {
                $ids = $gateway->search('res.partner.bank', []);

                if (empty($ids)) {
                    return ['synced' => 0];
                }

                $records = $gateway->read('res.partner.bank', $ids, [
                    'bank_name', 'acc_number', 'acc_holder_name', 'company_id', 'partner_id', 'active', 'currency_id',
                ]);

                $now = now();
                $count = 0;

                foreach ($records as $record) {
                    $companyId = $this->resolveCompanyId($record['company_id'] ?? null);
                    $partnerId = $this->resolvePartnerId($record['partner_id'] ?? null);

                    BankAccount::updateOrCreate(
                        ['odoo_id' => $record['id']],
                        [
                            'bank_name' => $record['bank_name'] ?? null,
                            'acc_number' => $record['acc_number'] ?? null,
                            'acc_holder_name' => $record['acc_holder_name'] ?? null,
                            'company_id' => $companyId,
                            'partner_id' => $partnerId,
                            'is_active' => $record['active'] ?? true,
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

    protected function resolvePartnerId(mixed $field): ?int
    {
        $odooId = $this->extractId($field);

        if ($odooId) {
            $partner = Partner::where('odoo_id', $odooId)->first();

            return $partner?->id;
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
