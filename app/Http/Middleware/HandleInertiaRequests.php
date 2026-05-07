<?php

namespace App\Http\Middleware;

use App\Models\Place;
use App\Models\Story;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $request->user()
                    ? [
                        'id' => $request->user()->id,
                        'name' => $request->user()->name,
                        'email' => $request->user()->email,
                        'role' => $request->user()->role,
                        'is_admin' => $request->user()->isAdmin(),
                    ]
                    : null,
                'favorites' => fn () => $request->user()
                    ? $this->favoriteIds($request->user())
                    : ['places' => [], 'stories' => []],
            ],
            'flash' => fn () => $request->session()->get('flash'),
        ];
    }

    /**
     * Liste des IDs favoris de l'utilisateur courant, groupes par type.
     * Permet au frontend (FavoriteButton) de savoir si le coeur est plein
     * sans une requête supplementaire par card.
     */
    private function favoriteIds($user): array
    {
        $rows = $user->favorites()
            ->select('favoritable_type', 'favoritable_id')
            ->get();

        return [
            'places' => $rows
                ->where('favoritable_type', Place::class)
                ->pluck('favoritable_id')
                ->values()
                ->all(),
            'stories' => $rows
                ->where('favoritable_type', Story::class)
                ->pluck('favoritable_id')
                ->values()
                ->all(),
        ];
    }
}
