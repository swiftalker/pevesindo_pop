<?php

namespace App\Models\Services\Project;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $order_id
 * @property int $odoo_id
 * @property string $name
 * @property int $company_id
 * @property int $partner_id
 * @property int $analytic_account_id
 * @property \Carbon\Carbon $date_start
 * @property \Carbon\Carbon $date_end
 * @property string $project_state
 * @property string $sync_state
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
class Project extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'center_app_ref',
        'order_id',
        'odoo_id',
        'name',
        'company_id',
        'partner_id',
        'analytic_account_id',
        'date_start',
        'date_end',
        'project_state',
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
            'order_id' => 'integer',
            'company_id' => 'integer',
            'partner_id' => 'integer',
            'analytic_account_id' => 'integer',
            'date_start' => 'date',
            'date_end' => 'date',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Sales\Order\SaleOrder::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Core\Company::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Core\Partner::class);
    }

    public function analyticAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Finance\Accounting\AnalyticAccount::class);
    }
}
