<?php

namespace App\Models\Odoo\Finance\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $invoice_id
 * @property int $product_id
 * @property string $name
 * @property float $quantity
 * @property float $price_unit
 * @property float $price_subtotal
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class InvoiceLine extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'odoo_id',
        'invoice_id',
        'product_id',
        'name',
        'quantity',
        'price_unit',
        'price_subtotal',
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
            'invoice_id' => 'integer',
            'product_id' => 'integer',
            'quantity' => 'decimal:2',
            'price_unit' => 'decimal:2',
            'price_subtotal' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Finance\Accounting\Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\SupplyChain\Inventory\Product::class);
    }
}
