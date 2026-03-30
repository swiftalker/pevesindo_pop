<?php

namespace App\Models\Fleet;

use App\Models\OdooCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $odoo_company_id
 * @property string $name
 * @property string $license_plate
 * @property string $model
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OdooVehicle extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'odoo_id',
        'odoo_company_id',
        'name',
        'license_plate',
        'model',
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
            'odoo_company_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function odooCompany(): BelongsTo
    {
        return $this->belongsTo(OdooCompany::class);
    }
}
