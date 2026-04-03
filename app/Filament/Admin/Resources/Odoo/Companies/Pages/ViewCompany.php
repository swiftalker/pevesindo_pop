<?php

namespace App\Filament\Admin\Resources\Odoo\Companies\Pages;

use App\Engine\Odoo\Jobs\System\SyncCompaniesJob;
use App\Filament\Admin\Resources\Odoo\Companies\CompanyResource;
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
                ->action(function () {
                    $userId = auth()->id();
                    dispatch(new SyncCompaniesJob($userId));

                    Notification::make()
                        ->title('Sync Started')
                        ->icon('heroicon-o-arrow-path')
                        ->body('Sinkronisasi master data dimulai.')
                        ->info()
                        ->send();
                }),
        ];
    }
}
