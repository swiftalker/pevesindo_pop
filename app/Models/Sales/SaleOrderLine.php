<?php

namespace App\Models\Sales;

use App\Models\OdooProduct;
use App\Models\SaleOrder;
use Carbon\Carbon;
use Database\Factories\SaleOrderLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $sale_order_id
 * @property int $odoo_id
 * @property int $odoo_product_id
 * @property string $name
 * @property float $product_uom_qty
 * @property float $price_unit
 * @property float $price_subtotal
 * @property float $discount
 * @property float $tax_amount
 * @property string $sync_state
 * @property Carbon $created_at
 * @property Carbon $updated_at
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
        'sale_order_id',
        'odoo_id',
        'odoo_product_id',
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
            'sale_order_id' => 'integer',
            'odoo_product_id' => 'integer',
            'product_uom_qty' => 'decimal:2',
            'price_unit' => 'decimal:2',
            'price_subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
        ];
    }

    public function saleOrder(): BelongsTo
    {
        return $this->belongsTo(SaleOrder::class);
    }

    public function odooProduct(): BelongsTo
    {
        return $this->belongsTo(OdooProduct::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): SaleOrderLineFactory
    {
        return SaleOrderLineFactory::new();
    }
}
