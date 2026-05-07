<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#C77F2C">
    <meta name="description" content="Bia Namur — le carnet vivant des Namurois. Brief hebdo curaté, carte sentimentale des bonnes adresses, stories du patrimoine.">

    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">

    <title inertia>{{ config('app.name', 'Bia Namur') }}</title>

    {{-- Fontes via Bunny Fonts (alternative Google Fonts respectueuse RGPD) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=lora:400,500,600|inter-tight:400,500,600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="h-full bg-bia-cream text-bia-ink font-sans antialiased">
    @inertia
</body>
</html>
