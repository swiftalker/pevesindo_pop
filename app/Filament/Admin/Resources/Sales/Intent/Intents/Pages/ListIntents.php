<?php

namespace App\Filament\Admin\Resources\Sales\Intent\Intents\Pages;

use App\Filament\Admin\Resources\Sales\Intent\Intents\IntentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntents extends ListRecords
{
    protected static string $resource = IntentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
