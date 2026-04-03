<?php

namespace App\Models\Odoo\SupplyChain\Manufacture;

use App\Models\Odoo\Core\Company;
use App\Models\Odoo\SupplyChain\Inventory\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $odoo_id
 * @property int $product_id
 * @property int $company_id
 * @property string $code
 * @property float $product_qty
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Bom extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'odoo_id',
        'product_id',
        'company_id',
        'code',
        'product_qty',
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
            'product_id' => 'integer',
            'company_id' => 'integer',
            'product_qty' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
