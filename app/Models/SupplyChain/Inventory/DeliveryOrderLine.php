<?php

namespace App\Models\SupplyChain\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $delivery_order_id
 * @property int $odoo_id
 * @property int $product_id
 * @property string $name
 * @property float $product_uom_qty
 * @property float $qty_done
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class DeliveryOrderLine extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'delivery_order_id',
        'odoo_id',
        'product_id',
        'name',
        'product_uom_qty',
        'qty_done',
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
            'delivery_order_id' => 'integer',
            'product_id' => 'integer',
            'product_uom_qty' => 'decimal:2',
            'qty_done' => 'decimal:2',
        ];
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Models\SupplyChain\Inventory\DeliveryOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\SupplyChain\Inventory\Product::class);
    }
}
