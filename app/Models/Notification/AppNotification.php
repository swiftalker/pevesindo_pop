<?php

namespace App\Models\Notification;

use App\Models\OdooCompany;
use Carbon\Carbon;
use Database\Factories\AppNotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $odoo_company_id
 * @property string $notification_type
 * @property string $title
 * @property string $body
 * @property string $data
 * @property string $channel
 * @property Carbon $read_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AppNotification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'odoo_company_id',
        'notification_type',
        'title',
        'body',
        'data',
        'channel',
        'read_at',
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
            'data' => 'array',
            'read_at' => 'timestamp',
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
    protected static function newFactory(): AppNotificationFactory
    {
        return AppNotificationFactory::new();
    }
}
