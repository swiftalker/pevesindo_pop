<?php

namespace App\Models\Project;

use App\Models\Project;
use App\Models\SalesIntent;
use Carbon\Carbon;
use Database\Factories\RabFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $project_id
 * @property int $sales_intent_id
 * @property int $rab_template_id
 * @property float $total
 * @property int $project_duration_days
 * @property int $technician_needed
 * @property string $rab_state
 * @property Carbon $submitted_at
 * @property Carbon $approved_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Rab extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'center_app_ref',
        'project_id',
        'sales_intent_id',
        'rab_template_id',
        'total',
        'project_duration_days',
        'technician_needed',
        'rab_state',
        'submitted_at',
        'approved_at',
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
            'project_id' => 'integer',
            'sales_intent_id' => 'integer',
            'rab_template_id' => 'integer',
            'total' => 'decimal:2',
            'submitted_at' => 'timestamp',
            'approved_at' => 'timestamp',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function salesIntent(): BelongsTo
    {
        return $this->belongsTo(SalesIntent::class);
    }

    public function rabTemplate(): BelongsTo
    {
        return $this->belongsTo(RabTemplate::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): RabFactory
    {
        return RabFactory::new();
    }
}
