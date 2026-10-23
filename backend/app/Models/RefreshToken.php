<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Long-lived token used to obtain new access tokens. Only its SHA-256 hash is stored.
 *
 * @property int $id
 * @property int $user_id
 * @property string $token_hash
 * @property CarbonInterface $expires_at
 * @property CarbonInterface|null $revoked_at
 * @property CarbonInterface $created_at
 * @property-read User $user
 */
final class RefreshToken extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'refresh_tokens';

    protected $fillable = ['user_id', 'token_hash', 'expires_at', 'revoked_at'];

    /** @var array<string, string> */
    protected $casts = [
        'user_id' => 'integer',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
