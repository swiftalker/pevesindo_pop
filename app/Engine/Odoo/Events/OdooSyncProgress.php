<?php

namespace App\Engine\Odoo\Events;

use App\Models\Odoo\Sync\OdooSyncTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast to report progress during a long-running Odoo sync task.
 *
 * Usage in sync workers:
 *   OdooSyncProgress::dispatch($task, $topic, $current, $total);
 */
class OdooSyncProgress implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public OdooSyncTask $task,
        public string $topic,
        public int $current,
        public int $total,
        public ?string $message = null,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel($this->topic),
            new Channel('odoo.sync'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'odoo.sync.progress';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->task->id,
            'type' => $this->task->type->value,
            'model' => $this->task->model,
            'pop_app_ref' => $this->task->pop_app_ref,
            'topic' => $this->topic,
            'current' => $this->current,
            'total' => $this->total,
            'percent' => $this->total > 0 ? round(($this->current / $this->total) * 100, 1) : 0,
            'message' => $this->message,
        ];
    }
}
