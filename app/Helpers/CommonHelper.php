<?php

namespace App\Helpers;

use App\Enums\NotificationSound;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class CommonHelper
{
    /**
     * Send a Filament database notification with sound metadata.
     *
     * Sound is stored in the notification's viewData as ['sound' => 'value'],
     * which is persisted in the database and accessible on the frontend
     * via the notification's data.viewData.sound property.
     *
     * @param  Model  $recipient  The user to notify (must use Notifiable trait)
     * @param  string  $title  Notification title
     * @param  string|null  $body  Notification body text
     * @param  NotificationSound  $sound  Sound category to play
     * @param  string  $status  Filament status: success, warning, danger, info
     * @param  array<string, mixed>  $extra  Additional viewData to merge
     */
    public static function notify(
        Model $recipient,
        string $title,
        ?string $body = null,
        NotificationSound $sound = NotificationSound::Crud,
        string $status = 'success',
        array $extra = [],
    ): void {
        $notification = Notification::make()
            ->title($title)
            ->$status()
            ->viewData(array_merge([
                'sound' => $sound->value,
            ], $extra));

        if ($body) {
            $notification->body($body);
        }

        $notification->sendToDatabase($recipient, isEventDispatched: true);
        
        // Broadcast via Echo so the user sees a realtime toast and hears the sound
        // if they are not the one who triggered the action directly
        $notification->broadcast($recipient);
        
        // Also send locally via Livewire/JS so the current active user sees the toast immediately
        $notification->send();
    }

    /**
     * Notify multiple recipients with the same notification.
     *
     * @param  iterable<Model>  $recipients
     * @param  array<string, mixed>  $extra
     */
    public static function notifyMany(
        iterable $recipients,
        string $title,
        ?string $body = null,
        NotificationSound $sound = NotificationSound::Crud,
        string $status = 'success',
        array $extra = [],
    ): void {
        foreach ($recipients as $recipient) {
            static::notify($recipient, $title, $body, $sound, $status, $extra);
        }
    }
}
