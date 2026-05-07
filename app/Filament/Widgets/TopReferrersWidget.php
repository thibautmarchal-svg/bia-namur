<?php

namespace App\Filament\Widgets;

use App\Models\PageView;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Top sources de trafic externe (referrer host) sur la fenetre 30j.
 *
 * Le middleware RecordPageView skip les self-referrals donc seules les
 * sources externes apparaissent. Utile pour reperer un partage qui
 * cartonne (Twitter, Reddit, presse locale).
 */
class TopReferrersWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 1];

    protected static ?string $heading = 'Top sources externes (30 j)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PageView::query()
                    ->select('referrer_host')
                    ->selectRaw('COUNT(*) as views')
                    ->where('is_bot', false)
                    ->whereNotNull('referrer_host')
                    ->where('viewed_at', '>=', now()->subDays(30))
                    ->groupBy('referrer_host')
                    ->orderByDesc('views')
                    ->limit(10),
            )
            ->columns([
                Tables\Columns\TextColumn::make('referrer_host')
                    ->label('Source')
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('views')
                    ->label('Vues')
                    ->badge()
                    ->color('primary')
                    ->alignEnd(),
            ])
            ->paginated(false)
            ->emptyStateHeading('Pas encore de trafic externe')
            ->emptyStateDescription('Les sources apparaitront des qu\'un partage externe ramenera des visiteurs.');
    }
}
