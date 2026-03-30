<?php

namespace App\Models\Project;

use App\Models\Odoo\OdooProduct;
use Carbon\Carbon;
use Database\Factories\RabLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $rab_id
 * @property int $odoo_product_id
 * @property string $description
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
        'odoo_product_id',
        'description',
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
            'odoo_product_id' => 'integer',
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function rab(): BelongsTo
    {
        return $this->belongsTo(Rab::class);
    }

    public function odooProduct(): BelongsTo
    {
        return $this->belongsTo(OdooProduct::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): RabLineFactory
    {
        return RabLineFactory::new();
    }
}
