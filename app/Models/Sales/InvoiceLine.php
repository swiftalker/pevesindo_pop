<?php

namespace App\Models\Sales;

use App\Models\Invoice;
use App\Models\OdooProduct;
use Carbon\Carbon;
use Database\Factories\InvoiceLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $invoice_id
 * @property int $odoo_id
 * @property int $odoo_product_id
 * @property string $name
 * @property float $quantity
 * @property float $price_unit
 * @property float $price_subtotal
 * @property Carbon $created_at
 * @property Carbon $updated_at
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
        'center_app_ref',
        'invoice_id',
        'odoo_id',
        'odoo_product_id',
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
            'odoo_product_id' => 'integer',
            'quantity' => 'decimal:2',
            'price_unit' => 'decimal:2',
            'price_subtotal' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function odooProduct(): BelongsTo
    {
        return $this->belongsTo(OdooProduct::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): InvoiceLineFactory
    {
        return InvoiceLineFactory::new();
    }
}
