<?php

namespace App\Models\Core;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $messageable_id
 * @property string $messageable_type
 * @property string $body
 * @property bool $is_internal
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Message extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'messageable_id',
        'messageable_type',
        'body',
        'is_internal',
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
            'messageable_id' => 'integer',
            'is_internal' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messageable(): BelongsTo
    {
        return $this->belongsTo(Messageable::class);
    }
}
