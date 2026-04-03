<?php

namespace App\Engine\Odoo\Jobs\System;

use App\Engine\Odoo\Client\OdooGateway;
use App\Engine\Odoo\Contracts\SyncJobInterface;
use App\Engine\Odoo\Pipeline\Pipeline;
use App\Engine\Odoo\SyncEvents;
use App\Models\Odoo\Core\Company;
use App\Models\Odoo\Finance\AnalyticAccount;
use App\Models\Odoo\Finance\AnalyticPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAnalyticAccountsJob implements ShouldQueue, SyncJobInterface
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
            ->model('account.analytic.account')
            ->notifiable($this->notifiableUserId)
            ->topic(SyncEvents::TOPIC_COMPANIES)
            ->label('Analytic Accounts Sync')
            ->run(function () use ($gateway) {
                $ids = $gateway->search('account.analytic.account', []);

                if (empty($ids)) {
                    return ['synced' => 0];
                }

                $records = $gateway->read('account.analytic.account', $ids, [
                    'name', 'code', 'company_id', 'plan_id', 'active',
                ]);

                $now = now();
                $count = 0;

                foreach ($records as $record) {
                    $companyId = $this->resolveCompanyId($record['company_id'] ?? null);
                    $planId = $this->resolvePlanId($record['plan_id'] ?? null);

                    AnalyticAccount::updateOrCreate(
                        ['odoo_id' => $record['id']],
                        [
                            'name' => $record['name'],
                            'code' => $record['code'] ?? null,
                            'company_id' => $companyId,
                            'plan_id' => $planId,
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

    protected function resolvePlanId(mixed $field): ?int
    {
        $odooId = $this->extractId($field);

        if ($odooId) {
            $plan = AnalyticPlan::where('odoo_id', $odooId)->first();

            return $plan?->id;
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
