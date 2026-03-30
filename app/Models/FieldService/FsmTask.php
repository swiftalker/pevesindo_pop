<?php

namespace App\Models\FieldService;

use App\Models\Employee;
use App\Models\OdooCompany;
use App\Models\OdooPartner;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $odoo_id
 * @property int $odoo_company_id
 * @property int $odoo_partner_id
 * @property int $project_id
 * @property string $name
 * @property int $assigned_to
 * @property Carbon $planned_date_begin
 * @property Carbon $planned_date_end
 * @property float $effective_hours
 * @property string $fsm_state
 * @property string $sync_state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class FsmTask extends Model
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
        'odoo_company_id',
        'odoo_partner_id',
        'project_id',
        'name',
        'assigned_to',
        'planned_date_begin',
        'planned_date_end',
        'effective_hours',
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
            'odoo_company_id' => 'integer',
            'odoo_partner_id' => 'integer',
            'project_id' => 'integer',
            'assigned_to' => 'integer',
            'planned_date_begin' => 'timestamp',
            'planned_date_end' => 'timestamp',
            'effective_hours' => 'decimal:2',
        ];
    }

    public function odooCompany(): BelongsTo
    {
        return $this->belongsTo(OdooCompany::class);
    }

    public function odooPartner(): BelongsTo
    {
        return $this->belongsTo(OdooPartner::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
