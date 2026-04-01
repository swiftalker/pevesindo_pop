<?php

namespace App\Models\Finance\Expenses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $employee_id
 * @property int $odoo_id
 * @property int $company_id
 * @property int $product_id
 * @property int $project_id
 * @property int $analytic_account_id
 * @property string $name
 * @property string $payment_mode
 * @property float $total_amount
 * @property \Carbon\Carbon $date
 * @property string $expense_state
 * @property string $sync_state
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Expense extends Model
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
        'company_id',
        'product_id',
        'project_id',
        'analytic_account_id',
        'name',
        'payment_mode',
        'total_amount',
        'date',
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
            'employee_id' => 'integer',
            'company_id' => 'integer',
            'product_id' => 'integer',
            'project_id' => 'integer',
            'analytic_account_id' => 'integer',
            'total_amount' => 'decimal:2',
            'date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\HR\Employee\Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Core\Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\SupplyChain\Inventory\Product::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Services\Project\Project::class);
    }

    public function analyticAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Finance\Accounting\AnalyticAccount::class);
    }
}
