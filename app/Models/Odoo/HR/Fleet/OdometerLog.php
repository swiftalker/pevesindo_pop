<?php

namespace App\Models\Odoo\HR\Fleet;

use App\Models\Odoo\SupplyChain\Fleet\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $vehicle_id
 * @property Carbon $date
 * @property float $value
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
        'odoo_id',
        'vehicle_id',
        'date',
        'value',
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
            'date' => 'date',
            'value' => 'decimal:2',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
