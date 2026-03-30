<?php

namespace App\Models\Project;

use App\Models\OdooCompany;
use App\Models\OdooPartner;
use App\Models\SaleOrder;
use Carbon\Carbon;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $sale_order_id
 * @property int $odoo_id
 * @property string $name
 * @property int $odoo_company_id
 * @property int $odoo_partner_id
 * @property Carbon $date_start
 * @property Carbon $date_end
 * @property string $project_state
 * @property string $sync_state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 */
class Project extends Model
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
        'odoo_id',
        'name',
        'odoo_company_id',
        'odoo_partner_id',
        'date_start',
        'date_end',
        'project_state',
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
            'odoo_company_id' => 'integer',
            'odoo_partner_id' => 'integer',
            'date_start' => 'date',
            'date_end' => 'date',
        ];
    }

    public function saleOrder(): BelongsTo
    {
        return $this->belongsTo(SaleOrder::class);
    }

    public function odooCompany(): BelongsTo
    {
        return $this->belongsTo(OdooCompany::class);
    }

    public function odooPartner(): BelongsTo
    {
        return $this->belongsTo(OdooPartner::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }
}
