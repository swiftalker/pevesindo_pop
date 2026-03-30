<?php

namespace App\Models\Sales;

use App\Models\OdooCompany;
use App\Models\OdooPricelist;
use Carbon\Carbon;
use Database\Factories\SalesIntentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $user_id
 * @property int $odoo_company_id
 * @property string $sales_type
 * @property string $customer_name
 * @property string $customer_phone
 * @property string $project_address
 * @property int $odoo_pricelist_id
 * @property string $note
 * @property string $state
 * @property string $sync_state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 */
class SalesIntent extends Model
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
        'odoo_company_id',
        'sales_type',
        'customer_name',
        'customer_phone',
        'project_address',
        'odoo_pricelist_id',
        'note',
        'state',
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
            'odoo_company_id' => 'integer',
            'odoo_pricelist_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function odooCompany(): BelongsTo
    {
        return $this->belongsTo(OdooCompany::class);
    }

    public function odooPricelist(): BelongsTo
    {
        return $this->belongsTo(OdooPricelist::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): SalesIntentFactory
    {
        return SalesIntentFactory::new();
    }
}
