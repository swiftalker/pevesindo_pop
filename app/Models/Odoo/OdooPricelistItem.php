<?php

namespace App\Models\Odoo;

use Carbon\Carbon;
use Database\Factories\OdooPricelistItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $odoo_pricelist_id
 * @property int $odoo_product_id
 * @property float $min_quantity
 * @property float $fixed_price
 * @property float $percent_price
 * @property Carbon $date_start
 * @property Carbon $date_end
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OdooPricelistItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'odoo_id',
        'odoo_pricelist_id',
        'odoo_product_id',
        'min_quantity',
        'fixed_price',
        'percent_price',
        'date_start',
        'date_end',
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
            'odoo_pricelist_id' => 'integer',
            'odoo_product_id' => 'integer',
            'min_quantity' => 'decimal:2',
            'fixed_price' => 'decimal:2',
            'percent_price' => 'decimal:2',
            'date_start' => 'date',
            'date_end' => 'date',
        ];
    }

    public function odooPricelist(): BelongsTo
    {
        return $this->belongsTo(OdooPricelist::class);
    }

    public function odooProduct(): BelongsTo
    {
        return $this->belongsTo(OdooProduct::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): OdooPricelistItemFactory
    {
        return OdooPricelistItemFactory::new();
    }
}
