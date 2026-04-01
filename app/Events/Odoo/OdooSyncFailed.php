<?php

namespace App\Events\Odoo;

use App\Engine\Odoo\Models\OdooSyncTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when an Odoo sync task fails permanently.
 */
class OdooSyncFailed implements ShouldBroadcast
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
        return 'odoo.sync.failed';
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
            'error' => mb_substr($this->task->error_log ?? '', 0, 500),
            'attempts' => $this->task->attempts,
        ];
    }
}
