<?php

namespace App\Filament\Widgets;

use App\Models\PageView;
use Filament\Widgets\ChartWidget;

/**
 * Vues quotidiennes sur les N derniers jours (filtre 7/30/90).
 *
 * Une ligne unique avec le total non-bot par jour. Volontairement simple :
 * sur ce volume editorial, l'utile c'est la tendance globale, pas le
 * detail par type.
 */
class PageViewsTrendWidget extends ChartWidget
{
    protected static ?string $heading = 'Vues quotidiennes';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

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

        $rows = PageView::query()
            ->where('is_bot', false)
            ->where('viewed_at', '>=', $start)
            ->selectRaw('DATE(viewed_at) as day, COUNT(*) as views')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('views', 'day')
            ->all();

        $labels = [];
        $values = [];
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($days - 1 - $i)->format('Y-m-d');
            $labels[] = now()->subDays($days - 1 - $i)->isoFormat('D MMM');
            $values[] = (int) ($rows[$date] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Vues',
                    'data' => $values,
                    'borderColor' => '#C77F2C',
                    'backgroundColor' => 'rgba(199, 127, 44, 0.12)',
                    'tension' => 0.3,
                    'fill' => true,
                    'pointRadius' => 2,
                    'pointHoverRadius' => 5,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
