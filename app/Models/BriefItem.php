<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BriefItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'brief_id',
        'event_id',
        'place_id',
        'position',
        'ai_text',
        'edited_text',
        'reasoning',
    ];

    protected function casts(): array
    {
        return [
            'reasoning' => 'array',
        ];
    }

    public function brief(): BelongsTo
    {
        return $this->belongsTo(Brief::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    /** Texte affiché : version éditée par l'admin si présente, sinon texte IA. */
    public function getDisplayTextAttribute(): ?string
    {
        return $this->edited_text ?: $this->ai_text;
    }
}
