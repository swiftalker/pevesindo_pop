<?php

namespace App\Models\Odoo;

use Carbon\Carbon;
use Database\Factories\OdooProductVariantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $odoo_product_id
 * @property string $name
 * @property string $default_code
 * @property string $barcode
 * @property float $qty_available
 * @property float $virtual_available
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OdooProductVariant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'odoo_id',
        'odoo_product_id',
        'name',
        'default_code',
        'barcode',
        'qty_available',
        'virtual_available',
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
            'odoo_product_id' => 'integer',
            'qty_available' => 'decimal:2',
            'virtual_available' => 'decimal:2',
        ];
    }

    public function odooProduct(): BelongsTo
    {
        return $this->belongsTo(OdooProduct::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): OdooProductVariantFactory
    {
        return OdooProductVariantFactory::new();
    }
}
