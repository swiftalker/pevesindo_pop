<?php

namespace App\Enums\Odoo;

enum SyncTaskStatus: string
{
    case Pending = 'pending';
    case Syncing = 'syncing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Syncing => 'Sinkronisasi',
            self::Completed => 'Selesai',
            self::Failed => 'Gagal',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Syncing => 'warning',
            self::Completed => 'success',
            self::Failed => 'danger',
        };
    }
}
