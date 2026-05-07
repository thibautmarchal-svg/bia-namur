<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class IngestionHealthWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Ingestion — santé des sources';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Event::query()
                    ->selectRaw('MAX(id) as id, source')
                    ->selectRaw("SUM(CASE WHEN status='ingested' THEN 1 ELSE 0 END) as count_ingested")
                    ->selectRaw("SUM(CASE WHEN status='normalized' THEN 1 ELSE 0 END) as count_normalized")
                    ->selectRaw("SUM(CASE WHEN status='dropped' THEN 1 ELSE 0 END) as count_dropped")
                    ->selectRaw('MAX(ingested_at) as last_ingested')
                    ->groupBy('source'),
            )
            ->columns([
                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('count_normalized')
                    ->label('Normalisés')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('count_ingested')
                    ->label('À normaliser')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('count_dropped')
                    ->label('Doublons')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_ingested')
                    ->label('Dernier ingest')
                    ->since(),
            ])
            ->paginated(false)
            ->poll('60s');
    }
}
