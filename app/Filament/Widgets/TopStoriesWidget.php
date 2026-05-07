<?php

namespace App\Filament\Widgets;

use App\Models\PageView;
use App\Models\Story;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Top 10 stories les plus lues sur les 30 derniers jours.
 *
 * Pendant complementaire a TopPlacesWidget — utile pour decider quelles
 * stories valent une mise en avant accrue dans le brief, ou quels
 * sujets meritent une suite editoriale.
 */
class TopStoriesWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 1];

    protected static ?string $heading = 'Top 10 stories (30 j)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Story::query()
                    ->select('stories.id', 'stories.slug', 'stories.title', 'stories.type')
                    ->selectSub(
                        PageView::query()
                            ->selectRaw('COUNT(*)')
                            ->where('viewable_type', Story::class)
                            ->whereColumn('viewable_id', 'stories.id')
                            ->where('is_bot', false)
                            ->where('viewed_at', '>=', now()->subDays(30)),
                        'views_30d',
                    )
                    ->where('stories.status', Story::STATUS_PUBLISHED)
                    ->orderByDesc('views_30d')
                    ->limit(10),
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Story')
                    ->weight('medium')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('views_30d')
                    ->label('Vues')
                    ->badge()
                    ->color('primary')
                    ->alignEnd(),
            ])
            ->paginated(false)
            ->emptyStateHeading('Aucune vue enregistrée pour le moment.')
            ->emptyStateDescription('Les statistiques apparaîtront dès que des visiteurs consulteront les stories.');
    }
}
