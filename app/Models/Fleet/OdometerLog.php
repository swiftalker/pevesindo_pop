<?php

namespace App\Models\Fleet;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $odoo_id
 * @property int $odoo_vehicle_id
 * @property int $driver_id
 * @property Carbon $date
 * @property float $value
 * @property string $unit
 * @property string $sync_state
 * @property Carbon $created_at
 * @property Carbon $updated_at
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
        'odoo_vehicle_id',
        'driver_id',
        'date',
        'value',
        'unit',
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
            'odoo_vehicle_id' => 'integer',
            'driver_id' => 'integer',
            'date' => 'date',
            'value' => 'decimal:2',
        ];
    }

    public function odooVehicle(): BelongsTo
    {
        return $this->belongsTo(OdooVehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
