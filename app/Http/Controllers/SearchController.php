<?php

namespace App\Http\Controllers;

use App\Http\Resources\BriefResource;
use App\Http\Resources\PlaceResource;
use App\Http\Resources\StoryResource;
use App\Models\Brief;
use App\Models\City;
use App\Models\Place;
use App\Models\Story;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    private const MAX_RESULTS_PER_TYPE = 12;

    private const MIN_QUERY_LENGTH = 2;

    public function __invoke(Request $request): Response
    {
        $query = trim((string) $request->query('q', ''));

        $results = [
            'places' => [],
            'stories' => [],
            'briefs' => [],
            'total' => 0,
        ];

        if (mb_strlen($query) >= self::MIN_QUERY_LENGTH) {
            $namur = City::where('slug', 'namur')->firstOrFail();
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $query) . '%';

            $places = Place::query()
                ->forCity($namur)
                ->published()
                ->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('neighborhood', 'like', $like)
                        ->orWhere('address', 'like', $like);
                })
                ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$like])
                ->orderBy('name')
                ->limit(self::MAX_RESULTS_PER_TYPE)
                ->get();

            $stories = Story::query()
                ->forCity($namur)
                ->published()
                ->where(function ($q) use ($like) {
                    $q->where('title', 'like', $like)
                        ->orWhere('excerpt', 'like', $like)
                        ->orWhere('content', 'like', $like);
                })
                ->orderByRaw('CASE WHEN title LIKE ? THEN 0 ELSE 1 END', [$like])
                ->orderBy('title')
                ->limit(self::MAX_RESULTS_PER_TYPE)
                ->get();

            $briefs = Brief::query()
                ->forCity($namur)
                ->published()
                ->where(function ($q) use ($like) {
                    $q->where('title', 'like', $like)
                        ->orWhere('intro_text', 'like', $like);
                })
                ->orderByDesc('published_at')
                ->limit(self::MAX_RESULTS_PER_TYPE)
                ->get();

            $results = [
                'places' => PlaceResource::collection($places)->resolve(),
                'stories' => StoryResource::collection($stories)->resolve(),
                'briefs' => BriefResource::collection($briefs)->resolve(),
                'total' => $places->count() + $stories->count() + $briefs->count(),
            ];
        }

        return Inertia::render('Search/Index', [
            'query' => $query,
            'results' => $results,
            'minLength' => self::MIN_QUERY_LENGTH,
        ]);
    }
}
