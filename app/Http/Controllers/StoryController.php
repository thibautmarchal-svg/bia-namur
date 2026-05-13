<?php

namespace App\Http\Controllers;

use App\Http\Resources\StoryResource;
use App\Models\City;
use App\Models\Story;
use App\Support\Seo\SeoBuilder;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use Inertia\Response;

class StoryController extends Controller
{
    public function index(): Response
    {
        View::share('seo', SeoBuilder::forStoriesIndex());

        $namur = City::where('slug', 'namur')->firstOrFail();

        $stories = Story::query()
            ->forCity($namur)
            ->published()
            ->latest('updated_at')
            ->get();

        return Inertia::render('Stories/Index', [
            'stories' => $stories->map(fn ($s) => [
                'slug' => $s->slug,
                'title' => $s->title,
                'excerpt' => $s->excerpt,
                'type' => $s->type,
                'reading_minutes' => max(1, (int) ceil(str_word_count(strip_tags($s->content ?? '')) / 220)),
            ])->values(),
        ]);
    }

    public function show(string $slug): Response
    {
        $namur = City::where('slug', 'namur')->firstOrFail();

        $story = Story::query()
            ->forCity($namur)
            ->where('slug', $slug)
            ->where('status', Story::STATUS_PUBLISHED)
            ->with(['place:id,slug,name,type'])
            ->firstOrFail();

        View::share('seo', SeoBuilder::forStory($story));

        return Inertia::render('Stories/Show', [
            'story' => StoryResource::make($story),
        ]);
    }
}
