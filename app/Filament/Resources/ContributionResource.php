<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContributionResource\Pages;
use App\Models\Contribution;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContributionResource extends Resource
{
    protected static ?string $model = Contribution::class;

    protected static ?string $navigationIcon = 'heroicon-o-hand-raised';

    protected static ?string $navigationGroup = 'Communauté';

    protected static ?string $navigationLabel = 'Contributions';

    protected static ?string $modelLabel = 'contribution';

    protected static ?string $pluralModelLabel = 'contributions';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::query()
            ->whereIn('status', [Contribution::STATUS_PENDING, Contribution::STATUS_MANUAL_REVIEW])
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contribution')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Contributeur')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->disabled(),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                Contribution::TYPE_PLACE_SUGGESTION => 'Suggestion de lieu',
                                Contribution::TYPE_PHOTO => 'Photo',
                                Contribution::TYPE_CORRECTION => 'Correction',
                                Contribution::TYPE_STORY_PROPOSAL => 'Proposition de story',
                            ])
                            ->disabled(),
                        Forms\Components\KeyValue::make('payload')
                            ->label('Contenu de la contribution')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('target_place_id')
                            ->label('Lieu cible')
                            ->relationship('targetPlace', 'name')
                            ->searchable(),
                        Forms\Components\Select::make('target_story_id')
                            ->label('Story cible')
                            ->relationship('targetStory', 'title')
                            ->searchable(),
                    ]),

                Forms\Components\Section::make('Score IA')
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('ai_score')
                            ->label('Score (0-100)')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('ai_model')
                            ->label('Modèle')
                            ->disabled(),
                        Forms\Components\TextInput::make('ai_prompt_version')
                            ->label('Version prompt')
                            ->disabled(),
                        Forms\Components\KeyValue::make('ai_reasoning')
                            ->label('Raisonnement IA')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Modération')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                Contribution::STATUS_PENDING => 'En attente',
                                Contribution::STATUS_AUTO_APPROVED => 'Auto-approuvée',
                                Contribution::STATUS_MANUAL_REVIEW => 'À relire',
                                Contribution::STATUS_REJECTED => 'Rejetée',
                                Contribution::STATUS_MERGED => 'Intégrée',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('reviewed_at')
                            ->label('Modéré le'),
                        Forms\Components\Textarea::make('reviewer_notes')
                            ->label('Notes modérateur')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Contributeur')
                    ->searchable()
                    ->default('Anonyme'),
                Tables\Columns\TextColumn::make('targetPlace.name')
                    ->label('Lieu cible')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ai_score')
                    ->label('Score IA')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 75 => 'success',
                        $state >= 40 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Contribution::STATUS_AUTO_APPROVED, Contribution::STATUS_MERGED => 'success',
                        Contribution::STATUS_PENDING, Contribution::STATUS_MANUAL_REVIEW => 'warning',
                        Contribution::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Reçue')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Contribution::STATUS_PENDING => 'En attente',
                        Contribution::STATUS_AUTO_APPROVED => 'Auto-approuvée',
                        Contribution::STATUS_MANUAL_REVIEW => 'À relire',
                        Contribution::STATUS_REJECTED => 'Rejetée',
                        Contribution::STATUS_MERGED => 'Intégrée',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        Contribution::TYPE_PLACE_SUGGESTION => 'Suggestion lieu',
                        Contribution::TYPE_PHOTO => 'Photo',
                        Contribution::TYPE_CORRECTION => 'Correction',
                        Contribution::TYPE_STORY_PROPOSAL => 'Story',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContributions::route('/'),
            'edit' => Pages\EditContribution::route('/{record}/edit'),
        ];
    }
}
