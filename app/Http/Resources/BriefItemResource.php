<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BriefItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'text' => $this->edited_text ?: $this->ai_text,
            'edited' => ! empty($this->edited_text),
            'venue' => data_get($this->reasoning, 'venue'),
            'when_text' => data_get($this->reasoning, 'when_text'),
            'place' => $this->whenLoaded('place', fn () => $this->place ? [
                'slug' => $this->place->slug,
                'name' => $this->place->name,
            ] : null),
            'event' => $this->whenLoaded('event', fn () => $this->event ? [
                'id' => $this->event->id,
                'title' => $this->event->title,
                'starts_at' => $this->event->starts_at?->toIso8601String(),
            ] : null),
        ];
    }
}
