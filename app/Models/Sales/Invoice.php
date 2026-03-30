<?php

namespace App\Models\Sales;

use Carbon\Carbon;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $sale_order_id
 * @property int $odoo_id
 * @property string $name
 * @property string $invoice_type
 * @property float $amount_total
 * @property float $amount_residual
 * @property string $invoice_state
 * @property string $sync_state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 */
class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'center_app_ref',
        'sale_order_id',
        'odoo_id',
        'name',
        'invoice_type',
        'amount_total',
        'amount_residual',
        'invoice_state',
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
            'sale_order_id' => 'integer',
            'amount_total' => 'decimal:2',
            'amount_residual' => 'decimal:2',
        ];
    }

    public function saleOrder(): BelongsTo
    {
        return $this->belongsTo(SaleOrder::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): InvoiceFactory
    {
        return InvoiceFactory::new();
    }
}
