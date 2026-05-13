<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ShareSeoDefaults;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            ShareSeoDefaults::class,
            HandleInertiaRequests::class,
        ]);

        // Bypass CSRF pour les endpoints webhook : Telegram et autres
        // services externes ne peuvent pas attacher de token CSRF.
        // L'auth se fait via secret en URL + verification additionnelle
        // (chat_id pour Telegram, signature pour d'autres webhooks).
        $middleware->validateCsrfTokens(except: [
            'webhooks/telegram/*',
            // Endpoints de deploiement appeles par GitHub Actions / cron-job.org :
            // pas de session navigateur donc pas de token CSRF possible.
            // L'authentification se fait via secret partage en POST/query.
            '_deploy/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
