<?php

namespace App\Models\Odoo\SupplyChain\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $company_id
 * @property int $partner_id
 * @property string $name
 * @property string $picking_type_code
 * @property string $state
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Picking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'odoo_id',
        'company_id',
        'partner_id',
        'name',
        'picking_type_code',
        'state',
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
            'company_id' => 'integer',
            'partner_id' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Core\Company::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Core\Partner::class);
    }
}
