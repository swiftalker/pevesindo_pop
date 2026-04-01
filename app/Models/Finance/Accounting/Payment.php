<?php

namespace App\Models\Finance\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $invoice_id
 * @property int $odoo_id
 * @property int $journal_id
 * @property float $amount
 * @property \Carbon\Carbon $payment_date
 * @property string $payment_state
 * @property string $sync_state
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
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
        'journal_id',
        'amount',
        'payment_date',
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
            'journal_id' => 'integer',
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Finance\Accounting\Invoice::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Finance\Accounting\Journal::class);
    }
}
