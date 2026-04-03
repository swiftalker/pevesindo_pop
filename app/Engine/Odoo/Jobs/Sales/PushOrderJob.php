<?php

namespace App\Engine\Odoo\Jobs\Sales;

use App\Engine\Odoo\Domains\Sales\Orders\OrderIntegration;
use App\Engine\Odoo\Pipeline\Pipeline;
use App\Engine\Odoo\SyncEvents;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

/**
 * Pushes a sale order to Odoo via Pipeline.
 *
 * Supports push_draft, push_confirm, push_cancel, push_update.
 */
class PushOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 0;

    public int $timeout = 120;

    public function __construct(
        public mixed $record,
        public string $action,
        public ?int $notifiableUserId = null,
    ) {
        $this->onConnection(config('odoo.queue_connection', 'redis'));
        $this->onQueue(config('odoo.queue_name', 'odoo'));
    }

    public function retryUntil(): \DateTimeInterface
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
        return [new RateLimited('odoo-api')];
    }

    public function handle(OrderIntegration $adapter): void
    {
        $method = match ($this->action) {
            'push_draft' => 'pushDraft',
            'push_confirm' => 'pushConfirm',
            'push_cancel' => 'pushCancel',
            'push_update' => 'pushUpdate',
            default => throw new \InvalidArgumentException("Unknown action: {$this->action}"),
        };

        Pipeline::make()
            ->model('sale.order')
            ->record($this->record)
            ->notifiable($this->notifiableUserId)
            ->topic(SyncEvents::TOPIC_SALE_ORDERS)
            ->label("Push Order ({$this->action})")
            ->push()
            ->run(fn () => $adapter->{$method}($this->record));
    }
}
