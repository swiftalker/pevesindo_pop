<?php

namespace App\Models\Odoo;

use Carbon\Carbon;
use Database\Factories\OdooWarehouseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $odoo_company_id
 * @property string $name
 * @property string $code
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OdooWarehouse extends Model
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
        'code',
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
        ];
    }

    public function odooCompany(): BelongsTo
    {
        return $this->belongsTo(OdooCompany::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): OdooWarehouseFactory
    {
        return OdooWarehouseFactory::new();
    }
}
