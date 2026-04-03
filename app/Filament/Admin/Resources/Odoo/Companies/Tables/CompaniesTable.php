<?php

namespace App\Filament\Admin\Resources\Odoo\Companies\Tables;

use App\Engine\Odoo\Jobs\System\SyncCompaniesJob;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
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
                    ->boolean()
                    ->toggleable(isToggledHidden: false),
                TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->toggleable(isToggledHidden: true),
                TextColumn::make('partner.name')
                    ->label('Kontak Utama')
                    ->toggleable(isToggledHidden: true),
                TextColumn::make('currency')
                    ->label('Mata Uang')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHidden: true),
                TextColumn::make('journals_count')
                    ->label('Jurnal')
                    ->counts('journals')
                    ->numeric()
                    ->toggleable(isToggledHidden: true),
                TextColumn::make('bank_accounts_count')
                    ->label('Rekening Bank')
                    ->counts('bankAccounts')
                    ->numeric()
                    ->toggleable(isToggledHidden: true),
                TextColumn::make('updated_at')
                    ->label('Terakhir Sync')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHidden: true),
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
                        try {
                            dispatch(new SyncCompaniesJob(auth()->id()));

                            Notification::make()
                                ->title('Sync Started')
                                ->body('Sinkronisasi dimulai. Data akan segera diperbarui.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Sync Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('name');
    }
}
