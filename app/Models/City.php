<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'latitude',
        'longitude',
        'bounding_box',
        'primary_color',
        'founder_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'bounding_box' => 'array',
        ];
    }

    public function founderAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'founder_admin_id');
    }

    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function briefs(): HasMany
    {
        return $this->hasMany(Brief::class);
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
