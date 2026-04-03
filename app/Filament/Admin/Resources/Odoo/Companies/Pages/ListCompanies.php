<?php

namespace App\Filament\Admin\Resources\Odoo\Companies\Pages;

use App\Filament\Admin\Resources\Odoo\Companies\CompanyResource;
use App\Jobs\Odoo\Core\CompanySync;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCompanies extends ListRecords
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
                    CompanySync::dispatch();

                    Notification::make()
                        ->title('Sync Started')
                        ->body('Company synchronization is running in the background.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
