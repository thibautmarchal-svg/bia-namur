<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MODERATOR = 'moderator';

    public const ROLE_MEMBER = 'member';

    public const TIER_FREE = 'free';

    public const TIER_PLUS = 'plus';

    public const TIER_PATRON = 'patron';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'locale',
        'preferences',
        'subscription_tier',
        'subscription_started_at',
        'subscription_renews_at',
        'stripe_customer_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'stripe_customer_id',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
            'subscription_started_at' => 'datetime',
            'subscription_renews_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isModerator(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MODERATOR], true);
    }

    /** Filament admin panel access — réservé aux admins et modérateurs. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isModerator();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription_tier !== self::TIER_FREE
            && $this->subscription_renews_at?->isFuture() === true;
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function uploadedPhotos(): HasMany
    {
        return $this->hasMany(Photo::class, 'uploaded_by');
    }
}
