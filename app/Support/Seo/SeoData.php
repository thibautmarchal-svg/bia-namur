<?php

namespace App\Support\Seo;

/**
 * DTO immutable representant les meta SEO d'une page.
 *
 * Injecte dans la vue Blade racine via view()->share('seo', ...).
 * Lue par resources/views/app.blade.php pour generer title + description
 * + canonical + Open Graph + Twitter Card dans le HTML servi (donc visible
 * par Google et les bots reseaux sociaux qui n'executent pas le JS).
 */
class SeoData
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $canonical,
        public readonly string $ogImage,
        public readonly string $ogType = 'website',
        public readonly string $ogImageAlt = 'Bia Namur',
        public readonly ?string $articlePublishedTime = null,
        public readonly ?string $articleModifiedTime = null,
        public readonly bool $noindex = false,
    ) {}
}
