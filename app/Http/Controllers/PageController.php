<?php

namespace App\Http\Controllers;

use App\Models\Story;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function wallon(): Response
    {
        $words = config('bia-wallon.words', []);
        $families = config('bia-wallon.families', []);
        $externalLinks = config('bia-wallon.external_links', []);

        // Stories type=wallon liees, si on en a
        $stories = Story::query()
            ->where('type', Story::TYPE_WALLON)
            ->where('status', Story::STATUS_PUBLISHED)
            ->latest('updated_at')
            ->limit(3)
            ->get(['slug', 'title', 'excerpt'])
            ->values()
            ->all();

        return Inertia::render('Wallon', [
            'words' => $words,
            'families' => $families,
            'externalLinks' => $externalLinks,
            'stories' => $stories,
        ]);
    }

    public function about(): Response
    {
        return Inertia::render('About');
    }

    public function legalMentions(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->withNoIndex(Inertia::render('Legal/Mentions', [
            'updatedAt' => '2026-05-07',
        ]));
    }

    public function legalTerms(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->withNoIndex(Inertia::render('Legal/Terms', [
            'updatedAt' => '2026-05-07',
        ]));
    }

    public function legalPrivacy(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->withNoIndex(Inertia::render('Legal/Privacy', [
            'updatedAt' => '2026-05-07',
        ]));
    }

    /**
     * Pages legales : header X-Robots-Tag pour ne pas etre indexees par les
     * moteurs (le contenu legal n'a pas vocation a etre dans les resultats
     * de recherche, ca pollue les SERP utiles).
     */
    protected function withNoIndex(Response $response): \Symfony\Component\HttpFoundation\Response
    {
        return $response->toResponse(request())
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
