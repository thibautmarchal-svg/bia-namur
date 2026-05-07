<?php

namespace App\Http\Resources;

use App\Models\BriefItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BriefResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'intro_text' => $this->intro_text,
            'year' => $this->year,
            'week_number' => $this->week_number,
            'status' => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),
            'items' => BriefItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
