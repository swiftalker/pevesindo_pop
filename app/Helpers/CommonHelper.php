<?php

use App\Enums\NotificationSound;
use App\Models\Auth\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

if (! function_exists('notify')) {
    /**
     * Send a Filament database notification to one or more users.
     *
     * @param  object|array|int  $recipients  User model(s) or user ID(s)
     * @param  string  $title  Notification title
     * @param  string|null  $body  Notification body (optional)
     * @param  string  $sound  Sound key: crud, delivery, shipped, sync, error, log, alert
     * @param  string  $status  Filament status: success, warning, danger, info
     * @param  string|null  $direction  sender, receiver, or null
     * @param  bool  $loop  Sound loops on frontend until markAsRead() is called
     * @param  array  $extra  Extra viewData (url, action, custom data)
     *
     * Usage:
     *   notify($user, 'Order confirmed', 'SO-001', 'shipped');
     *   notify(5, 'New sale', 'Assigned to you', 'delivery', loop: true);
     *   notify([$user1, $user2], 'New order', null, 'alert', loop: true);
     */
    function notify(
        $recipients,
        string $title,
        ?string $body = null,
        string $sound = 'crud',
        string $status = 'success',
        ?string $direction = null,
        bool $loop = false,
        array $extra = [],
    ): void {
        $soundValue = resolve_sound_value($sound);
        $users = resolve_users($recipients);

        foreach ($users as $user) {
            $notificationId = 'notif-'.bin2hex(random_bytes(4));

            $notification = Notification::make($notificationId)
                ->title($title)
                ->{$status}()
                ->viewData(array_merge([
                    'sound' => $soundValue,
                    'loop' => $loop,
                    'direction' => $direction,
                    'notification_id' => $notificationId,
                ], $extra));

            if ($body) {
                $notification->body($body);
            }

            // Add a default action that marks as read and stops the sound loop
            if ($loop) {
                $notification->actions([
                    Action::make('mark_read')
                        ->button()
                        ->label('Tandai dibaca')
                        ->markAsRead()
                        ->close()
                        ->dispatch('stop-notification-sound', ['sound' => $soundValue]),
                ]);
            }

            $notification->sendToDatabase($user, isEventDispatched: true);
            $notification->broadcast($user);
            $notification->send();
        }
    }
}

if (! function_exists('notify_loop')) {
    /**
     * Send notification with looping sound until user marks as read.
     *
     * Shortcut wrapper for notify() with loop=true.
     *
     * @param  object|array|int  $recipients
     */
    function notify_loop(
        $recipients,
        string $title,
        ?string $body = null,
        string $sound = 'alert',
        string $status = 'warning',
        ?string $direction = null,
        array $extra = [],
    ): void {
        notify($recipients, $title, $body, $sound, $status, $direction, loop: true, extra: $extra);
    }
}

if (! function_exists('resolve_sound_value')) {
    /**
     * Validate a sound key against the NotificationSound enum.
     */
    function resolve_sound_value(string $sound): string
    {
        $valid = collect(NotificationSound::cases())
            ->map(fn ($case) => $case->value)
            ->all();

        return in_array($sound, $valid, true) ? $sound : NotificationSound::Crud->value;
    }
}

if (! function_exists('resolve_users')) {
    /**
     * Resolve recipients into User model instances.
     *
     * Accepts: single Model, int ID, array of Models, array of IDs, or mixed array.
     */
    function resolve_users(mixed $recipients): array
    {
        $model = get_model_for_users();

        if ($recipients instanceof Model) {
            return [$recipients];
        }

        if (is_int($recipients)) {
            return $model::whereKey($recipients)->get()->all();
        }

        if (is_array($recipients)) {
            $first = reset($recipients);

            if (is_int($first)) {
                return $model::whereIn('id', $recipients)->get()->all();
            }

            return $recipients;
        }

        return [$recipients];
    }
}

if (! function_exists('get_model_for_users')) {
    /**
     * Get the User model class name.
     */
    function get_model_for_users(): string
    {
        return config('auth.providers.users.model', User::class);
    }
}
