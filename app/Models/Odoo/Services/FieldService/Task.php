<?php

namespace App\Models\Odoo\Services\FieldService;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $project_id
 * @property int $company_id
 * @property int $partner_id
 * @property string $name
 * @property bool $is_fsm
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'odoo_id',
        'project_id',
        'company_id',
        'partner_id',
        'name',
        'is_fsm',
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
            'company_id' => 'integer',
            'partner_id' => 'integer',
            'is_fsm' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Services\Project\Project::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Core\Company::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Odoo\Core\Partner::class);
    }
}
