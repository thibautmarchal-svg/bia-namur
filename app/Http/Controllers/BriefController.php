<?php

namespace App\Http\Controllers;

use App\Http\Resources\BriefResource;
use App\Models\Brief;
use App\Models\City;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BriefController extends Controller
{
    public function index(): Response
    {
        $namur = City::where('slug', 'namur')->firstOrFail();

        $statusFilter = app()->environment('local')
            ? [Brief::STATUS_DRAFT_AI, Brief::STATUS_PENDING_REVIEW, Brief::STATUS_PUBLISHED]
            : [Brief::STATUS_PUBLISHED];

        $briefs = Brief::query()
            ->forCity($namur)
            ->whereIn('status', $statusFilter)
            ->orderByDesc('year')
            ->orderByDesc('week_number')
            ->get();

        return Inertia::render('Briefs/Index', [
            'briefs' => $briefs->map(fn ($b) => [
                'slug' => $b->slug,
                'title' => $b->title,
                'year' => $b->year,
                'week_number' => $b->week_number,
                'intro' => Str::limit($b->intro_text, 180),
                'status' => $b->status,
            ])->values(),
        ]);
    }

    public function show(string $slug): Response
    {
        $namur = City::where('slug', 'namur')->firstOrFail();

        $statusFilter = app()->environment('local')
            ? [Brief::STATUS_DRAFT_AI, Brief::STATUS_PENDING_REVIEW, Brief::STATUS_PUBLISHED]
            : [Brief::STATUS_PUBLISHED];

        $brief = Brief::query()
            ->forCity($namur)
            ->where('slug', $slug)
            ->whereIn('status', $statusFilter)
            ->with([
                'items' => fn ($q) => $q->orderBy('position'),
                'items.event:id,title,starts_at',
                'items.place:id,slug,name',
            ])
            ->firstOrFail();

        return Inertia::render('Briefs/Show', [
            'brief' => BriefResource::make($brief),
        ]);
    }
}
