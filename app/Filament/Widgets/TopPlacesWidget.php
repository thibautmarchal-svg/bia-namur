<?php

namespace App\Filament\Widgets;

use App\Models\PageView;
use App\Models\Place;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Top 10 lieux consultes sur les 30 derniers jours.
 *
 * On exclut les bots (`is_bot = false`) pour avoir une vue editoriale
 * proche de la realite. La requete groupe par viewable_id et compte
 * les vues uniques (le middleware dedup deja par hash IP sur 24h).
 */
class TopPlacesWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 1];

    protected static ?string $heading = 'Top 10 lieux (30 j)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Place::query()
                    ->select('places.id', 'places.slug', 'places.name', 'places.type')
                    ->selectSub(
                        PageView::query()
                            ->selectRaw('COUNT(*)')
                            ->where('viewable_type', Place::class)
                            ->whereColumn('viewable_id', 'places.id')
                            ->where('is_bot', false)
                            ->where('viewed_at', '>=', now()->subDays(30)),
                        'views_30d',
                    )
                    ->where('places.status', Place::STATUS_PUBLISHED)
                    ->orderByDesc('views_30d')
                    ->limit(10),
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Lieu')
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
            ->emptyStateDescription('Les statistiques apparaîtront dès que des visiteurs consulteront les fiches.');
    }
}
