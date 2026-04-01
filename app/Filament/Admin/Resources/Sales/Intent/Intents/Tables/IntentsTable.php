<?php

namespace App\Filament\Admin\Resources\Sales\Intent\Intents\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class IntentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_phone')
                    ->searchable(),
                TextColumn::make('sales_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('intent_state')
                    ->badge()
                    ->sortable(),
                TextColumn::make('pipeline_stage')
                    ->badge()
                    ->sortable(),
                TextColumn::make('expected_revenue')
                    ->money('idr')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Salesperson')
                    ->sortable(),
                TextColumn::make('sync_state')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
