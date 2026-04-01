<?php

namespace App\Models\Odoo\Finance;

use App\Models\Odoo\Core\Company;
use App\Models\Odoo\Core\Partner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $company_id
 * @property int $partner_id
 * @property string $bank_name
 * @property string $acc_number
 * @property string $acc_holder_name
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class BankAccount extends Model
{
    protected $fillable = [
        'odoo_id',
        'company_id',
        'partner_id',
        'bank_name',
        'acc_number',
        'acc_holder_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'partner_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
