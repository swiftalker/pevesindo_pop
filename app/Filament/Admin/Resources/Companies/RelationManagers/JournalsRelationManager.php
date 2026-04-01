<?php

namespace App\Filament\Admin\Resources\Companies\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JournalsRelationManager extends RelationManager
{
    protected static string $relationship = 'journals';

    protected static ?string $title = 'Jurnal';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('odoo_id')
                    ->label('Odoo ID')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Jurnal')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->searchable(),
                TextColumn::make('journal_type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'sale' => 'success',
                        'purchase' => 'warning',
                        'bank' => 'info',
                        'cash' => 'primary',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('name');
    }
}
