<?php

namespace App\Models\Odoo\Core;

use Carbon\Carbon;
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
 * @property int $company_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ApiLog extends Model
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
        'company_id',
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
            'company_id' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
