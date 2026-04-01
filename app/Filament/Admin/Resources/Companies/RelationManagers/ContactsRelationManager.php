<?php

namespace App\Filament\Admin\Resources\Companies\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $title = 'Kontak';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('odoo_id')
                    ->label('Odoo ID')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable(),
                TextColumn::make('mobile')
                    ->label('HP')
                    ->searchable(),
                IconColumn::make('is_company')
                    ->label('Perusahaan?')
                    ->boolean(),
            ])
            ->defaultSort('name');
    }
}
