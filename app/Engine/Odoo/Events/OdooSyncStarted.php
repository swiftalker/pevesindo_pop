<?php

namespace App\Engine\Odoo\Events;

use App\Models\Odoo\Sync\OdooSyncTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when an Odoo sync task begins execution.
 *
 * Livewire components can listen via Echo on the relevant topic channel.
 */
class OdooSyncStarted implements ShouldBroadcast
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
        return 'odoo.sync.started';
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
            'started_at' => $this->task->created_at?->toIso8601String(),
        ];
    }
}
