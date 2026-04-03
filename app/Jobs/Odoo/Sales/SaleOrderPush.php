<?php

namespace App\Jobs\Odoo\Sales;

use App\Engine\Odoo\Adapters\Sales\SaleOrders;
use App\Engine\Odoo\SyncEvents;
use App\Enums\Odoo\SyncTaskStatus;
use App\Events\Odoo\OdooSyncCompleted;
use App\Events\Odoo\OdooSyncFailed;
use App\Models\Odoo\Sync\OdooSyncTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Pushes a sale order to Odoo.
 *
 * Mirrors Workers/SaleOrderPush.ex — handles push_draft, push_confirm,
 * push_cancel, and push_update actions.
 */
class SaleOrderPush implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 0;

    public int $timeout = 120;

    /**
     * @param  OdooSyncTask  $task  The sync task tracking record
     * @param  string  $action  The action: push_draft, push_confirm, push_cancel, push_update
     * @param  mixed  $record  The local record (e.g. Intent model)
     */
    public function __construct(
        public OdooSyncTask $task,
        public string $action,
        public mixed $record,
    ) {
        $this->onConnection(config('odoo.queue_connection', 'redis'));
        $this->onQueue(config('odoo.queue_name', 'odoo'));
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(24);
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [5, 30, 120, 300, 600];
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new RateLimited('odoo-api'),
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(SaleOrders $adapter): void
    {
        $this->task->markSyncing();

        Log::info("SaleOrderPush: Processing task #{$this->task->id} action={$this->action}", [
            'pop_app_ref' => $this->task->pop_app_ref,
            'attempt' => $this->task->attempts,
        ]);

        try {
            $result = match ($this->action) {
                'push_draft' => $adapter->pushDraft($this->record),
                'push_confirm' => $adapter->pushConfirm($this->record),
                'push_cancel' => $adapter->pushCancel($this->record),
                'push_update' => $adapter->pushUpdate($this->record),
                default => throw new \InvalidArgumentException("Unknown action: {$this->action}"),
            };

            $this->handleSuccess($result);
        } catch (\Throwable $e) {
            $this->handleFailure($e);
        }
    }

    /**
     * Handle a permanent job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error("SaleOrderPush: Task #{$this->task->id} permanently failed", [
            'error' => $exception?->getMessage(),
        ]);

        $this->task->markFailed($exception?->getMessage() ?? 'Unknown error');

        OdooSyncFailed::dispatch($this->task, SyncEvents::TOPIC_SALE_ORDERS);
    }

    /**
     * Handle a successful push result.
     *
     * @param  array<string, mixed>  $result
     */
    protected function handleSuccess(array $result): void
    {
        $odooId = $result['odoo_id'] ?? $this->task->odoo_id;

        $this->task->markCompleted($odooId, $result);

        Log::info("SaleOrderPush: Task #{$this->task->id} completed", [
            'odoo_id' => $odooId,
            'action' => $this->action,
        ]);

        SyncEvents::logCompleted(SyncEvents::TOPIC_SALE_ORDERS, ['pushed' => 1]);

        OdooSyncCompleted::dispatch($this->task, SyncEvents::TOPIC_SALE_ORDERS);
    }

    /**
     * Handle a sync failure with retry logic.
     */
    protected function handleFailure(\Throwable $exception): void
    {
        Log::error("SaleOrderPush: Task #{$this->task->id} attempt failed", [
            'error' => $exception->getMessage(),
            'attempt' => $this->task->attempts,
        ]);

        if ($exception->getCode() === 429) {
            Log::warning("SaleOrderPush: Rate limited — snoozing task #{$this->task->id}");
            $this->task->update(['status' => SyncTaskStatus::Pending]);
            $this->release(60);

            return;
        }

        if ($this->task->canRetry()) {
            $this->task->update([
                'status' => SyncTaskStatus::Pending,
                'error_log' => $exception->getMessage(),
            ]);

            $this->release($this->backoff()[$this->task->attempts - 1] ?? 600);

            return;
        }

        $this->task->markFailed($exception->getMessage());

        OdooSyncFailed::dispatch($this->task, SyncEvents::TOPIC_SALE_ORDERS);
    }
}
