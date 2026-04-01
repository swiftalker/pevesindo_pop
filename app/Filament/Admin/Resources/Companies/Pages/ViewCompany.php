<?php

namespace App\Filament\Admin\Resources\Companies\Pages;

use App\Filament\Admin\Resources\Companies\CompanyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('sync')
                ->label('Sync from Odoo')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->action(function () {
                    \App\Engine\Odoo\Jobs\Core\CompanySync::dispatch();

                    \Filament\Notifications\Notification::make()
                        ->title('Sync Started')
                        ->body('Company synchronization is running in the background.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
