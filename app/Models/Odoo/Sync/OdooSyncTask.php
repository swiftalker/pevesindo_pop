<?php

namespace App\Models\Odoo\Sync;

use App\Enums\Odoo\SyncTaskStatus;
use App\Enums\Odoo\SyncTaskType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property SyncTaskType $type
 * @property string $model
 * @property string|null $pop_app_ref
 * @property int|null $odoo_id
 * @property array|null $payload
 * @property array|null $response_data
 * @property SyncTaskStatus $status
 * @property string|null $error_log
 * @property int $attempts
 * @property int $max_attempts
 * @property Carbon|null $last_attempted_at
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OdooSyncTask extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'attempts' => 0,
        'max_attempts' => 5,
    ];

    protected $fillable = [
        'type',
        'model',
        'pop_app_ref',
        'odoo_id',
        'payload',
        'response_data',
        'status',
        'error_log',
        'attempts',
        'max_attempts',
        'last_attempted_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SyncTaskType::class,
            'status' => SyncTaskStatus::class,
            'payload' => 'array',
            'response_data' => 'array',
            'odoo_id' => 'integer',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'last_attempted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Mark the task as syncing (in-progress).
     */
    public function markSyncing(): void
    {
        $this->update([
            'status' => SyncTaskStatus::Syncing,
            'attempts' => $this->attempts + 1,
            'last_attempted_at' => now(),
        ]);
    }

    /**
     * Mark the task as completed with optional Odoo response data.
     *
     * @param  array<string, mixed>  $responseData
     */
    public function markCompleted(?int $odooId = null, array $responseData = []): void
    {
        $this->update([
            'status' => SyncTaskStatus::Completed,
            'odoo_id' => $odooId ?? $this->odoo_id,
            'response_data' => $responseData ?: $this->response_data,
            'completed_at' => now(),
            'error_log' => null,
        ]);
    }

    /**
     * Mark the task as failed with an error message.
     */
    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => SyncTaskStatus::Failed,
            'error_log' => $errorMessage,
        ]);
    }

    /**
     * Reset the task back to pending for a retry.
     */
    public function restart(): void
    {
        $this->update([
            'status' => SyncTaskStatus::Pending,
            'error_log' => null,
        ]);
    }

    /**
     * Create a duplicate of this task as a new pending task.
     */
    public function duplicate(): static
    {
        return static::create([
            'type' => $this->type,
            'model' => $this->model,
            'pop_app_ref' => $this->pop_app_ref.'-dup-'.now()->timestamp,
            'payload' => $this->payload,
            'status' => SyncTaskStatus::Pending,
            'max_attempts' => $this->max_attempts,
        ]);
    }

    /**
     * Check if this task can be retried.
     */
    public function canRetry(): bool
    {
        return $this->attempts < $this->max_attempts;
    }

    /**
     * Scope to filter by status.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithStatus($query, SyncTaskStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter pending tasks ready for processing.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeReadyToProcess($query)
    {
        return $query
            ->where('status', SyncTaskStatus::Pending)
            ->orderBy('created_at');
    }
}
