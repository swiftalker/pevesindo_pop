<?php

namespace App\Models\Services\FieldService;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $odoo_id
 * @property int $company_id
 * @property int $partner_id
 * @property int $project_id
 * @property string $name
 * @property int $assigned_to
 * @property string $worksheet_result
 * @property \Carbon\Carbon $reschedule_requested_at
 * @property string $reschedule_reason
 * @property \Carbon\Carbon $reassignment_requested_at
 * @property string $reassignment_reason
 * @property string $fsm_state
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
        'odoo_id',
        'company_id',
        'partner_id',
        'project_id',
        'name',
        'assigned_to',
        'worksheet_result',
        'reschedule_requested_at',
        'reschedule_reason',
        'reassignment_requested_at',
        'reassignment_reason',
        'fsm_state',
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
            'company_id' => 'integer',
            'partner_id' => 'integer',
            'project_id' => 'integer',
            'assigned_to' => 'integer',
            'reschedule_requested_at' => 'timestamp',
            'reassignment_requested_at' => 'timestamp',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Core\Company::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Core\Partner::class);
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
