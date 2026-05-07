<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'address' => $this->address,
            'neighborhood' => $this->neighborhood,
            'opening_hours' => $this->opening_hours,
            'contact' => $this->contact,
            'tags' => $this->tags ?? [],
            'is_sponsored' => (bool) $this->is_sponsored,
            'sponsored_label' => $this->sponsored_label,
            'cover_photo_url' => null,    // S2 quand R2 + uploads en place
            'source' => $this->source,
            'story' => StoryResource::make($this->whenLoaded('story')),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
