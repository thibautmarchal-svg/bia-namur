<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#C77F2C">

    {{-- SEO core --}}
    <title>{{ $seo->title }}</title>
    <meta name="description" content="{{ $seo->description }}">
    <link rel="canonical" href="{{ $seo->canonical }}">
    @if ($seo->noindex)
        <meta name="robots" content="noindex, nofollow">
    @endif

    {{-- Open Graph (Facebook / LinkedIn / WhatsApp / Slack) --}}
    <meta property="og:site_name" content="Bia Namur">
    <meta property="og:locale" content="fr_BE">
    <meta property="og:type" content="{{ $seo->ogType }}">
    <meta property="og:title" content="{{ $seo->title }}">
    <meta property="og:description" content="{{ $seo->description }}">
    <meta property="og:url" content="{{ $seo->canonical }}">
    <meta property="og:image" content="{{ $seo->ogImage }}">
    <meta property="og:image:alt" content="{{ $seo->ogImageAlt }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    @if ($seo->articlePublishedTime)
        <meta property="article:published_time" content="{{ $seo->articlePublishedTime }}">
    @endif
    @if ($seo->articleModifiedTime)
        <meta property="article:modified_time" content="{{ $seo->articleModifiedTime }}">
    @endif

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo->title }}">
    <meta name="twitter:description" content="{{ $seo->description }}">
    <meta name="twitter:image" content="{{ $seo->ogImage }}">
    <meta name="twitter:image:alt" content="{{ $seo->ogImageAlt }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/pwa-icons/apple-touch-icon.png">

    {{-- PWA manifest : present uniquement en build prod (Vite l'a genere dans /build) --}}
    @if (file_exists(public_path('build/manifest.webmanifest')))
        <link rel="manifest" href="{{ asset('build/manifest.webmanifest') }}">
    @endif

    {{-- Fontes via Bunny Fonts (alternative Google Fonts respectueuse RGPD) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=lora:400,500,600|inter-tight:400,500,600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead

    {{-- JSON-LD Schema.org : injecte cote serveur pour que Googlebot le voit
         immediatement (sans attendre l'hydration JS). Plusieurs schemas par
         page autorises (recommande Google) : separe @Article + BreadcrumbList. --}}
    @foreach ($seo->jsonLd as $schema)
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endforeach
</head>
<body class="h-full bg-bia-cream text-bia-ink font-sans antialiased">
    @inertia

    {{-- Cloudflare Web Analytics : cookieless, RGPD-friendly. Active uniquement
         si BIA_CLOUDFLARE_BEACON_TOKEN est defini en prod. --}}
    @if (app()->environment('production') && config('bia.analytics.cloudflare_beacon_token'))
        <script defer
                src="https://static.cloudflareinsights.com/beacon.min.js"
                data-cf-beacon='{"token": "{{ config('bia.analytics.cloudflare_beacon_token') }}"}'>
        </script>
    @endif
</body>
</html>
