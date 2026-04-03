<?php

namespace App\Models\Odoo\SupplyChain\Purchase;

use App\Models\Odoo\Finance\Accounting\AnalyticAccount;
use App\Models\Odoo\SupplyChain\Inventory\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $order_id
 * @property int $product_id
 * @property int $analytic_account_id
 * @property string $name
 * @property float $product_uom_qty
 * @property float $price_unit
 * @property float $price_subtotal
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OrderLine extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'odoo_id',
        'order_id',
        'product_id',
        'analytic_account_id',
        'name',
        'product_uom_qty',
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
            'order_id' => 'integer',
            'product_id' => 'integer',
            'analytic_account_id' => 'integer',
            'product_uom_qty' => 'decimal:2',
            'price_unit' => 'decimal:2',
            'price_subtotal' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function analyticAccount(): BelongsTo
    {
        return $this->belongsTo(AnalyticAccount::class);
    }
}
