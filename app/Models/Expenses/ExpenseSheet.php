<?php

namespace App\Models\Expenses;

use App\Models\Employee;
use App\Models\OdooCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $employee_id
 * @property int $odoo_id
 * @property int $odoo_company_id
 * @property string $name
 * @property string $state
 * @property string $sync_state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ExpenseSheet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'center_app_ref',
        'employee_id',
        'odoo_id',
        'odoo_company_id',
        'name',
        'state',
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
            'employee_id' => 'integer',
            'odoo_company_id' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function odooCompany(): BelongsTo
    {
        return $this->belongsTo(OdooCompany::class);
    }
}
