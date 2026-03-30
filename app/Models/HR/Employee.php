<?php

namespace App\Models\HR;

use App\Models\OdooCompany;
use Carbon\Carbon;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $odoo_id
 * @property string $name
 * @property string $job_title
 * @property int $odoo_company_id
 * @property string $department
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Employee extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'odoo_id',
        'name',
        'job_title',
        'odoo_company_id',
        'department',
        'is_active',
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
            'is_active' => 'boolean',
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

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }
}
