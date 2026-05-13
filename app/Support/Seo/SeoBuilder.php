<?php

namespace App\Support\Seo;

use App\Models\Brief;
use App\Models\Place;
use App\Models\Story;
use App\Support\JsonLdBuilder;
use App\Support\PhotoResolver;
use Illuminate\Support\Str;

/**
 * Builder qui produit un SeoData adapte au contexte (page statique ou
 * contenu specifique). Centralise toute la logique meta de Bia Namur :
 *  - Title : "{contenu} — Bia Namur" pour la fiche, "Bia Namur — {tagline}"
 *    pour la home (pattern recommande Google : sujet d'abord, marque ensuite).
 *  - Description : extrait du contenu (excerpt, intro) ou copy editorialise.
 *  - Image OG : photo de couverture du contenu, fallback social-share par defaut.
 *  - Canonical : URL absolue via config('app.url').
 */
class SeoBuilder
{
    /**
     * Image OG par defaut (Facebook/LinkedIn/WhatsApp/Twitter) quand
     * le contenu n'a pas sa propre cover photo. 1200x630 = format Facebook
     * recommande, ratio 1.91:1. Generee depuis assets-logo/og-image.svg
     * via `node scripts/generate-og-image.mjs`.
     */
    private const DEFAULT_OG_IMAGE = '/images/og/bia-namur-default.jpg';

    /**
     * SEO de la home — vitrine principale, tagline complete.
     */
    public static function forHome(): SeoData
    {
        return new SeoData(
            title: 'Bia Namur — Le carnet vivant des Namurois',
            description: 'Chaque semaine, le brief des sorties à Namur, la carte sentimentale des bonnes adresses et les stories du patrimoine namurois. Curaté avec soin.',
            canonical: self::absoluteUrl('/'),
            ogImage: self::absoluteUrl(self::DEFAULT_OG_IMAGE),
            ogType: 'website',
            ogImageAlt: 'Bia Namur — Le carnet vivant des Namurois',
            jsonLd: JsonLdBuilder::forHome(),
        );
    }

    public static function forPlacesIndex(): SeoData
    {
        return new SeoData(
            title: 'Les lieux qui font Namur — Bia Namur',
            description: 'La sélection éditoriale des bonnes adresses namuroises : bistrots, cafés, marchés, lieux culturels et patrimoine. Une carte sentimentale loin des guides touristiques.',
            canonical: self::absoluteUrl('/lieux'),
            ogImage: self::absoluteUrl(self::DEFAULT_OG_IMAGE),
            ogType: 'website',
            jsonLd: [
                JsonLdBuilder::breadcrumb([
                    ['name' => 'Accueil', 'url' => '/'],
                    ['name' => 'Lieux', 'url' => '/lieux'],
                ]),
            ],
        );
    }

    public static function forPlace(Place $place): SeoData
    {
        $description = self::truncate(
            $place->description
            ?? "Découvrir {$place->name} à Namur sur Bia Namur — le carnet vivant des Namurois.",
            160,
        );

        $photo = PhotoResolver::for(PhotoResolver::TYPE_PLACE, $place->slug);
        $ogImage = $photo ? self::absoluteUrl($photo['src_jpg']) : self::absoluteUrl(self::DEFAULT_OG_IMAGE);

        return new SeoData(
            title: "{$place->name} — Bia Namur",
            description: $description,
            canonical: self::absoluteUrl("/lieu/{$place->slug}"),
            ogImage: $ogImage,
            ogType: 'website',
            ogImageAlt: $place->name,
            jsonLd: [
                JsonLdBuilder::forPlace($place),
                JsonLdBuilder::breadcrumb([
                    ['name' => 'Accueil', 'url' => '/'],
                    ['name' => 'Lieux', 'url' => '/lieux'],
                    ['name' => $place->name, 'url' => "/lieu/{$place->slug}"],
                ]),
            ],
        );
    }

    public static function forStoriesIndex(): SeoData
    {
        return new SeoData(
            title: 'Stories — Patrimoine, traditions, wallon namurois | Bia Namur',
            description: 'Les histoires qui font Namur : patrimoine vivant, traditions des Fêtes de Wallonie, mots de wallon namurois et anecdotes de ruelles. Écrites avec rigueur, racontées avec chaleur.',
            canonical: self::absoluteUrl('/stories'),
            ogImage: self::absoluteUrl(self::DEFAULT_OG_IMAGE),
            ogType: 'website',
            jsonLd: [
                JsonLdBuilder::breadcrumb([
                    ['name' => 'Accueil', 'url' => '/'],
                    ['name' => 'Stories', 'url' => '/stories'],
                ]),
            ],
        );
    }

    public static function forStory(Story $story): SeoData
    {
        $description = self::truncate(
            $story->excerpt
            ?? Str::limit(strip_tags((string) $story->content), 160),
            160,
        );

        $photo = PhotoResolver::for(PhotoResolver::TYPE_STORY, $story->slug);
        $ogImage = $photo ? self::absoluteUrl($photo['src_jpg']) : self::absoluteUrl(self::DEFAULT_OG_IMAGE);

        return new SeoData(
            title: "{$story->title} — Bia Namur",
            description: $description,
            canonical: self::absoluteUrl("/story/{$story->slug}"),
            ogImage: $ogImage,
            ogType: 'article',
            ogImageAlt: $story->title,
            articlePublishedTime: $story->published_at?->toIso8601String(),
            articleModifiedTime: $story->updated_at?->toIso8601String(),
            jsonLd: [
                JsonLdBuilder::forStory($story),
                JsonLdBuilder::breadcrumb([
                    ['name' => 'Accueil', 'url' => '/'],
                    ['name' => 'Stories', 'url' => '/stories'],
                    ['name' => $story->title, 'url' => "/story/{$story->slug}"],
                ]),
            ],
        );
    }

    public static function forBriefsIndex(): SeoData
    {
        return new SeoData(
            title: 'Les briefs hebdo — Cette semaine à Namur | Bia Namur',
            description: 'Tous les briefs hebdomadaires de Bia Namur. Chaque vendredi, 5 à 7 sélections culturelles namuroises : concerts, expos, marchés, balades, patrimoine.',
            canonical: self::absoluteUrl('/briefs'),
            ogImage: self::absoluteUrl(self::DEFAULT_OG_IMAGE),
            ogType: 'website',
            jsonLd: [
                JsonLdBuilder::breadcrumb([
                    ['name' => 'Accueil', 'url' => '/'],
                    ['name' => 'Briefs', 'url' => '/briefs'],
                ]),
            ],
        );
    }

    public static function forBrief(Brief $brief): SeoData
    {
        $description = self::truncate(
            $brief->intro_text
            ?? "Le brief hebdo Bia Namur — semaine {$brief->week_number} {$brief->year}.",
            160,
        );

        // Photo de couverture si renseignee
        $coverPath = null;
        if ($brief->relationLoaded('photos') && $brief->photos->isNotEmpty()) {
            $coverPath = '/storage/' . $brief->photos->first()->path;
        }
        $ogImage = $coverPath ? self::absoluteUrl($coverPath) : self::absoluteUrl(self::DEFAULT_OG_IMAGE);

        return new SeoData(
            title: "Cette semaine à Namur, semaine {$brief->week_number} — Bia Namur",
            description: $description,
            canonical: self::absoluteUrl("/brief/{$brief->slug}"),
            ogImage: $ogImage,
            ogType: 'article',
            ogImageAlt: $brief->title,
            articlePublishedTime: $brief->published_at?->toIso8601String(),
            articleModifiedTime: $brief->updated_at?->toIso8601String(),
            jsonLd: [
                JsonLdBuilder::forBrief($brief),
                JsonLdBuilder::breadcrumb([
                    ['name' => 'Accueil', 'url' => '/'],
                    ['name' => 'Briefs', 'url' => '/briefs'],
                    ['name' => "Semaine {$brief->week_number}", 'url' => "/brief/{$brief->slug}"],
                ]),
            ],
        );
    }

    public static function forMap(): SeoData
    {
        return new SeoData(
            title: 'La carte sentimentale de Namur — Bia Namur',
            description: 'Tous les lieux Bia Namur sur une carte interactive : bistrots, cafés, lieux culturels, patrimoine. Filtres par type et quartier, géolocalisation autour de moi.',
            canonical: self::absoluteUrl('/carte'),
            ogImage: self::absoluteUrl(self::DEFAULT_OG_IMAGE),
            ogType: 'website',
        );
    }

    public static function forWallon(): SeoData
    {
        return new SeoData(
            title: 'Le wallon namurois — mots, expressions et prononciation | Bia Namur',
            description: 'Le vocabulaire wallon namurois expliqué : bia, biesse, tchafyî, à l\'aise, cougnou. Pour les Namurois qui veulent renouer avec leur langue et les néo-namurois curieux.',
            canonical: self::absoluteUrl('/wallon'),
            ogImage: self::absoluteUrl(self::DEFAULT_OG_IMAGE),
            ogType: 'website',
        );
    }

    public static function forAbout(): SeoData
    {
        return new SeoData(
            title: 'À propos — Bia Namur, le carnet vivant des Namurois',
            description: 'Bia Namur est un projet éditorial indépendant qui célèbre Namur sans condescendance. Notre démarche, nos valeurs, l\'équipe derrière le carnet.',
            canonical: self::absoluteUrl('/a-propos'),
            ogImage: self::absoluteUrl(self::DEFAULT_OG_IMAGE),
            ogType: 'website',
        );
    }

    public static function forContribute(): SeoData
    {
        return new SeoData(
            title: 'Contribuer — Partagez un bon spot namurois | Bia Namur',
            description: 'Vous connaissez un bistrot, une balade, une anecdote qui mérite Bia Namur ? Envoyez-nous votre contribution, on relit chaque proposition avec soin.',
            canonical: self::absoluteUrl('/contribuer'),
            ogImage: self::absoluteUrl(self::DEFAULT_OG_IMAGE),
            ogType: 'website',
        );
    }

    /**
     * Pages legales et techniques : on garde un title parlant mais noindex
     * pour ne pas polluer la Search Console (ces pages n'apportent rien au SEO).
     */
    public static function forLegal(string $type): SeoData
    {
        $map = [
            'mentions' => ['Mentions légales', '/mentions-legales'],
            'privacy' => ['Politique de confidentialité', '/confidentialite'],
            'terms' => ['Conditions générales d\'utilisation', '/cgu'],
        ];
        [$label, $path] = $map[$type] ?? ['Bia Namur', '/'];

        return new SeoData(
            title: "{$label} — Bia Namur",
            description: "Les {$label} de Bia Namur.",
            canonical: self::absoluteUrl($path),
            ogImage: self::absoluteUrl(self::DEFAULT_OG_IMAGE),
            ogType: 'website',
            noindex: true,
        );
    }

    /**
     * Default : utilise quand un controller n'a pas explicitement appele
     * une factory ci-dessus. Garde Bia Namur indexable avec une description
     * generique mais legitime.
     */
    public static function default(): SeoData
    {
        return self::forHome();
    }

    private static function absoluteUrl(string $path): string
    {
        $base = rtrim((string) config('app.url'), '/');
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return $base . '/' . ltrim($path, '/');
    }

    private static function truncate(string $text, int $length): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');

        return Str::limit($clean, $length);
    }
}
