<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BriefResource;
use App\Models\Brief;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestBriefsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Derniers briefs hebdo')
            ->description('Les 5 plus récents briefs générés ou publiés.')
            ->query(
                BriefResource::getEloquentQuery()
                    ->orderByDesc('year')
                    ->orderByDesc('week_number')
                    ->limit(5),
            )
            ->columns([
                Tables\Columns\TextColumn::make('slug')
                    ->label('Numéro')
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->limit(50),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->badge()
                    ->color(fn (?int $s) => match (true) {
                        $s === null || $s < 5 => 'warning',
                        $s >= 5 && $s <= 7 => 'success',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Brief::STATUS_PUBLISHED => 'success',
                        Brief::STATUS_PENDING_REVIEW => 'warning',
                        Brief::STATUS_DRAFT_AI => 'info',
                        Brief::STATUS_ARCHIVED => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('generated_at')
                    ->label('Généré')
                    ->dateTime('d/m/Y H:i')
                    ->since(),
            ])
            ->paginated(false)
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Ouvrir')
                    ->icon('heroicon-m-arrow-right')
                    ->url(fn (Brief $record) => BriefResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
