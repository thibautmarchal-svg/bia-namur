<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brief extends Model
{
    use BelongsToCity, HasFactory, SoftDeletes;

    public const STATUS_DRAFT_AI = 'draft_ai';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'city_id',
        'week_number',
        'year',
        'slug',
        'title',
        'intro_text',
        'generated_at',
        'status',
        'reviewer_id',
        'reviewed_at',
        'published_at',
        'selected_event_ids',
        'telegram_message_id',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
            'selected_event_ids' => 'array',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(BriefItem::class)->orderBy('position');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'uploadable');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }
}
