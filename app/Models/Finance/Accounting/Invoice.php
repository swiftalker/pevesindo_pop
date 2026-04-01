<?php

namespace App\Models\Finance\Accounting;

use App\Models\Odoo\Finance\Accounting\AnalyticAccount;
use App\Models\Sales\Order\SaleOrder;
use App\Models\Services\Project\Project;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $pop_app_ref
 * @property int $order_id
 * @property int $project_id
 * @property int $odoo_id
 * @property string $name
 * @property string $invoice_type
 * @property int $team_id
 * @property int $analytic_account_id
 * @property float $amount_total
 * @property string $invoice_state
 * @property string $note
 * @property string $sync_state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 */
class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pop_app_ref',
        'order_id',
        'project_id',
        'odoo_id',
        'name',
        'invoice_type',
        'team_id',
        'analytic_account_id',
        'amount_total',
        'invoice_state',
        'note',
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
            'project_id' => 'integer',
            'team_id' => 'integer',
            'analytic_account_id' => 'integer',
            'amount_total' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SaleOrder::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Odoo\Sales\Crm\Team::class);
    }

    public function analyticAccount(): BelongsTo
    {
        return $this->belongsTo(AnalyticAccount::class);
    }
}
