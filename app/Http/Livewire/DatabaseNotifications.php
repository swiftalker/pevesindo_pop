<?php

namespace App\Http\Livewire;

use Filament\Notifications\Livewire\DatabaseNotifications as BaseDatabaseNotifications;
use Livewire\Attributes\On;

class DatabaseNotifications extends BaseDatabaseNotifications
{
    public function markAllNotificationsAsRead(): void
    {
        parent::markAllNotificationsAsRead();

        $this->dispatch('mark-as-read-all');
    }

    public function markNotificationAsRead(string $id): void
    {
        parent::markNotificationAsRead($id);

        $this->dispatch('stop-notification-sound', ['sound' => null]);
    }
}
