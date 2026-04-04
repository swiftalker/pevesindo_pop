<?php

namespace App\Engine\Odoo;

use App\Engine\Odoo\Enums\SyncTaskStatus;
use App\Engine\Odoo\Enums\SyncTaskType;
use App\Engine\Odoo\Events\OdooSyncCompleted;
use App\Engine\Odoo\Events\OdooSyncFailed;
use App\Models\Auth\User;
use App\Models\Odoo\Sync\OdooSyncTask;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Chain builder untuk menjalankan Odoo sync jobs.
 *
 * Pipeline otomatis handle:
 * - Task lifecycle (Syncing → Completed/Failed)
 * - Event dispatch (SyncCompleted, SyncFailed)
 * - Notifikasi user (success, failed looping)
 *
 * Usage:
 *   Pipeline::make()
 *       ->model('res.company')
 *       ->task($task)      // Atau otomatis dibuat jika tidak ada
 *       ->notifiable($userId)
 *       ->topic(SyncEvents::TOPIC_COMPANIES)
 *       ->label('Companies')
 *       ->run(fn () => $gateway->read(...));
 */
class Pipeline
{
    protected ?string $model = null;

    protected ?Model $record = null;

    protected ?OdooSyncTask $task = null;

    protected ?int $notifiableUserId = null;

    protected string $topic = 'odoo.sync';

    protected ?string $label = null;

    protected SyncTaskType $direction = SyncTaskType::Pull;

    public static function make(): self
    {
        return new self;
    }

    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function record(?Model $record): self
    {
        $this->record = $record;

        return $this;
    }

    public function task(?OdooSyncTask $task): self
    {
        $this->task = $task;

        return $this;
    }

    public function direction(SyncTaskType $direction): self
    {
        $this->direction = $direction;

        return $this;
    }

    public function push(): self
    {
        $this->direction = SyncTaskType::Push;

        return $this;
    }

    public function pull(): self
    {
        $this->direction = SyncTaskType::Pull;

        return $this;
    }

    public function notifiable(?int $userId): self
    {
        $this->notifiableUserId = $userId;

        return $this;
    }

    public function topic(string $topic): self
    {
        $this->topic = $topic;

        return $this;
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Jalankan work closure. Pipeline otomatis handle:
     * - Buat task jika belum ada
     * - Mark syncing → completed/failed
     * - Dispatch events
     * - Notifikasi user
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $work
     * @return TResult|array<string, mixed>
     *
     * @throws \Throwable
     */
    public function run(Closure $work): mixed
    {
        $this->ensureTask();
        $this->logStarted();
        $this->task->markSyncing();

        try {
            $result = $work();

            $this->handleSuccess($result);

            return $result;
        } catch (\Throwable $e) {
            $this->handleFailure($e);

            throw $e;
        }
    }

    protected function ensureTask(): void
    {
        if (! $this->task) {
            $this->task = OdooSyncTask::create([
                'type' => $this->direction,
                'model' => $this->model ?? 'unknown',
                'pop_app_ref' => $this->record?->id,
                'payload' => ['record_id' => $this->record?->getKey()],
                'status' => SyncTaskStatus::Pending,
            ]);
        }
    }

    protected function logStarted(): void
    {
        $label = $this->label ?? $this->model ?? 'sync';

        Log::info("Pipeline: Started [{$label}]");
    }

    protected function handleSuccess(mixed $result): void
    {
        $label = $this->label ?? $this->model ?? 'sync';

        // Extract odoo_id from result if available
        $odooId = is_array($result) ? ($result['odoo_id'] ?? null) : null;

        $this->task->markCompleted($odooId, is_array($result) ? $result : []);

        OdooSyncCompleted::dispatch($this->task, $this->topic);

        Log::info("Pipeline: Completed [{$label}]");

        $this->notifySuccess($label, $odooId);
    }

    protected function handleFailure(\Throwable $e): void
    {
        $label = $this->label ?? $this->model ?? 'sync';

        $this->task->markFailed($e->getMessage());

        OdooSyncFailed::dispatch($this->task, $this->topic);

        Log::error("Pipeline: Failed [{$label}]: {$e->getMessage()}");

        $this->notifyFailed($label, $e);
    }

    protected function notifySuccess(string $label, mixed $odooId): void
    {
        if (! $this->notifiableUserId) {
            return;
        }

        $user = User::find($this->notifiableUserId);

        if (! $user) {
            return;
        }

        $msg = ucfirst($label).' berhasil disinkronisasi'
            .($odooId !== null && $odooId !== 0 ? ' (Odoo #'.$odooId.')' : '')
            .'.';

        notify($user, "Sync {$label} Berhasil", $msg, 'sync', 'success');
    }

    protected function notifyFailed(string $label, \Throwable $e): void
    {
        if (! $this->notifiableUserId) {
            return;
        }

        $user = User::find($this->notifiableUserId);

        if (! $user) {
            return;
        }

        notify_loop(
            $user,
            "Sync {$label} Gagal",
            $e->getMessage(),
            'error',
            'danger'
        );
    }
}
