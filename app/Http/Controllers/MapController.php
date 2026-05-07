<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlaceResource;
use App\Models\City;
use App\Models\Place;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    public function __invoke(): Response
    {
        $namur = City::where('slug', 'namur')->firstOrFail();

        $places = Place::query()
            ->forCity($namur)
            ->published()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        return Inertia::render('Map', [
            'city' => [
                'slug' => $namur->slug,
                'name' => $namur->name,
                'center' => [
                    'lat' => (float) $namur->latitude,
                    'lng' => (float) $namur->longitude,
                ],
                'bounding_box' => $namur->bounding_box,
            ],
            'places' => PlaceResource::collection($places),
        ]);
    }
}
