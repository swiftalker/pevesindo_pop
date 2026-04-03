<?php

namespace App\Models\Odoo\Services\Planning;

use App\Models\Odoo\Core\Company;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $company_id
 * @property Carbon $start_datetime
 * @property Carbon $end_datetime
 * @property string $state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Slot extends Model
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
        'start_datetime',
        'end_datetime',
        'state',
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
            'start_datetime' => 'timestamp',
            'end_datetime' => 'timestamp',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
