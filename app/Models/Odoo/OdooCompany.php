<?php

namespace App\Models\Odoo;

use Carbon\Carbon;
use Database\Factories\OdooCompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $odoo_id
 * @property string $name
 * @property string $code
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OdooCompany extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'odoo_id',
        'name',
        'code',
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
            'is_active' => 'boolean',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): OdooCompanyFactory
    {
        return OdooCompanyFactory::new();
    }
}
