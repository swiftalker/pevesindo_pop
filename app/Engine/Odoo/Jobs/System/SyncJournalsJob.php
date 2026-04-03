<?php

namespace App\Engine\Odoo\Jobs\System;

use App\Engine\Odoo\Client\OdooGateway;
use App\Engine\Odoo\Contracts\SyncJobInterface;
use App\Engine\Odoo\Enums\SyncTaskStatus;
use App\Engine\Odoo\Enums\SyncTaskType;
use App\Engine\Odoo\Pipeline\Pipeline;
use App\Engine\Odoo\SyncEvents;
use App\Models\Odoo\Core\Company;
use App\Models\Odoo\Finance\Journal;
use App\Models\Odoo\Sync\OdooSyncTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncJournalsJob implements ShouldQueue, SyncJobInterface
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
            ->model('account.journal')
            ->notifiable($this->notifiableUserId)
            ->topic(SyncEvents::TOPIC_JOURNALS)
            ->label('Journals Sync')
            ->run(function () use ($gateway) {
                $ids = $gateway->search('account.journal', []);

                if (empty($ids)) {
                    return ['synced' => 0];
                }

                $records = $gateway->read('account.journal', $ids, [
                    'name', 'code', 'type', 'company_id', 'active', 'currency_id',
                ]);

                $now = now();
                $count = 0;

                foreach ($records as $record) {
                    $companyId = $this->resolveCompanyId($gateway, $record['company_id'] ?? null);

                    Journal::updateOrCreate(
                        ['odoo_id' => $record['id']],
                        [
                            'name' => $record['name'],
                            'code' => $record['code'] ?? null,
                            'journal_type' => $record['type'] ?? null,
                            'company_id' => $companyId,
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

    protected function createTask(): OdooSyncTask
    {
        return OdooSyncTask::create([
            'type' => SyncTaskType::Pull,
            'model' => 'account.journal',
            'status' => SyncTaskStatus::Pending,
            'payload' => [],
        ]);
    }

    protected function resolveCompanyId(OdooGateway $gateway, mixed $field): ?int
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
