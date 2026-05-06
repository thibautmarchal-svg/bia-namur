<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory, BelongsToCity;

    public const STATUS_INGESTED = 'ingested';
    public const STATUS_NORMALIZED = 'normalized';
    public const STATUS_SELECTED = 'selected';
    public const STATUS_DROPPED = 'dropped';

    protected $fillable = [
        'city_id',
        'source',
        'external_id',
        'title',
        'description',
        'full_text',
        'starts_at',
        'ends_at',
        'recurrence',
        'place_id',
        'venue_name',
        'address',
        'category',
        'price_info',
        'url',
        'image_url',
        'raw_payload',
        'ingested_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'ingested_at' => 'datetime',
            'category' => 'array',
            'raw_payload' => 'array',
        ];
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>=', now());
    }

    public function scopeForWeek($query, int $year, int $week)
    {
        $start = now()->setISODate($year, $week)->startOfWeek();
        $end = (clone $start)->endOfWeek();

        return $query->whereBetween('starts_at', [$start, $end]);
    }
}
