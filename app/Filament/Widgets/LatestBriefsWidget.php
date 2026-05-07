<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BriefResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestBriefsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Derniers briefs hebdo';

    public function table(Table $table): Table
    {
        return $table
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
                    ->color('primary'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('generated_at')
                    ->label('Généré')
                    ->dateTime('d/m/Y H:i')
                    ->since(),
            ])
            ->paginated(false);
    }
}
