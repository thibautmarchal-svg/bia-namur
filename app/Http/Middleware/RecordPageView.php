<?php

namespace App\Http\Middleware;

use App\Models\Brief;
use App\Models\PageView;
use App\Models\Place;
use App\Models\Story;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tracking interne anonymise pour stats editoriales.
 *
 * Pourquoi pas un service tiers ici : on veut savoir exactement quels
 * lieux/stories cartonnent pour decider quoi mettre en avant. C'est plus
 * editorial qu'analytique au sens marketing.
 *
 * Anti-PII : on ne stocke jamais l'IP en clair, juste un hash SHA-256
 * tronque. La fenetre de dedup est 24h : un meme hash IP qui revient
 * sur la meme page dans la journee n'incremente pas le compteur.
 *
 * Anti-bot : on filtre via le user-agent. Les bots indexeurs (Google,
 * Bing) ne polluent pas les stats editoriales.
 */
class RecordPageView
{
    /** Patterns user-agent qui sont des bots — heuristique simple. */
    private const BOT_PATTERNS = [
        'bot', 'crawler', 'spider', 'curl', 'wget', 'python-requests',
        'go-http', 'java/', 'lighthouse', 'headlesschrome', 'pingdom',
        'uptime', 'monitor', 'preview', 'embed', 'whatsapp', 'telegram',
        'facebookexternalhit', 'slackbot',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // On ne tracke que les GET 200 successful
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return $response;
        }

        try {
            $this->record($request);
        } catch (\Throwable $e) {
            // Le tracking ne doit jamais casser une page — log et on continue.
            Log::channel('moderation')->warning('page_view.record_failed', [
                'path' => $request->path(),
                'error' => $e->getMessage(),
            ]);
        }

        return $response;
    }

    private function record(Request $request): void
    {
        $path = '/'.ltrim($request->path(), '/');
        $route = $request->route();

        $viewable = $this->resolveViewable($route?->getName(), $route?->parameter('slug'));
        if ($viewable === null) {
            return;
        }

        $userAgent = (string) $request->userAgent();
        $isBot = $this->isBot($userAgent);

        $ip = $request->ip() ?? 'unknown';
        // Salt environnement → un attaquant qui aurait acces a la BDD ne peut
        // pas faire de reverse lookup brute force sur l'espace IP.
        $salt = config('app.key');
        $ipHash = hash('sha256', $ip.'|'.$salt);

        // Dedup 24h : si le meme hash IP a vu cette ressource dans la
        // derniere heure, on n'enregistre pas une 2e fois.
        $alreadySeen = PageView::query()
            ->where('viewable_type', $viewable['type'])
            ->where('viewable_id', $viewable['id'])
            ->where('ip_hash', $ipHash)
            ->where('viewed_at', '>=', now()->subHours(24))
            ->exists();
        if ($alreadySeen) {
            return;
        }

        PageView::create([
            'viewable_type' => $viewable['type'],
            'viewable_id' => $viewable['id'],
            'slug' => $viewable['slug'],
            'ip_hash' => $ipHash,
            'referrer_host' => $this->extractReferrerHost($request),
            'is_bot' => $isBot,
            'viewed_at' => now(),
        ]);
    }

    /** @return array{type:class-string, id:int, slug:string}|null */
    private function resolveViewable(?string $routeName, mixed $slug): ?array
    {
        if (! is_string($slug)) {
            return null;
        }

        $modelClass = match ($routeName) {
            'places.show' => Place::class,
            'stories.show' => Story::class,
            'briefs.show' => Brief::class,
            default => null,
        };
        if ($modelClass === null) {
            return null;
        }

        $record = $modelClass::query()->where('slug', $slug)->first(['id', 'slug']);
        if (! $record) {
            return null;
        }

        return [
            'type' => $modelClass,
            'id' => $record->id,
            'slug' => $record->slug,
        ];
    }

    private function isBot(string $userAgent): bool
    {
        if ($userAgent === '') {
            return true;
        }

        $needle = strtolower($userAgent);
        foreach (self::BOT_PATTERNS as $pattern) {
            if (str_contains($needle, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function extractReferrerHost(Request $request): ?string
    {
        $referrer = $request->header('referer');
        if (! is_string($referrer) || $referrer === '') {
            return null;
        }

        $host = parse_url($referrer, PHP_URL_HOST);
        if (! is_string($host)) {
            return null;
        }

        // Skip self-referrals (navigation interne) — pas interessant
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        if ($host === $appHost) {
            return null;
        }

        return mb_substr($host, 0, 191);
    }
}
