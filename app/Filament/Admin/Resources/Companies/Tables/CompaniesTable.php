<?php

namespace App\Filament\Admin\Resources\Companies\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('odoo_id')
                    ->label('Odoo ID')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->searchable(),
                TextColumn::make('parent.name')
                    ->label('Pusat')
                    ->placeholder('— Pusat —')
                    ->searchable(),
                TextColumn::make('currency')
                    ->label('Mata Uang')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('journals_count')
                    ->label('Jurnal')
                    ->counts('journals')
                    ->numeric(),
                TextColumn::make('bank_accounts_count')
                    ->label('Bank')
                    ->counts('bankAccounts')
                    ->numeric(),
                TextColumn::make('updated_at')
                    ->label('Terakhir Sync')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
                SelectFilter::make('parent_id')
                    ->label('Pusat')
                    ->relationship('parent', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
