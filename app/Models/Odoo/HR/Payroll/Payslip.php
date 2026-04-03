<?php

namespace App\Models\Odoo\HR\Payroll;

use App\Models\Odoo\Core\Company;
use App\Models\Odoo\HR\Employee\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $employee_id
 * @property int $company_id
 * @property string $name
 * @property Carbon $date_from
 * @property Carbon $date_to
 * @property string $state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Payslip extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'odoo_id',
        'employee_id',
        'company_id',
        'name',
        'date_from',
        'date_to',
        'state',
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
            'company_id' => 'integer',
            'date_from' => 'date',
            'date_to' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
