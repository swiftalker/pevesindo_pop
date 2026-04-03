<?php

namespace App\Models\Odoo\SupplyChain\Inventory;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $picking_id
 * @property int $product_id
 * @property float $product_uom_qty
 * @property string $state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class StockMove extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'odoo_id',
        'picking_id',
        'product_id',
        'product_uom_qty',
        'state',
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
            'picking_id' => 'integer',
            'product_id' => 'integer',
            'product_uom_qty' => 'decimal:2',
        ];
    }

    public function picking(): BelongsTo
    {
        return $this->belongsTo(Picking::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
