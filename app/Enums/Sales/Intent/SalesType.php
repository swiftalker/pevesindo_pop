<?php

namespace App\Enums\Sales\Intent;

enum SalesType: string
{
    case CLOSED = 'closed';
    case OPEN = 'open';

    public function label(): string
    {
        return match ($this) {
            self::CLOSED => 'Closed',
            self::OPEN => 'Open',
        };
    }
}
