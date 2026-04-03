<?php

namespace App\Models\Odoo\Sales\Order;

use App\Models\Odoo\Core\Company;
use App\Models\Odoo\Core\Partner;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property string $name
 * @property int $partner_id
 * @property int $company_id
 * @property int $team_id
 * @property float $amount_total
 * @property string $state
 * @property string $note
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SaleOrder extends Model
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
        'partner_id',
        'company_id',
        'team_id',
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
            'team_id' => 'integer',
            'amount_total' => 'decimal:2',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Odoo\Sales\Crm\Team::class);
    }
}
