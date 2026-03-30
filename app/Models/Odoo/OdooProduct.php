<?php

namespace App\Models\Odoo;

use Carbon\Carbon;
use Database\Factories\OdooProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $odoo_id
 * @property string $name
 * @property string $internal_reference
 * @property string $product_type
 * @property float $list_price
 * @property string $uom_name
 * @property string $categ_name
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OdooProduct extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'odoo_id',
        'name',
        'internal_reference',
        'product_type',
        'list_price',
        'uom_name',
        'categ_name',
        'is_active',
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
            'list_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): OdooProductFactory
    {
        return OdooProductFactory::new();
    }
}
