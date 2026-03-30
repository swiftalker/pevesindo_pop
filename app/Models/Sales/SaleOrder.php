<?php

namespace App\Models\Sales;

use App\Models\Odoo\OdooCompany;
use App\Models\Odoo\OdooPartner;
use App\Models\Odoo\OdooPricelist;
use Carbon\Carbon;
use Database\Factories\SaleOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $sales_intent_id
 * @property int $odoo_id
 * @property string $name
 * @property int $odoo_company_id
 * @property int $odoo_partner_id
 * @property int $odoo_pricelist_id
 * @property Carbon $date_order
 * @property float $amount_untaxed
 * @property float $amount_tax
 * @property float $amount_total
 * @property string $sale_state
 * @property string $payment_state
 * @property string $sync_state
 * @property string $analytic_data
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
        'center_app_ref',
        'sales_intent_id',
        'odoo_id',
        'name',
        'odoo_company_id',
        'odoo_partner_id',
        'odoo_pricelist_id',
        'date_order',
        'amount_untaxed',
        'amount_tax',
        'amount_total',
        'sale_state',
        'payment_state',
        'sync_state',
        'analytic_data',
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
            'sales_intent_id' => 'integer',
            'odoo_company_id' => 'integer',
            'odoo_partner_id' => 'integer',
            'odoo_pricelist_id' => 'integer',
            'date_order' => 'date',
            'amount_untaxed' => 'decimal:2',
            'amount_tax' => 'decimal:2',
            'amount_total' => 'decimal:2',
            'analytic_data' => 'array',
        ];
    }

    public function salesIntent(): BelongsTo
    {
        return $this->belongsTo(SalesIntent::class);
    }

    public function odooCompany(): BelongsTo
    {
        return $this->belongsTo(OdooCompany::class);
    }

    public function odooPartner(): BelongsTo
    {
        return $this->belongsTo(OdooPartner::class);
    }

    public function odooPricelist(): BelongsTo
    {
        return $this->belongsTo(OdooPricelist::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): SaleOrderFactory
    {
        return SaleOrderFactory::new();
    }
}
