<?php

namespace App\Models\Sales\Intent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $user_id
 * @property int $company_id
 * @property string $sales_type
 * @property string $intent_state
 * @property string $pipeline_stage
 * @property string $customer_name
 * @property string $customer_phone
 * @property string $project_address
 * @property int $pricelist_id
 * @property float $expected_revenue
 * @property string $note
 * @property int $odoo_lead_id
 * @property int $odoo_order_id
 * @property string $sync_state
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
class Intent extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'center_app_ref',
        'user_id',
        'company_id',
        'sales_type',
        'intent_state',
        'pipeline_stage',
        'customer_name',
        'customer_phone',
        'project_address',
        'pricelist_id',
        'expected_revenue',
        'note',
        'odoo_lead_id',
        'odoo_order_id',
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
            'user_id' => 'integer',
            'company_id' => 'integer',
            'pricelist_id' => 'integer',
            'expected_revenue' => 'decimal:2',
            'sales_type' => \App\Enums\Sales\Intent\SalesType::class,
            'intent_state' => \App\Enums\Sales\Intent\IntentState::class,
            'pipeline_stage' => \App\Enums\Sales\Intent\PipelineStage::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Auth\User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Core\Company::class);
    }

    public function pricelist(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Sales\Pricelist\Pricelist::class);
    }

    public function intentItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Sales\Intent\IntentItem::class);
    }
}
