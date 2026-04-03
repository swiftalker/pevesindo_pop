<?php

namespace App\Engine\Odoo\Enums;

enum Direction: string
{
    case Push = 'push';
    case Pull = 'pull';
    case Report = 'report';

    public function label(): string
    {
        return match ($this) {
            self::Push => 'Push ke Odoo',
            self::Pull => 'Pull dari Odoo',
            self::Report => 'Ambil Report Odoo',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Push => 'heroicon-o-arrow-up-tray',
            self::Pull => 'heroicon-o-arrow-down-tray',
            self::Report => 'heroicon-o-document-chart-bar',
        };
    }

    public function isPush(): bool
    {
        return $this === self::Push;
    }

    public function isPull(): bool
    {
        return $this === self::Pull;
    }

    public function isReport(): bool
    {
        return $this === self::Report;
    }
}
