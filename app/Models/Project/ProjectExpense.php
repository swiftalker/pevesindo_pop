<?php

namespace App\Models\Project;

use App\Models\HR\Employee;
use Carbon\Carbon;
use Database\Factories\ProjectExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $project_id
 * @property int $project_task_id
 * @property int $employee_id
 * @property int $odoo_id
 * @property string $description
 * @property float $amount
 * @property string $category
 * @property string $expense_state
 * @property string $sync_state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ProjectExpense extends Model
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
        'project_task_id',
        'employee_id',
        'odoo_id',
        'description',
        'amount',
        'category',
        'expense_state',
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
            'project_task_id' => 'integer',
            'employee_id' => 'integer',
            'amount' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectTask(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProjectExpenseFactory
    {
        return ProjectExpenseFactory::new();
    }
}
