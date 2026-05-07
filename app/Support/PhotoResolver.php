<?php

namespace App\Support;

/**
 * Resolution de la photo de couverture pour un Place ou une Story.
 *
 * Strategie par ordre de priorite :
 *  1. Photo perso uploadee dans public/images/places/{slug}.{webp,jpg,png}
 *     ou public/images/stories/{slug}.{webp,jpg,png} (override admin/user).
 *     Si presente : retourne cette photo sans credit (assume copyright Bia
 *     Namur / contrib utilisateur sous CGU).
 *  2. Photo par defaut definie dans config/bia-photos.php (Wikimedia Commons).
 *  3. null (composant frontend affiche le fallback serif "Bia").
 *
 * Le srcset utilise les variantes 800w et 1600w generees par
 * scripts/optimize-photos.mjs (webp + jpg fallback).
 */
class PhotoResolver
{
    public const TYPE_PLACE = 'places';

    public const TYPE_STORY = 'stories';

    public const TYPE_BRIEF = 'briefs';

    /**
     * Retourne le payload photo pour un slug donne.
     *
     * @return array{url:string, src_jpg:string, srcset:string, sizes:string, alt:string, credit:?string, license:?string, license_url:?string, source_url:?string, is_override:bool}|null
     */
    public static function for(string $type, string $slug, ?string $altOverride = null): ?array
    {
        // 1. Override perso : check public/images/{type}/{slug}.{webp,jpg,png}
        $personalPhoto = self::resolvePersonal($type, $slug);
        if ($personalPhoto !== null) {
            return [
                'url' => asset($personalPhoto),
                'src_jpg' => asset($personalPhoto),
                'srcset' => asset($personalPhoto),
                'sizes' => '(min-width: 1024px) 1024px, 100vw',
                'alt' => $altOverride ?? '',
                'credit' => null,
                'license' => null,
                'license_url' => null,
                'source_url' => null,
                'is_override' => true,
            ];
        }

        // 2. Default Wikimedia
        $config = config("bia-photos.{$type}.{$slug}");
        if (! is_array($config)) {
            return null;
        }

        $base = $config['path'];   // e.g. "images/defaults/places/citadelle-de-namur"

        return [
            'url' => asset("{$base}-1600.webp"),
            'src_jpg' => asset("{$base}.jpg"),
            'srcset' => sprintf(
                '%s 800w, %s 1600w',
                asset("{$base}-800.webp"),
                asset("{$base}-1600.webp"),
            ),
            'sizes' => '(min-width: 1024px) 1024px, 100vw',
            'alt' => $altOverride ?? $config['alt'] ?? '',
            'credit' => $config['credit'] ?? null,
            'license' => $config['license'] ?? null,
            'license_url' => $config['license_url'] ?? null,
            'source_url' => $config['source_url'] ?? null,
            'is_override' => false,
        ];
    }

    /**
     * Cherche une photo perso dans public/images/{type}/{slug}.{ext}
     * et retourne le chemin relatif (sans /), ou null si absente.
     */
    protected static function resolvePersonal(string $type, string $slug): ?string
    {
        $publicPath = public_path("images/{$type}");
        if (! is_dir($publicPath)) {
            return null;
        }

        foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
            $relative = "images/{$type}/{$slug}.{$ext}";
            if (is_file(public_path($relative))) {
                return $relative;
            }
        }

        return null;
    }
}
