<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Place extends Model
{
    use HasFactory, SoftDeletes, BelongsToCity;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const SOURCE_ADMIN = 'admin';
    public const SOURCE_OPENDATA = 'opendata';
    public const SOURCE_CONTRIBUTION = 'contribution';

    protected $fillable = [
        'city_id',
        'slug',
        'name',
        'type',
        'description',
        'story_id',
        'latitude',
        'longitude',
        'address',
        'neighborhood',
        'opening_hours',
        'contact',
        'tags',
        'cover_photo_id',
        'source',
        'status',
        'is_sponsored',
        'sponsored_label',
        'sponsored_until',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'opening_hours' => 'array',
            'contact' => 'array',
            'tags' => 'array',
            'is_sponsored' => 'boolean',
            'sponsored_until' => 'datetime',
        ];
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function coverPhoto(): BelongsTo
    {
        return $this->belongsTo(Photo::class, 'cover_photo_id');
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'uploadable');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
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
