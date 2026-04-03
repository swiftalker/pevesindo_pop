<?php

namespace App\Enums\Odoo;

enum SyncTaskType: string
{
    case Push = 'push';
    case Pull = 'pull';

    public function label(): string
    {
        return match ($this) {
            self::Push => 'Push ke Odoo',
            self::Pull => 'Pull dari Odoo',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Push => 'heroicon-o-arrow-up-tray',
            self::Pull => 'heroicon-o-arrow-down-tray',
        };
    }
}
