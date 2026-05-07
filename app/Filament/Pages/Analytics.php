<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PageViewsTrendWidget;
use App\Filament\Widgets\TopPlacesWidget;
use App\Filament\Widgets\TopReferrersWidget;
use App\Filament\Widgets\TopStoriesWidget;
use App\Filament\Widgets\ViewsByTypeWidget;
use Filament\Pages\Page;

class Analytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Analytics';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.analytics';

    protected static ?string $title = 'Analytics éditoriales';

    public function getHeaderWidgets(): array
    {
        return [
            PageViewsTrendWidget::class,
            ViewsByTypeWidget::class,
            TopReferrersWidget::class,
            TopPlacesWidget::class,
            TopStoriesWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|string|array
    {
        return [
            'default' => 1,
            'lg' => 2,
        ];
    }
}
