<?php

namespace App\Filament\Admin\Resources\Sales\Intent\Intents\Pages;

use App\Filament\Admin\Resources\Sales\Intent\Intents\IntentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditIntent extends EditRecord
{
    protected static string $resource = IntentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
