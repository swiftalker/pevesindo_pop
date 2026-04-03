<?php

namespace App\Filament\Admin\Resources\Odoo\Companies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('odoo_id')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('code'),
                Toggle::make('is_active')
                    ->required(),
                Select::make('parent_id')
                    ->relationship('parent', 'name'),
                Select::make('partner_id')
                    ->relationship('partner', 'name'),
                TextInput::make('currency')
                    ->required()
                    ->default('IDR'),
            ]);
    }
}
