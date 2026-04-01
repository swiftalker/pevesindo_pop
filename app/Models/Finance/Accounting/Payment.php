<?php

namespace App\Models\Finance\Accounting;

use App\Models\Odoo\Finance\Accounting\Journal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $pop_app_ref
 * @property int $invoice_id
 * @property int $odoo_id
 * @property int $journal_id
 * @property float $amount
 * @property Carbon $payment_date
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
        'pop_app_ref',
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
        return $this->belongsTo(Invoice::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
}
