<?php

namespace App\Models\Project;

use App\Models\OdooProduct;
use Carbon\Carbon;
use Database\Factories\RabTemplateLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $rab_template_id
 * @property int $odoo_product_id
 * @property string $description
 * @property float $default_quantity
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class RabTemplateLine extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'rab_template_id',
        'odoo_product_id',
        'description',
        'default_quantity',
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
            'rab_template_id' => 'integer',
            'odoo_product_id' => 'integer',
            'default_quantity' => 'decimal:2',
        ];
    }

    public function rabTemplate(): BelongsTo
    {
        return $this->belongsTo(RabTemplate::class);
    }

    public function odooProduct(): BelongsTo
    {
        return $this->belongsTo(OdooProduct::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): RabTemplateLineFactory
    {
        return RabTemplateLineFactory::new();
    }
}
