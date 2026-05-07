<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Story extends Model
{
    use BelongsToCity, HasFactory, SoftDeletes;

    public const TYPE_PLACE = 'place';

    public const TYPE_TRADITION = 'tradition';

    public const TYPE_WALLON = 'wallon';

    public const TYPE_PATRIMOINE = 'patrimoine';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'city_id',
        'place_id',
        'type',
        'title',
        'slug',
        'content',
        'excerpt',
        'cover_photo_id',
        'ai_generated',
        'ai_model',
        'ai_prompt_version',
        'reviewed_by',
        'reviewed_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'ai_generated' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function coverPhoto(): BelongsTo
    {
        return $this->belongsTo(Photo::class, 'cover_photo_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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
