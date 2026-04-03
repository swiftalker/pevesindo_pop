<?php

namespace App\Models\Odoo\Sales\Crm;

use App\Models\Odoo\Core\Company;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $company_id
 * @property int $team_id
 * @property string $name
 * @property float $expected_revenue
 * @property float $probability
 * @property string $stage
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Lead extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'odoo_id',
        'company_id',
        'team_id',
        'name',
        'expected_revenue',
        'probability',
        'stage',
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
            'company_id' => 'integer',
            'team_id' => 'integer',
            'expected_revenue' => 'decimal:2',
            'probability' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Odoo\Sales\Crm\Team::class);
    }
}
