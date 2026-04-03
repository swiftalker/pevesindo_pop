<?php

namespace App\Filament\Admin\Resources\Odoo\Companies\Tables;

use App\Engine\Odoo\Jobs\System\SyncCompaniesJob;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('currency')
                    ->label('Mata Uang')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('partner.name')
                    ->label('Kontak')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('contacts_count')
                    ->label('Kontak')
                    ->counts('contacts')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('journals_count')
                    ->label('Jurnal')
                    ->counts('journals')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bank_accounts_count')
                    ->label('Bank')
                    ->counts('bankAccounts')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Synced')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->headerActions([
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

                        Notification::make()
                            ->title('Sync Started')
                            ->body('Sinkronisasi master data dimulai.')
                            ->info()
                            ->send();
                    }),
            ])
            ->defaultSort('name');
    }
}
