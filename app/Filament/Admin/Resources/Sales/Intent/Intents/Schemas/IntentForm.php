<?php

namespace App\Filament\Admin\Resources\Sales\Intent\Intents\Schemas;

use App\Enums\Sales\Intent\IntentState;
use App\Enums\Sales\Intent\PipelineStage;
use App\Enums\Sales\Intent\SalesType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IntentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer & Pipeline Details')->schema([
                    TextInput::make('customer_name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('customer_phone')
                        ->tel()
                        ->maxLength(255),
                    Textarea::make('project_address')
                        ->columnSpanFull(),
                    Select::make('sales_type')
                        ->options(SalesType::class)
                        ->required(),
                    Select::make('intent_state')
                        ->options(IntentState::class)
                        ->required(),
                    Select::make('pipeline_stage')
                        ->options(PipelineStage::class)
                        ->required(),
                ])->columns(2),

                Section::make('Revenue & Configuration')->schema([
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->required(),
                    Select::make('company_id')
                        ->relationship('company', 'name')
                        ->required(),
                    Select::make('pricelist_id')
                        ->relationship('pricelist', 'name')
                        ->nullable(),
                    TextInput::make('expected_revenue')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0.00),
                ])->columns(2),

                Section::make('Odoo References')->schema([
                    TextInput::make('odoo_lead_id')->numeric()->label('Odoo CRM Lead ID'),
                    TextInput::make('odoo_order_id')->numeric()->label('Odoo Sale Order ID'),
                    TextInput::make('odoo_picking_id')->numeric()->label('Odoo Delivery ID'),
                    TextInput::make('odoo_purchase_id')->numeric()->label('Odoo Purchase ID'),
                ])->columns(2)->collapsed(),
            ]);
    }
}
