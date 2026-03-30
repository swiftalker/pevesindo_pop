<?php

namespace App\Models\Odoo;

use App\Models\OdooCompany;
use Carbon\Carbon;
use Database\Factories\OdooPartnerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $odoo_company_id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string $mobile
 * @property string $street
 * @property string $city
 * @property string $state_name
 * @property string $zip
 * @property string $partner_type
 * @property bool $is_company
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OdooPartner extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'odoo_id',
        'odoo_company_id',
        'name',
        'email',
        'phone',
        'mobile',
        'street',
        'city',
        'state_name',
        'zip',
        'partner_type',
        'is_company',
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
            'odoo_company_id' => 'integer',
            'is_company' => 'boolean',
        ];
    }

    public function odooCompany(): BelongsTo
    {
        return $this->belongsTo(OdooCompany::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): OdooPartnerFactory
    {
        return OdooPartnerFactory::new();
    }
}
