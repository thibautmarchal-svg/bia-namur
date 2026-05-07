<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AiCostsWidget;
use App\Filament\Widgets\IngestionHealthWidget;
use App\Filament\Widgets\LatestBriefsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\TopPlacesWidget;
use App\Filament\Widgets\TopStoriesWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Bia Namur — Admin')
            // Pas de ->login() ici : on délègue à notre page magic link via la route 'login'
            ->colors([
                'primary' => [
                    50 => '#FBF3E5',
                    100 => '#F5E1C0',
                    200 => '#EFCD97',
                    300 => '#E5A965',
                    400 => '#D6943F',
                    500 => '#C77F2C',
                    600 => '#A66822',
                    700 => '#8B5618',
                    800 => '#6E430E',
                    900 => '#523108',
                    950 => '#2C1A04',
                ],
                'gray' => Color::Stone,
            ])
            ->favicon(asset('favicon.svg'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                StatsOverviewWidget::class,
                TopPlacesWidget::class,
                TopStoriesWidget::class,
                AiCostsWidget::class,
                IngestionHealthWidget::class,
                LatestBriefsWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make()->label('Contenu éditorial'),
                NavigationGroup::make()->label('Communauté'),
                NavigationGroup::make()->label('Pipelines IA'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
