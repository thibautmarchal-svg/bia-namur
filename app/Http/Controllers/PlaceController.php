<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlaceResource;
use App\Models\City;
use App\Models\Place;
use App\Support\Seo\SeoBuilder;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use Inertia\Response;

class PlaceController extends Controller
{
    public function index(): Response
    {
        View::share('seo', SeoBuilder::forPlacesIndex());

        $namur = City::where('slug', 'namur')->firstOrFail();

        $places = Place::query()
            ->forCity($namur)
            ->published()
            ->orderBy('name')
            ->get();

        return Inertia::render('Places/Index', [
            'places' => PlaceResource::collection($places),
        ]);
    }

    public function show(string $slug): Response
    {
        $namur = City::where('slug', 'namur')->firstOrFail();

        $place = Place::query()
            ->forCity($namur)
            ->where('slug', $slug)
            ->where('status', Place::STATUS_PUBLISHED)
            ->with(['story:id,slug,title,type,excerpt,content,place_id'])
            ->firstOrFail();

        View::share('seo', SeoBuilder::forPlace($place));

        return Inertia::render('Places/Show', [
            'place' => PlaceResource::make($place),
        ]);
    }
}
