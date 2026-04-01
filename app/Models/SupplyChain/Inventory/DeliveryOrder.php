<?php

namespace App\Models\SupplyChain\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $order_id
 * @property int $fsm_task_id
 * @property int $odoo_id
 * @property string $name
 * @property int $partner_id
 * @property int $warehouse_id
 * @property \Carbon\Carbon $scheduled_date
 * @property int $driver_id
 * @property int $vehicle_id
 * @property string $delivery_state
 * @property string $sync_state
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
class DeliveryOrder extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'center_app_ref',
        'order_id',
        'fsm_task_id',
        'odoo_id',
        'name',
        'partner_id',
        'warehouse_id',
        'scheduled_date',
        'driver_id',
        'vehicle_id',
        'delivery_state',
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
            'order_id' => 'integer',
            'fsm_task_id' => 'integer',
            'partner_id' => 'integer',
            'warehouse_id' => 'integer',
            'scheduled_date' => 'date',
            'driver_id' => 'integer',
            'vehicle_id' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Sales\Order\SaleOrder::class);
    }

    public function fsmTask(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Services\FieldService\Task::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Core\Partner::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\SupplyChain\Inventory\Warehouse::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\HR\Employee\Employee::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\SupplyChain\Fleet\Vehicle::class);
    }
}
