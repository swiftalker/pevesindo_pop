<?php

namespace App\Engine\Odoo\Listeners;

use App\Engine\Odoo\Contracts\SyncJobInterface;
use App\Engine\Odoo\Enums\Direction;
use App\Engine\Odoo\Enums\SyncTaskStatus;
use App\Engine\Odoo\Enums\SyncTaskType;
use App\Engine\Odoo\Events\OdooSyncRequested;
use App\Models\Odoo\Sync\OdooSyncTask;
use Illuminate\Support\Facades\Log;

/**
 * Registry-based listener for OdooSyncRequested events.
 *
 * Domains register their job factory closures via the static register() method.
 * The listener resolves the appropriate job based on model + action,
 * creates an OdooSyncTask with the correct direction, and dispatches.
 *
 * Usage (register in a ServiceProvider):
 *   DispatchOdooSyncJob::register('sale.order', 'push_draft', fn ($event, $task) =>
 *       new App\Engine\Odoo\Jobs\Sales\PushOrderJob($task, $event->action, $event->record)
 *   );
 */
class DispatchOdooSyncJob
{
    /**
     * Registry of job factory closures.
     * Keys: model|action -> closure(OdooSyncRequested, OdooSyncTask): ?SyncJobInterface
     *
     * @var array<string, callable>
     */
    protected static array $registry = [];

    /**
     * Register a domain job for a specific model + action pair.
     */
    public static function register(string $model, string $action, callable $jobFactory): void
    {
        static::$registry[static::key($model, $action)] = $jobFactory;
    }

    /**
     * Register a wildcard handler for a model (matches any action).
     */
    public static function registerWildcard(string $model, callable $jobFactory): void
    {
        static::$registry[static::key($model, '*')] = $jobFactory;
    }

    /**
     * Handle the OdooSyncRequested event.
     */
    public function handle(OdooSyncRequested $event): void
    {
        $registryKey = static::key($event->model, $event->action);
        $wildcardKey = static::key($event->model, '*');

        $factory = static::$registry[$registryKey] ?? static::$registry[$wildcardKey] ?? null;

        $task = OdooSyncTask::create([
            'type' => $this->resolveTaskType($event),
            'model' => $event->model,
            'pop_app_ref' => $event->popAppRef,
            'payload' => $this->extractPayload($event),
            'status' => SyncTaskStatus::Pending,
        ]);

        Log::info("Odoo Sync: Created task #{$task->id} [{$event->direction->value}] {$event->model}/{$event->action}", [
            'pop_app_ref' => $event->popAppRef,
        ]);

        if (! $factory) {
            Log::warning("Odoo Sync: No job registered for {$event->model}/{$event->action}");
            $task->markFailed("No job handler for {$event->model}/{$event->action}");

            return;
        }

        try {
            $job = $factory($event, $task);

            if ($job) {
                dispatch($job->onQueue('odoo'));
            } else {
                Log::warning("Odoo Sync: Job factory returned null for {$event->model}/{$event->action}");
                $task->markFailed('Job factory returned null');
            }
        } catch (\Throwable $e) {
            Log::error("Odoo Sync: Failed to dispatch job: {$e->getMessage()}", [
                'exception' => $e,
            ]);
            $task->markFailed("Dispatch error: {$e->getMessage()}");
        }
    }

    /**
     * Resolve the task type from the event direction.
     */
    protected function resolveTaskType(OdooSyncRequested $event): SyncTaskType
    {
        return match ($event->direction) {
            Direction::Push => SyncTaskType::Push,
            Direction::Pull => SyncTaskType::Pull,
            Direction::Report => SyncTaskType::Pull,
        };
    }

    /**
     * Extract payload from the event record for storage.
     *
     * @return array<string, mixed>
     */
    protected function extractPayload(OdooSyncRequested $event): array
    {
        if (is_array($event->record)) {
            return $event->record;
        }

        if (is_object($event->record) && method_exists($event->record, 'toArray')) {
            return $event->record->toArray();
        }

        return ['record_id' => $event->record];
    }

    /**
     * Build a registry key from model and action.
     */
    protected static function key(string $model, string $action): string
    {
        return "{$model}|{$action}";
    }

    /**
     * Get all registered model-action pairs.
     *
     * @return array<int, string>
     */
    public static function registered(): array
    {
        return array_keys(static::$registry);
    }
}
