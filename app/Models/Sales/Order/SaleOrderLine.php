<?php

namespace App\Models\Sales\Order;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $order_id
 * @property int $odoo_id
 * @property int $sequence
 * @property string $display_type
 * @property int $product_id
 * @property int $analytic_account_id
 * @property string $name
 * @property float $product_uom_qty
 * @property float $price_unit
 * @property float $price_subtotal
 * @property float $discount
 * @property float $tax_amount
 * @property string $sync_state
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class SaleOrderLine extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'center_app_ref',
        'order_id',
        'odoo_id',
        'sequence',
        'display_type',
        'product_id',
        'analytic_account_id',
        'name',
        'product_uom_qty',
        'price_unit',
        'price_subtotal',
        'discount',
        'tax_amount',
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
            'product_id' => 'integer',
            'analytic_account_id' => 'integer',
            'product_uom_qty' => 'decimal:2',
            'price_unit' => 'decimal:2',
            'price_subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Sales\Order\SaleOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\SupplyChain\Inventory\Product::class);
    }

    public function analyticAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Finance\Accounting\AnalyticAccount::class);
    }
}
