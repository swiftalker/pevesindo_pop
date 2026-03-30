<?php

namespace App\Models\Project;

use App\Models\Employee;
use App\Models\ProjectTask;
use Carbon\Carbon;
use Database\Factories\ProjectProgressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_task_id
 * @property int $reported_by
 * @property int $progress_percentage
 * @property string $notes
 * @property string $photos
 * @property string $milestone_status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ProjectProgress extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'project_task_id',
        'reported_by',
        'progress_percentage',
        'notes',
        'photos',
        'milestone_status',
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
            'project_task_id' => 'integer',
            'reported_by' => 'integer',
            'photos' => 'array',
        ];
    }

    public function projectTask(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProjectProgressFactory
    {
        return ProjectProgressFactory::new();
    }
}
