<?php

namespace App\Models\Services\Timesheet;

use App\Models\Odoo\HR\Employee\Employee;
use App\Models\Services\FieldService\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $pop_app_ref
 * @property int $fsm_task_id
 * @property int $odoo_id
 * @property int $employee_id
 * @property string $name
 * @property float $unit_amount
 * @property Carbon $date
 * @property string $sync_state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Timelog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pop_app_ref',
        'fsm_task_id',
        'odoo_id',
        'employee_id',
        'name',
        'unit_amount',
        'date',
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
            'fsm_task_id' => 'integer',
            'employee_id' => 'integer',
            'unit_amount' => 'decimal:2',
            'date' => 'date',
        ];
    }

    public function fsmTask(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
