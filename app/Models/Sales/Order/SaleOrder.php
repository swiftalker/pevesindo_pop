<?php

namespace App\Models\Sales\Order;

use App\Models\Odoo\Core\Company;
use App\Models\Odoo\Core\Partner;
use App\Models\Sales\Intent\Intent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $pop_app_ref
 * @property int $intent_id
 * @property int $odoo_id
 * @property string $name
 * @property int $company_id
 * @property int $partner_id
 * @property int $team_id
 * @property Carbon $date_order
 * @property float $amount_total
 * @property string $sale_state
 * @property string $payment_state
 * @property string $note
 * @property string $sync_state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 */
class SaleOrder extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pop_app_ref',
        'intent_id',
        'odoo_id',
        'name',
        'company_id',
        'partner_id',
        'team_id',
        'date_order',
        'amount_total',
        'sale_state',
        'payment_state',
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
            'intent_id' => 'integer',
            'company_id' => 'integer',
            'partner_id' => 'integer',
            'team_id' => 'integer',
            'date_order' => 'date',
            'amount_total' => 'decimal:2',
        ];
    }

    public function intent(): BelongsTo
    {
        return $this->belongsTo(Intent::class);
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
}
