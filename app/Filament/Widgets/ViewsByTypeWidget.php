<?php

namespace App\Filament\Widgets;

use App\Models\Brief;
use App\Models\PageView;
use App\Models\Place;
use App\Models\Story;
use Filament\Widgets\ChartWidget;

/**
 * Repartition des vues entre lieux / stories / briefs sur la fenetre
 * choisie. Bar simple avec les 3 totaux — assez pour repondre "qu'est-ce
 * qui interesse le plus les visiteurs en ce moment".
 */
class ViewsByTypeWidget extends ChartWidget
{
    protected static ?string $heading = 'Vues par type de contenu';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 1];

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => '7 jours',
            '30' => '30 jours',
            '90' => '90 jours',
        ];
    }

    protected function getData(): array
    {
        $days = (int) ($this->filter ?? 30);
        $start = now()->subDays($days - 1)->startOfDay();

        $byType = PageView::query()
            ->where('is_bot', false)
            ->where('viewed_at', '>=', $start)
            ->selectRaw('viewable_type, COUNT(*) as views')
            ->groupBy('viewable_type')
            ->pluck('views', 'viewable_type')
            ->all();

        return [
            'datasets' => [
                [
                    'label' => 'Vues',
                    'data' => [
                        (int) ($byType[Place::class] ?? 0),
                        (int) ($byType[Story::class] ?? 0),
                        (int) ($byType[Brief::class] ?? 0),
                    ],
                    'backgroundColor' => ['#C77F2C', '#E5A965', '#8B5618'],
                ],
            ],
            'labels' => ['Lieux', 'Stories', 'Briefs'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
        ];
    }
}
