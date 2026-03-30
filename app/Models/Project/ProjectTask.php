<?php

namespace App\Models\Project;

use App\Models\HR\Employee;
use Carbon\Carbon;
use Database\Factories\ProjectTaskFactory;
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
 * @property Carbon $deadline
 * @property int $progress_percentage
 * @property string $milestone_status
 * @property string $task_state
 * @property string $sync_state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ProjectTask extends Model
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
        'deadline',
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
            'deadline' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProjectTaskFactory
    {
        return ProjectTaskFactory::new();
    }
}
