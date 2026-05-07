<?php

namespace App\Http\Resources;

use App\Support\PhotoResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'type' => $this->type,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'cover_photo' => PhotoResolver::for(PhotoResolver::TYPE_STORY, $this->slug, $this->title),
            'place' => $this->whenLoaded('place', fn () => $this->place ? [
                'slug' => $this->place->slug,
                'name' => $this->place->name,
                'type' => $this->place->type,
            ] : null),
            'ai_generated' => (bool) $this->ai_generated,
            'reading_minutes' => max(1, (int) ceil(str_word_count(strip_tags($this->content ?? '')) / 220)),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
