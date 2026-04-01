<?php

namespace App\Models\Odoo\Finance;

use App\Models\Odoo\Core\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $company_id
 * @property string $name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AnalyticPlan extends Model
{
    protected $fillable = [
        'odoo_id',
        'company_id',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(AnalyticAccount::class, 'plan_id');
    }
}
