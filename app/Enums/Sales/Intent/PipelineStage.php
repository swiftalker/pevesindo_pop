<?php

namespace App\Enums\Sales\Intent;

enum PipelineStage: string
{
    case COLD = 'cold';
    case WARM = 'warm';
    case HOT = 'hot';
    case SURVEY_SCHEDULED = 'survey_scheduled';
    case NEGOTIATION = 'negotiation';
    case DEAL_MATERIAL = 'deal_material';
    case DEAL_PROJECT = 'deal_project';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::COLD => 'Cold',
            self::WARM => 'Warm',
            self::HOT => 'Hot',
            self::SURVEY_SCHEDULED => 'Survey Scheduled',
            self::NEGOTIATION => 'Negotiation',
            self::DEAL_MATERIAL => 'Deal Material',
            self::DEAL_PROJECT => 'Deal Project',
            self::CANCELLED => 'Cancelled',
        };
    }
}
