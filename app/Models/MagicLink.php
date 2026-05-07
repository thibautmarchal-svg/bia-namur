<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MagicLink extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'token_hash',
        'expires_at',
        'used_at',
        'requested_ip',
        'requested_user_agent',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    protected $hidden = [
        'token_hash',
        'requested_ip',
        'requested_user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Genere un token brut (64 chars) et retourne [tokenBrut, hashSHA256].
     * Le brut va dans l'email, le hash dans la BDD.
     */
    public static function generateToken(): array
    {
        $rawToken = Str::random(64);

        return [$rawToken, hash('sha256', $rawToken)];
    }

    public function isValid(): bool
    {
        return $this->used_at === null
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    public function markUsed(): void
    {
        $this->update(['used_at' => now()]);
    }
}
