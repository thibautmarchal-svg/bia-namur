<?php

namespace App\Providers;

use App\Models\Brief;
use App\Observers\BriefObserver;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Pas de wrapping {data: ...} sur les Resources : on utilise Inertia,
        // et l'eveloppe est genante cote Vue. On expose le payload plat.
        JsonResource::withoutWrapping();

        // Push notifications : declenche le job quand un brief passe a publie.
        Brief::observe(BriefObserver::class);
    }
}
