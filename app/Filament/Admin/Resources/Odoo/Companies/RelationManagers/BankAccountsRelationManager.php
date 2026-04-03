<?php

namespace App\Filament\Admin\Resources\Odoo\Companies\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'bankAccounts';

    protected static ?string $title = 'Rekening Bank';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('odoo_id')
                    ->label('Odoo ID')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bank_name')
                    ->label('Bank')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('acc_number')
                    ->label('No. Rekening')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('acc_holder_name')
                    ->label('Atas Nama')
                    ->searchable(),
                TextColumn::make('partner.name')
                    ->label('Partner')
                    ->placeholder('-'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->defaultSort('bank_name');
    }
}
