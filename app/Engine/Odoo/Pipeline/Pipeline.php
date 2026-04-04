<?php

namespace App\Engine\Odoo\Pipeline;

use App\Engine\Odoo\Enums\SyncTaskStatus;
use App\Engine\Odoo\Enums\SyncTaskType;
use App\Engine\Odoo\Events\OdooSyncCompleted;
use App\Engine\Odoo\Events\OdooSyncFailed;
use App\Engine\Odoo\SyncEvents;
use App\Models\Odoo\Sync\OdooSyncTask;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Pipeline
{
    protected string $model = '';

    protected ?int $notifiableUserId = null;

    protected string $topic = '';

    protected string $label = 'Odoo Sync';

    protected ?OdooSyncTask $existingTask = null;

    public function task(OdooSyncTask $task): self
    {
        $this->existingTask = $task;

        $this->existingTask->markSyncing();

        return $this;
    }

    public static function make(): self
    {
        return new self;
    }

    public function model(string $model): self
    {
        $this->model = $model;

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

    public function run(Closure $callback): void
    {
        Log::info("{$this->label}: Starting [{$this->topic}]");

        SyncEvents::logStarted($this->topic);

        try {
            $result = DB::transaction(fn () => $callback());

            $count = $result['synced'] ?? $result['pushed'] ?? 0;

            SyncEvents::logCompleted($this->topic, ['synced' => $count]);

            if ($this->existingTask) {
                $this->existingTask->markCompleted(payload: ['count' => $count]);
                OdooSyncCompleted::dispatch($this->existingTask, $this->topic);
            } else {
                $task = OdooSyncTask::create([
                    'type' => SyncTaskType::Pull,
                    'model' => $this->model ?: 'unknown',
                    'status' => SyncTaskStatus::Completed,
                    'payload' => $result,
                ]);

                OdooSyncCompleted::dispatch($task, $this->topic);
            }

            $this->notify(
                success: "{$this->label} Berhasil",
                message: "{$count} data berhasil disinkronisasi dari Odoo.",
            );
        } catch (\Throwable $e) {
            $this->handleFailure($e);
        }
    }

    protected function handleFailure(\Throwable $e): void
    {
        Log::error("{$this->label} failed: {$e->getMessage()}");

        if ($this->existingTask) {
            $this->existingTask->markFailed($e->getMessage());
            OdooSyncFailed::dispatch($this->existingTask, $this->topic);
        } else {
            $task = OdooSyncTask::create([
                'type' => SyncTaskType::Pull,
                'model' => $this->model ?: 'unknown',
                'status' => SyncTaskStatus::Failed,
                'error_log' => $e->getMessage(),
            ]);

            OdooSyncFailed::dispatch($task, $this->topic);
        }

        $this->notify(
            failed: "{$this->label} Gagal",
            error: $e->getMessage(),
        );
    }

    protected function notify(?string $success = null, ?string $message = null, ?string $failed = null, ?string $error = null): void
    {
        if ($this->notifiableUserId === null) {
            return;
        }

        $notifier = new Notifier($this->notifiableUserId);

        if ($success && $message) {
            $notifier->success($success, $message);
        }

        if ($failed && $error) {
            $notifier->failed($failed, $error);
        }
    }
}
