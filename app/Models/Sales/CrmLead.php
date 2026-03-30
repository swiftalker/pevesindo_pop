<?php

namespace App\Models\Sales;

use App\Models\SalesIntent;
use Carbon\Carbon;
use Database\Factories\CrmLeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $sales_intent_id
 * @property int $odoo_id
 * @property string $name
 * @property float $expected_revenue
 * @property float $probability
 * @property string $stage
 * @property string $sync_state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 */
class CrmLead extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'center_app_ref',
        'sales_intent_id',
        'odoo_id',
        'name',
        'expected_revenue',
        'probability',
        'stage',
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
            'sales_intent_id' => 'integer',
            'expected_revenue' => 'decimal:2',
            'probability' => 'decimal:2',
        ];
    }

    public function salesIntent(): BelongsTo
    {
        return $this->belongsTo(SalesIntent::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CrmLeadFactory
    {
        return CrmLeadFactory::new();
    }
}
