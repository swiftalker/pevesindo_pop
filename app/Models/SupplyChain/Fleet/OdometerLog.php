<?php

namespace App\Models\SupplyChain\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $odoo_id
 * @property int $vehicle_id
 * @property int $driver_id
 * @property \Carbon\Carbon $date
 * @property float $value
 * @property string $sync_state
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class OdometerLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'center_app_ref',
        'odoo_id',
        'vehicle_id',
        'driver_id',
        'date',
        'value',
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
            'vehicle_id' => 'integer',
            'driver_id' => 'integer',
            'date' => 'date',
            'value' => 'decimal:2',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\SupplyChain\Fleet\Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\HR\Employee\Employee::class);
    }
}
