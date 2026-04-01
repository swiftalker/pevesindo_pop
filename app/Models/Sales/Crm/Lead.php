<?php

namespace App\Models\Sales\Crm;

use App\Models\Odoo\Sales\Crm\Team;
use App\Models\Sales\Intent\Intent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $pop_app_ref
 * @property int $intent_id
 * @property int $odoo_id
 * @property int $team_id
 * @property string $name
 * @property float $expected_revenue
 * @property string $stage
 * @property string $sync_state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 */
class Lead extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pop_app_ref',
        'intent_id',
        'odoo_id',
        'team_id',
        'name',
        'expected_revenue',
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
            'intent_id' => 'integer',
            'team_id' => 'integer',
            'expected_revenue' => 'decimal:2',
        ];
    }

    public function intent(): BelongsTo
    {
        return $this->belongsTo(Intent::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
