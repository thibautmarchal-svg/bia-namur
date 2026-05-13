<?php

namespace App\Http\Middleware;

use App\Support\Seo\SeoBuilder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Partage un SeoData par defaut a toutes les vues web.
 *
 * Les Controllers qui ont mieux a dire (fiche lieu, story, brief) overrident
 * via `view()->share('seo', SeoBuilder::forXxx(...))` AVANT le `Inertia::render`.
 *
 * Sans SSR Inertia, c'est la seule maniere d'avoir des meta SEO corrects
 * dans le HTML servi a Googlebot (qui n'execute pas le JS lors de la
 * premiere crawl).
 */
class ShareSeoDefaults
{
    public function handle(Request $request, Closure $next)
    {
        View::share('seo', SeoBuilder::default());

        return $next($request);
    }
}
