<?php

namespace App\Models\Odoo;

use Carbon\Carbon;
use Database\Factories\OdooApiLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $method
 * @property string $endpoint
 * @property string $request_payload
 * @property int $response_status
 * @property string $response_body
 * @property int $duration_ms
 * @property int $odoo_company_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OdooApiLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'method',
        'endpoint',
        'request_payload',
        'response_status',
        'response_body',
        'duration_ms',
        'odoo_company_id',
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
            'request_payload' => 'array',
            'odoo_company_id' => 'integer',
        ];
    }

    public function odooCompany(): BelongsTo
    {
        return $this->belongsTo(OdooCompany::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): OdooApiLogFactory
    {
        return OdooApiLogFactory::new();
    }
}
