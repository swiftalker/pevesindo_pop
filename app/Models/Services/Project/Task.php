<?php

namespace App\Models\Services\Project;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $project_id
 * @property int $odoo_id
 * @property string $name
 * @property int $assigned_to
 * @property int $progress_percentage
 * @property string $milestone_status
 * @property string $task_state
 * @property string $sync_state
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'center_app_ref',
        'project_id',
        'odoo_id',
        'name',
        'assigned_to',
        'progress_percentage',
        'milestone_status',
        'task_state',
        'sync_state',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'project_id' => 'integer',
            'assigned_to' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Services\Project\Project::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\HR\Employee\Employee::class);
    }
}
