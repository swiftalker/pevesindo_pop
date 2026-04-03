<?php

namespace App\Engine\Odoo\Pipeline;

use App\Models\Auth\User;

/**
 * Resolve user dan kirim notifikasi otomatis dari Pipeline.
 */
class Notifier
{
    public function __construct(
        protected ?int $userId = null,
    ) {}

    public function success(string $title, string $message): void
    {
        $user = $this->resolveUser();

        if ($user) {
            notify($user, $title, $message, 'sync', 'success');
        }
    }

    public function failed(string $title, string $error): void
    {
        $user = $this->resolveUser();

        if ($user) {
            notify_loop($user, $title, $error, 'error', 'danger');
        }
    }

    protected function resolveUser(): ?User
    {
        if (! $this->userId) {
            return null;
        }

        return User::find($this->userId);
    }
}
