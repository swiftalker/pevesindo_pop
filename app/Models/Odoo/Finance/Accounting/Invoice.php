<?php

namespace App\Models\Odoo\Finance\Accounting;

use App\Models\Odoo\Core\Company;
use App\Models\Odoo\Core\Partner;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property string $name
 * @property int $company_id
 * @property int $partner_id
 * @property int $team_id
 * @property int $analytic_account_id
 * @property string $move_type
 * @property float $amount_total
 * @property string $state
 * @property string $payment_state
 * @property string $note
 * @property string $x_studio_many2many_field_4jv_1jeesssc3
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Invoice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'odoo_id',
        'name',
        'company_id',
        'partner_id',
        'team_id',
        'analytic_account_id',
        'move_type',
        'amount_total',
        'state',
        'payment_state',
        'note',
        'x_studio_many2many_field_4jv_1jeesssc3',
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
            'team_id' => 'integer',
            'analytic_account_id' => 'integer',
            'amount_total' => 'decimal:2',
            'x_studio_many2many_field_4jv_1jeesssc3' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
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
