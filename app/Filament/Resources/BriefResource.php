<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BriefResource\Pages;
use App\Models\Brief;
use App\Models\City;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BriefResource extends Resource
{
    protected static ?string $model = Brief::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'Contenu éditorial';

    protected static ?string $navigationLabel = 'Briefs hebdo';

    protected static ?string $modelLabel = 'brief';

    protected static ?string $pluralModelLabel = 'briefs';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Brief de la semaine')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('city_id')
                            ->label('Ville')
                            ->relationship('city', 'name')
                            ->default(fn () => City::where('slug', 'namur')->value('id'))
                            ->required(),
                        Forms\Components\TextInput::make('year')
                            ->label('Année')
                            ->numeric()
                            ->default(now()->year)
                            ->required(),
                        Forms\Components\TextInput::make('week_number')
                            ->label('Semaine ISO')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(53)
                            ->default(now()->isoWeek())
                            ->required(),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(60)
                            ->placeholder('2026-W19'),
                        Forms\Components\TextInput::make('title')
                            ->label('Titre du brief')
                            ->required()
                            ->columnSpan(2),
                        Forms\Components\Textarea::make('intro_text')
                            ->label('Intro éditoriale (2-3 phrases)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Statut & relecture')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                Brief::STATUS_DRAFT_AI => 'Brouillon IA',
                                Brief::STATUS_PENDING_REVIEW => 'À relire',
                                Brief::STATUS_PUBLISHED => 'Publié',
                                Brief::STATUS_ARCHIVED => 'Archivé',
                            ])
                            ->default(Brief::STATUS_DRAFT_AI)
                            ->required(),
                        Forms\Components\Select::make('reviewer_id')
                            ->label('Relu par')
                            ->relationship('reviewer', 'name')
                            ->searchable(),
                        Forms\Components\DateTimePicker::make('generated_at')
                            ->label('Généré le'),
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Publié le'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')
                    ->label('Numéro')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('year')
                    ->label('Année')
                    ->sortable(),
                Tables\Columns\TextColumn::make('week_number')
                    ->label('Sem.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null || $state < 5 => 'warning',
                        $state >= 5 && $state <= 7 => 'success',
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
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Publié')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('year', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Brief::STATUS_DRAFT_AI => 'Brouillon IA',
                        Brief::STATUS_PENDING_REVIEW => 'À relire',
                        Brief::STATUS_PUBLISHED => 'Publié',
                        Brief::STATUS_ARCHIVED => 'Archivé',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            BriefResource\RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBriefs::route('/'),
            'create' => Pages\CreateBrief::route('/create'),
            'edit' => Pages\EditBrief::route('/{record}/edit'),
        ];
    }
}
