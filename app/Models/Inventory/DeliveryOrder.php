<?php

namespace App\Models\Inventory;

use App\Models\Employee;
use App\Models\OdooPartner;
use App\Models\OdooWarehouse;
use App\Models\SaleOrder;
use Carbon\Carbon;
use Database\Factories\DeliveryOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $sale_order_id
 * @property int $fsm_task_id
 * @property int $odoo_id
 * @property string $name
 * @property int $odoo_partner_id
 * @property int $odoo_warehouse_id
 * @property Carbon $scheduled_date
 * @property int $driver_id
 * @property int $odoo_vehicle_id
 * @property string $delivery_state
 * @property string $sync_state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
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
        'sale_order_id',
        'fsm_task_id',
        'odoo_id',
        'name',
        'odoo_partner_id',
        'odoo_warehouse_id',
        'scheduled_date',
        'driver_id',
        'odoo_vehicle_id',
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
            'sale_order_id' => 'integer',
            'fsm_task_id' => 'integer',
            'odoo_partner_id' => 'integer',
            'odoo_warehouse_id' => 'integer',
            'scheduled_date' => 'date',
            'driver_id' => 'integer',
            'odoo_vehicle_id' => 'integer',
        ];
    }

    public function saleOrder(): BelongsTo
    {
        return $this->belongsTo(SaleOrder::class);
    }

    public function fsmTask(): BelongsTo
    {
        return $this->belongsTo(FsmTask::class);
    }

    public function odooPartner(): BelongsTo
    {
        return $this->belongsTo(OdooPartner::class);
    }

    public function odooWarehouse(): BelongsTo
    {
        return $this->belongsTo(OdooWarehouse::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function odooVehicle(): BelongsTo
    {
        return $this->belongsTo(OdooVehicle::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DeliveryOrderFactory
    {
        return DeliveryOrderFactory::new();
    }
}
