<?php

namespace App\Http\Resources;

use App\Support\JsonLdBuilder;
use App\Support\PhotoResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BriefResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Photo : override perso public/images/briefs/{slug}.{ext} possible,
        // sinon photo par défaut (confluent en S1, saisonniere en S2).
        $cover = PhotoResolver::for(PhotoResolver::TYPE_BRIEF, $this->slug, $this->title);
        if ($cover === null) {
            $cover = PhotoResolver::for(PhotoResolver::TYPE_BRIEF, 'default', $this->title);
        }

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'intro_text' => $this->intro_text,
            'year' => $this->year,
            'week_number' => $this->week_number,
            'status' => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),
            'cover_photo' => $cover,
            'items' => BriefItemResource::collection($this->whenLoaded('items')),
            'jsonld' => $this->when(
                $request->routeIs('briefs.show'),
                fn () => JsonLdBuilder::forBrief($this->resource),
            ),
        ];
    }
}
