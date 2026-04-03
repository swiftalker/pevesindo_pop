<?php

namespace App\Engine\Odoo\Pipeline;

enum Stage: string
{
    case Default = 'default';
    case Odoo = 'odoo';
    case Bind = 'bind';
    case Pop = 'pop';

    public function label(): string
    {
        return match ($this) {
            self::Default => 'Data Lokal',
            self::Odoo => 'Mapping Odoo',
            self::Bind => 'Data Terkait',
            self::Pop => 'Trigger Domain',
        };
    }
}
