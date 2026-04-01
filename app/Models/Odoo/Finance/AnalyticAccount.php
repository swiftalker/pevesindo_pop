<?php

namespace App\Models\Odoo\Finance;

use App\Models\Odoo\Core\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $plan_id
 * @property int $company_id
 * @property string $name
 * @property string $code
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AnalyticAccount extends Model
{
    protected $fillable = [
        'odoo_id',
        'plan_id',
        'company_id',
        'name',
        'code',
    ];

    protected function casts(): array
    {
        return [
            'plan_id' => 'integer',
            'company_id' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(AnalyticPlan::class, 'plan_id');
    }
}
