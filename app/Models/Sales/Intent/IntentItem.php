<?php

namespace App\Models\Sales\Intent;

use App\Models\Odoo\SupplyChain\Inventory\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $intent_id
 * @property int $odoo_id
 * @property int $product_id
 * @property string $name
 * @property float $quantity
 * @property float $price_unit
 * @property float $subtotal
 * @property string $sync_state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class IntentItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'intent_id',
        'odoo_id',
        'product_id',
        'name',
        'quantity',
        'price_unit',
        'subtotal',
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
            'product_id' => 'integer',
            'quantity' => 'decimal:2',
            'price_unit' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function intent(): BelongsTo
    {
        return $this->belongsTo(Intent::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
