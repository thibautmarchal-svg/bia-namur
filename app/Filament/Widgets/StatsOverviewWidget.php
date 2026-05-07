<?php

namespace App\Filament\Widgets;

use App\Models\Brief;
use App\Models\Contribution;
use App\Models\Place;
use App\Models\Story;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $placesPublished = Place::where('status', Place::STATUS_PUBLISHED)->count();
        $placesDraft = Place::where('status', Place::STATUS_DRAFT)->count();

        $storiesPublished = Story::where('status', Story::STATUS_PUBLISHED)->count();
        $storiesDraft = Story::whereIn('status', [Story::STATUS_DRAFT, Story::STATUS_PENDING_REVIEW])->count();

        $briefsTotal = Brief::count();
        $briefsAwaitingReview = Brief::whereIn(
            'status',
            [Brief::STATUS_DRAFT_AI, Brief::STATUS_PENDING_REVIEW],
        )->count();

        $contribPending = Contribution::whereIn(
            'status',
            [Contribution::STATUS_PENDING, Contribution::STATUS_MANUAL_REVIEW],
        )->count();

        return [
            Stat::make('Lieux publiés', $placesPublished)
                ->description($placesDraft > 0 ? "{$placesDraft} en brouillon" : 'Tous publiés')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('primary'),

            Stat::make('Stories', $storiesPublished)
                ->description($storiesDraft > 0 ? "{$storiesDraft} à relire" : 'Toutes publiées')
                ->descriptionIcon('heroicon-m-book-open')
                ->color($storiesDraft > 0 ? 'warning' : 'success'),

            Stat::make('Briefs hebdo', $briefsTotal)
                ->description($briefsAwaitingReview > 0 ? "{$briefsAwaitingReview} à relire" : 'À jour')
                ->descriptionIcon('heroicon-m-newspaper')
                ->color($briefsAwaitingReview > 0 ? 'warning' : 'success'),

            Stat::make('Contributions', $contribPending)
                ->description($contribPending > 0 ? 'En attente de modération' : 'Aucune en attente')
                ->descriptionIcon('heroicon-m-hand-raised')
                ->color($contribPending > 0 ? 'danger' : 'gray'),
        ];
    }
}
