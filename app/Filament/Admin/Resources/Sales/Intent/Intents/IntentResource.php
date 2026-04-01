<?php

namespace App\Filament\Admin\Resources\Sales\Intent\Intents;

use App\Filament\Admin\Resources\Sales\Intent\Intents\Pages\CreateIntent;
use App\Filament\Admin\Resources\Sales\Intent\Intents\Pages\EditIntent;
use App\Filament\Admin\Resources\Sales\Intent\Intents\Pages\ListIntents;
use App\Filament\Admin\Resources\Sales\Intent\Intents\RelationManagers\IntentItemsRelationManager;
use App\Filament\Admin\Resources\Sales\Intent\Intents\Schemas\IntentForm;
use App\Filament\Admin\Resources\Sales\Intent\Intents\Tables\IntentsTable;
use App\Models\Sales\Intent\Intent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IntentResource extends Resource
{
    protected static ?string $model = Intent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return IntentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            IntentItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIntents::route('/'),
            'create' => CreateIntent::route('/create'),
            'edit' => EditIntent::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
