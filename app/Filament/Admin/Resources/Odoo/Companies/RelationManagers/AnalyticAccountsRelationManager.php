<?php

namespace App\Filament\Admin\Resources\Odoo\Companies\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnalyticAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'analyticAccounts';

    protected static ?string $title = 'Akun Analitik';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('odoo_id')
                    ->label('Odoo ID')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Akun')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->searchable(),
                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->searchable(),
            ])
            ->defaultSort('code');
    }
}
