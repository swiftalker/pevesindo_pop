<?php

namespace App\Events\Odoo;

use App\Models\Odoo\Sync\OdooSyncTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when an Odoo sync task completes successfully.
 *
 * Livewire components can listen via Echo on the relevant topic channel.
 */
class OdooSyncCompleted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public OdooSyncTask $task,
        public string $topic,
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
        return 'odoo.sync.completed';
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
            'odoo_id' => $this->task->odoo_id,
            'topic' => $this->topic,
            'completed_at' => $this->task->completed_at?->toIso8601String(),
        ];
    }
}
