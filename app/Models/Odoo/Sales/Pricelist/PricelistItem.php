<?php

namespace App\Models\Odoo\Sales\Pricelist;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $pricelist_id
 * @property float $min_quantity
 * @property float $fixed_price
 * @property float $percent_price
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PricelistItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'odoo_id',
        'pricelist_id',
        'min_quantity',
        'fixed_price',
        'percent_price',
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
            'pricelist_id' => 'integer',
            'min_quantity' => 'decimal:2',
            'fixed_price' => 'decimal:2',
            'percent_price' => 'decimal:2',
        ];
    }

    public function pricelist(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Sales\Pricelist\Pricelist::class);
    }
}
