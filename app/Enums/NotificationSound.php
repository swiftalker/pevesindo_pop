<?php

namespace App\Enums;

/**
 * Sound categories for notification audio feedback.
 *
 * Each case maps to a .mp3 file in public/sounds/{value}.mp3
 */
enum NotificationSound: string
{
    case Crud = 'crud';
    case Delivery = 'delivery';
    case Shipped = 'shipped';
    case SyncSuccess = 'sync';
    case SyncFailed = 'error';
    case Log = 'log';
    case Alert = 'alert';

    /**
     * Human-readable label for this sound category.
     */
    public function label(): string
    {
        return match ($this) {
            self::Crud => 'CRUD Operation',
            self::Delivery => 'Delivery Order',
            self::Shipped => 'Shipment Complete',
            self::SyncSuccess => 'Sync Success',
            self::SyncFailed => 'Sync Failed',
            self::Log => 'Activity Log',
            self::Alert => 'Alert',
        };
    }

    /**
     * Get the public URL path for this sound file.
     */
    public function path(): string
    {
        return '/sounds/'.$this->value.'.mp3';
    }
}
