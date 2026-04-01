<?php

use App\Enums\NotificationSound;
use Filament\Notifications\Notification;

it('has all required sound cases', function () {
    $cases = NotificationSound::cases();

    expect($cases)->toHaveCount(7)
        ->and(NotificationSound::Crud->value)->toBe('crud')
        ->and(NotificationSound::Delivery->value)->toBe('delivery')
        ->and(NotificationSound::Shipped->value)->toBe('shipped')
        ->and(NotificationSound::SyncSuccess->value)->toBe('sync')
        ->and(NotificationSound::SyncFailed->value)->toBe('error')
        ->and(NotificationSound::Log->value)->toBe('log')
        ->and(NotificationSound::Alert->value)->toBe('alert');
});

it('generates correct sound file paths', function () {
    expect(NotificationSound::Crud->path())->toBe('/sounds/crud.mp3')
        ->and(NotificationSound::Alert->path())->toBe('/sounds/alert.mp3')
        ->and(NotificationSound::Delivery->path())->toBe('/sounds/delivery.mp3');
});

it('returns human-readable labels', function (NotificationSound $sound) {
    expect($sound->label())->toBeString()->not->toBeEmpty();
})->with(NotificationSound::cases());

it('filament notification carries sound in viewData', function () {
    $notification = Notification::make()
        ->title('Test Notification')
        ->success()
        ->viewData([
            'sound' => NotificationSound::Delivery->value,
        ]);

    $data = $notification->toArray();

    expect($data)
        ->toHaveKey('title', 'Test Notification')
        ->toHaveKey('status', 'success')
        ->toHaveKey('viewData')
        ->and($data['viewData'])->toHaveKey('sound', 'delivery');
});

it('database message includes sound in viewData', function () {
    $notification = Notification::make()
        ->title('Delivery Ready')
        ->warning()
        ->viewData([
            'sound' => NotificationSound::Shipped->value,
        ]);

    $dbMessage = $notification->getDatabaseMessage();

    expect($dbMessage)
        ->toHaveKey('title', 'Delivery Ready')
        ->toHaveKey('status', 'warning')
        ->toHaveKey('viewData')
        ->and($dbMessage['viewData'])->toHaveKey('sound', 'shipped');
});

it('different sounds produce different viewData values', function (NotificationSound $sound) {
    $notification = Notification::make()
        ->title('Test')
        ->success()
        ->viewData(['sound' => $sound->value]);

    $data = $notification->getDatabaseMessage();

    expect($data['viewData']['sound'])->toBe($sound->value);
})->with([
    'crud' => NotificationSound::Crud,
    'delivery' => NotificationSound::Delivery,
    'shipped' => NotificationSound::Shipped,
    'sync' => NotificationSound::SyncSuccess,
    'error' => NotificationSound::SyncFailed,
    'log' => NotificationSound::Log,
    'alert' => NotificationSound::Alert,
]);

it('admin panel has database notifications enabled', function () {
    $panel = filament()->getPanel('admin');

    expect($panel->hasDatabaseNotifications())->toBeTrue();
});

it('admin panel has polling disabled', function () {
    $panel = filament()->getPanel('admin');

    expect($panel->getDatabaseNotificationsPollingInterval())->toBeNull();
});

it('notification sound blade template exists', function () {
    expect(file_exists(resource_path('views/components/notification-sound.blade.php')))->toBeTrue();
});

it('sounds directory exists in public', function () {
    expect(is_dir(public_path('sounds')))->toBeTrue();
});
