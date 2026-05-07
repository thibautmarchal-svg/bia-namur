<?php

namespace App\Http\Resources;

use App\Support\JsonLdBuilder;
use App\Support\PhotoResolver;
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
            'cover_photo' => $this->resource->coverPhoto
                ? PhotoResolver::fromUploadedPhoto($this->resource->coverPhoto, $this->name)
                : PhotoResolver::for(PhotoResolver::TYPE_PLACE, $this->slug, $this->name),
            'source' => $this->source,
            'story' => StoryResource::make($this->whenLoaded('story')),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'jsonld' => $this->when(
                $request->routeIs('places.show'),
                fn () => JsonLdBuilder::forPlace($this->resource),
            ),
        ];
    }
}
