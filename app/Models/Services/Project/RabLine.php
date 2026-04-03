<?php

namespace App\Models\Services\Project;

use App\Models\Odoo\SupplyChain\Inventory\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $rab_id
 * @property int $odoo_invoice_line_id
 * @property int $sequence
 * @property string $display_type
 * @property int $product_id
 * @property string $name
 * @property float $quantity
 * @property float $unit_price
 * @property float $subtotal
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class RabLine extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'rab_id',
        'odoo_invoice_line_id',
        'sequence',
        'display_type',
        'product_id',
        'name',
        'quantity',
        'unit_price',
        'subtotal',
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
            'rab_id' => 'integer',
            'product_id' => 'integer',
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function rab(): BelongsTo
    {
        return $this->belongsTo(Rab::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
