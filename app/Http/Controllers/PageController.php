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
}
