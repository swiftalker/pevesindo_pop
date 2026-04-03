<?php

namespace App\Engine\Odoo\Jobs\System;

use App\Engine\Odoo\Client\OdooGateway;
use App\Engine\Odoo\Contracts\SyncJobInterface;
use App\Engine\Odoo\Pipeline\Pipeline;
use App\Engine\Odoo\SyncEvents;
use App\Models\Odoo\Core\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCompaniesJob implements ShouldQueue, SyncJobInterface
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
            ->model('res.company')
            ->notifiable($this->notifiableUserId)
            ->topic(SyncEvents::TOPIC_COMPANIES)
            ->label('Companies Sync')
            ->run(function () use ($gateway) {
                $odooIds = $gateway->search('res.company', []);

                if (empty($odooIds)) {
                    Log::info('CompanySync: No companies found');

                    return ['synced' => 0];
                }

                $records = $gateway->read('res.company', $odooIds, [
                    'name', 'parent_id', 'currency_id', 'partner_id', 'active',
                ]);

                $now = now();
                $count = 0;

                foreach ($records as $record) {
                    Company::updateOrCreate(
                        ['odoo_id' => $record['id']],
                        [
                            'name' => $record['name'],
                            'currency' => $this->extractCurrency($record['currency_id'] ?? null),
                            'is_active' => $record['active'] ?? true,
                            'odoo_data' => $record,
                            'synced_at' => $now,
                        ]
                    );

                    $count++;
                }

                // Chain related syncs
                dispatch(new SyncJournalsJob($this->notifiableUserId));
                dispatch(new SyncBankAccountsJob($this->notifiableUserId));
                dispatch(new SyncAnalyticAccountsJob($this->notifiableUserId));
                dispatch(new SyncContactsJob($this->notifiableUserId));

                return ['synced' => $count, 'ids' => $odooIds];
            });
    }

    protected function extractCurrency(mixed $field): string
    {
        if (is_array($field) && count($field) === 2) {
            return $field[1];
        }

        return 'IDR';
    }
}
