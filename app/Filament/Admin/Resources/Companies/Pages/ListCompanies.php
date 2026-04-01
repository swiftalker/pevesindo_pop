<?php

namespace App\Filament\Admin\Resources\Companies\Pages;

use App\Filament\Admin\Resources\Companies\CompanyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Helpers\CommonHelper;
use App\Enums\NotificationSound;

class ListCompanies extends ListRecords
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
                    // \App\Engine\Odoo\Jobs\Core\CompanySync::dispatch();
                    CommonHelper::notify(
                        auth()->user(),
                        'Sync Started',
                        'Company synchronization is running in the background.',
                        NotificationSound::Delivery,
                        'success',
                    );
                    // \Filament\Notifications\Notification::make()
                    //     ->title('Sync Started')
                    //     ->body('Company synchronization is running in the background.')
                    //     ->success()
                    //     ->send();
                }),
        ];
    }
}
