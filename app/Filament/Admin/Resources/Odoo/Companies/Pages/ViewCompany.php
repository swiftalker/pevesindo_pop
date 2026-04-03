<?php

namespace App\Filament\Admin\Resources\Odoo\Companies\Pages;

use App\Filament\Admin\Resources\Odoo\Companies\CompanyResource;
use App\Engine\Odoo\Jobs\System\SyncCompaniesJob;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Sync from Odoo')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Sinkronisasi Master Data')
                ->modalDescription('Perusahaan, kontak, jurnal, rekening bank, dan akun analitik akan ditarik dari Odoo.')
                ->action(function () {
                    $userId = auth()->id();
                    dispatch(new SyncCompaniesJob($userId));
                }),
        ];
    }
}
