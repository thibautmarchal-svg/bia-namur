<?php

namespace App\Filament\Widgets;

use App\Models\AiRun;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AiCostsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth();

        $monthly = AiRun::query()
            ->where('created_at', '>=', $monthStart)
            ->selectRaw('SUM(cost_usd) as total_cost, SUM(input_tokens) as in_tokens, SUM(output_tokens) as out_tokens, COUNT(*) as runs')
            ->first();

        $totalCost = (float) ($monthly->total_cost ?? 0);
        $inTokens = (int) ($monthly->in_tokens ?? 0);
        $outTokens = (int) ($monthly->out_tokens ?? 0);
        $runs = (int) ($monthly->runs ?? 0);

        $budgetUsd = 5;     // limite indicative — Anthropic limite 50€/mois côté config
        $usagePct = $budgetUsd > 0 ? min(100, ($totalCost / $budgetUsd) * 100) : 0;

        $failed = AiRun::query()
            ->where('created_at', '>=', $monthStart)
            ->where('status', AiRun::STATUS_FAILED)
            ->count();

        return [
            Stat::make('Coût Claude (mois)', '$' . number_format($totalCost, 3))
                ->description("{$runs} appels · " . number_format($usagePct, 0) . '% du budget indicatif')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color($usagePct > 80 ? 'danger' : ($usagePct > 50 ? 'warning' : 'success')),

            Stat::make('Tokens entrée', number_format($inTokens, 0, ',', ' '))
                ->description('Prompt + contexte envoyés')
                ->descriptionIcon('heroicon-m-arrow-up-on-square')
                ->color('gray'),

            Stat::make('Tokens sortie', number_format($outTokens, 0, ',', ' '))
                ->description('Briefs + stories générés')
                ->descriptionIcon('heroicon-m-arrow-down-on-square')
                ->color('gray'),

            Stat::make('Appels en échec', $failed)
                ->description($failed > 0 ? 'À investiguer dans ai_runs' : 'Aucun ce mois')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($failed > 0 ? 'danger' : 'success'),
        ];
    }
}
