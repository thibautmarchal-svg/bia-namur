<?php

namespace App\Http\Controllers;

use App\Http\Resources\BriefResource;
use App\Http\Resources\PlaceResource;
use App\Models\Brief;
use App\Models\City;
use App\Models\Place;
use App\Models\Story;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $namur = City::where('slug', 'namur')->firstOrFail();

        // Le brief le plus récent (en local : on tolère draft_ai pour valider sans publier).
        $brief = Brief::query()
            ->where('city_id', $namur->id)
            ->whereIn(
                'status',
                app()->environment('local')
                    ? [Brief::STATUS_DRAFT_AI, Brief::STATUS_PENDING_REVIEW, Brief::STATUS_PUBLISHED]
                    : [Brief::STATUS_PUBLISHED]
            )
            ->orderByDesc('year')
            ->orderByDesc('week_number')
            ->with(['items' => fn ($q) => $q->orderBy('position'), 'items.event:id,title,starts_at', 'items.place:id,slug,name'])
            ->first();

        $highlightPlaces = Place::query()
            ->forCity($namur)
            ->published()
            ->latest('updated_at')
            ->limit(3)
            ->get();

        $latestStories = Story::query()
            ->forCity($namur)
            ->published()
            ->latest('updated_at')
            ->limit(2)
            ->get();

        return Inertia::render('Home', [
            'brief' => $brief ? BriefResource::make($brief) : null,
            'highlightPlaces' => PlaceResource::collection($highlightPlaces),
            'latestStories' => $latestStories->map(fn ($s) => [
                'slug' => $s->slug,
                'title' => $s->title,
                'excerpt' => $s->excerpt,
                'type' => $s->type,
            ])->values(),
        ]);
    }
}
