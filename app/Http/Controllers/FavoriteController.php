<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlaceResource;
use App\Http\Resources\StoryResource;
use App\Models\Favorite;
use App\Models\Place;
use App\Models\Story;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class FavoriteController extends Controller
{
    /** Map des types autorises (alias frontend → classe Eloquent). */
    private const ALLOWED_TYPES = [
        'place' => Place::class,
        'story' => Story::class,
    ];

    public function index(): Response
    {
        $user = Auth::user();

        $favorites = $user->favorites()
            ->with('favoritable')
            ->orderByDesc('created_at')
            ->get();

        $places = $favorites
            ->where('favoritable_type', Place::class)
            ->pluck('favoritable')
            ->filter()
            ->values();

        $stories = $favorites
            ->where('favoritable_type', Story::class)
            ->pluck('favoritable')
            ->filter()
            ->values();

        return Inertia::render('Favorites/Index', [
            'places' => PlaceResource::collection($places)->resolve(),
            'stories' => StoryResource::collection($stories)->resolve(),
            'count' => $favorites->count(),
            'limit' => $user->favoritesLimit(),
            'tier' => $user->subscription_tier,
        ]);
    }

    public function toggle(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:place,story'],
            'id' => ['required', 'integer'],
        ]);

        $user = Auth::user();
        $modelClass = self::ALLOWED_TYPES[$data['type']];

        $target = $modelClass::query()
            ->where('id', $data['id'])
            ->where('status', 'published')
            ->firstOrFail();

        $existing = $user->favorites()
            ->where('favoritable_type', $modelClass)
            ->where('favoritable_id', $target->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return back(303)->with('flash', [
                'type' => 'success',
                'message' => 'Retiré de tes favoris.',
            ]);
        }

        if ($user->favorites()->count() >= $user->favoritesLimit()) {
            return back(303)->with('flash', [
                'type' => 'limit',
                'message' => 'Limite de '.$user->favoritesLimit().' favoris atteinte. Bia + débloquera plus de place.',
            ]);
        }

        Favorite::create([
            'user_id' => $user->id,
            'favoritable_type' => $modelClass,
            'favoritable_id' => $target->id,
        ]);

        return back(303)->with('flash', [
            'type' => 'success',
            'message' => 'Ajouté à tes favoris.',
        ]);
    }
}
