<?php

namespace App\Models\Odoo\SupplyChain\Purchase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $partner_id
 * @property int $company_id
 * @property string $name
 * @property float $amount_total
 * @property string $state
 * @property string $note
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'odoo_id',
        'partner_id',
        'company_id',
        'name',
        'amount_total',
        'state',
        'note',
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
            'partner_id' => 'integer',
            'company_id' => 'integer',
            'amount_total' => 'decimal:2',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Core\Partner::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Core\Company::class);
    }
}
