<?php

namespace App\Models\Sales;

use App\Models\Invoice;
use App\Models\OdooJournal;
use Carbon\Carbon;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $invoice_id
 * @property int $odoo_id
 * @property int $odoo_journal_id
 * @property float $amount
 * @property Carbon $payment_date
 * @property string $memo
 * @property string $payment_state
 * @property string $sync_state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 */
class Payment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'center_app_ref',
        'invoice_id',
        'odoo_id',
        'odoo_journal_id',
        'amount',
        'payment_date',
        'memo',
        'payment_state',
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
            'invoice_id' => 'integer',
            'odoo_journal_id' => 'integer',
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function odooJournal(): BelongsTo
    {
        return $this->belongsTo(OdooJournal::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }
}
