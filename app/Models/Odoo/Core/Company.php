<?php

namespace App\Models\Odoo\Core;

use App\Models\Odoo\Finance\AnalyticAccount;
use App\Models\Odoo\Finance\BankAccount;
use App\Models\Odoo\Finance\Journal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int|null $parent_id
 * @property int|null $partner_id
 * @property string $name
 * @property string $code
 * @property string $currency
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'odoo_id',
        'parent_id',
        'partner_id',
        'name',
        'code',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'parent_id' => 'integer',
            'partner_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Parent company (pusat) jika ini cabang.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Cabang-cabang dari company ini.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Linked res.partner record.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Kontak-kontak yang terkait company ini.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Partner::class, 'company_id');
    }

    /**
     * Jurnal akuntansi milik company ini.
     */
    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    /**
     * Rekening bank milik company ini.
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    /**
     * Akun analitik milik company ini.
     */
    public function analyticAccounts(): HasMany
    {
        return $this->hasMany(AnalyticAccount::class);
    }
}
