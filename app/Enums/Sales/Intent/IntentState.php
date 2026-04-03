<?php

namespace App\Enums\Sales\Intent;

enum IntentState: string
{
    case PROSPECT = 'prospect';
    case PIPELINE = 'pipeline';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PROSPECT => 'Prospect',
            self::PIPELINE => 'Pipeline',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
