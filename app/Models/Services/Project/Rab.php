<?php

namespace App\Models\Services\Project;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $center_app_ref
 * @property int $project_id
 * @property int $intent_id
 * @property int $odoo_invoice_id
 * @property int $team_id
 * @property float $total
 * @property string $rab_state
 * @property string $note
 * @property string $sync_state
 * @property string $x_studio_many2many_field_4jv_1jeesssc3
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
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
        'intent_id',
        'odoo_invoice_id',
        'team_id',
        'total',
        'rab_state',
        'note',
        'sync_state',
        'x_studio_many2many_field_4jv_1jeesssc3',
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
            'intent_id' => 'integer',
            'team_id' => 'integer',
            'total' => 'decimal:2',
            'x_studio_many2many_field_4jv_1jeesssc3' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Services\Project\Project::class);
    }

    public function intent(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Sales\Intent\Intent::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Odoo\Sales\Crm\Team::class);
    }
}
