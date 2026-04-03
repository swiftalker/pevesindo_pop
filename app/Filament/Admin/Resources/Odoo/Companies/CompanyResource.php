<?php

namespace App\Filament\Admin\Resources\Odoo\Companies;

use App\Filament\Admin\Resources\Odoo\Companies\Pages\ListCompanies;
use App\Filament\Admin\Resources\Odoo\Companies\Pages\ViewCompany;
use App\Filament\Admin\Resources\Odoo\Companies\RelationManagers\AnalyticAccountsRelationManager;
use App\Filament\Admin\Resources\Odoo\Companies\RelationManagers\BankAccountsRelationManager;
use App\Filament\Admin\Resources\Odoo\Companies\RelationManagers\ContactsRelationManager;
use App\Filament\Admin\Resources\Odoo\Companies\RelationManagers\JournalsRelationManager;
use App\Filament\Admin\Resources\Odoo\Companies\Schemas\CompanyInfolist;
use App\Filament\Admin\Resources\Odoo\Companies\Tables\CompaniesTable;
use App\Models\Odoo\Core\Company;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-building-office';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Odoo Master Data';
    }

    public static function getNavigationLabel(): string
    {
        return 'Companies';
    }

    public static function infolist(Schema $schema): Schema
    {
        return CompanyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompaniesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            JournalsRelationManager::class,
            BankAccountsRelationManager::class,
            AnalyticAccountsRelationManager::class,
            ContactsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'view' => ViewCompany::route('/{record}'),
        ];
    }
}
