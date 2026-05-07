<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoryResource\Pages;
use App\Models\Story;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StoryResource extends Resource
{
    protected static ?string $model = Story::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Contenu éditorial';

    protected static ?string $navigationLabel = 'Stories';

    protected static ?string $modelLabel = 'story';

    protected static ?string $pluralModelLabel = 'stories';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Story')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('city_id')
                            ->label('Ville')
                            ->relationship('city', 'name')
                            ->default(fn () => \App\Models\City::where('slug', 'namur')->value('id'))
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                Story::TYPE_PLACE => 'Histoire d\'un lieu',
                                Story::TYPE_TRADITION => 'Tradition / fête',
                                Story::TYPE_WALLON => 'Wallon namurois',
                                Story::TYPE_PATRIMOINE => 'Patrimoine',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(200),
                        Forms\Components\Select::make('place_id')
                            ->label('Lieu associé (optionnel)')
                            ->relationship('place', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('excerpt')
                            ->label('Chapeau / résumé')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('content')
                            ->label('Contenu (markdown)')
                            ->required()
                            ->rows(14)
                            ->helperText('200 à 400 mots. Ton namurois assumé.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Génération IA')
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        Forms\Components\Toggle::make('ai_generated')
                            ->label('Généré par IA'),
                        Forms\Components\TextInput::make('ai_model')
                            ->label('Modèle')
                            ->placeholder('claude-sonnet-4-6'),
                        Forms\Components\TextInput::make('ai_prompt_version')
                            ->label('Version prompt')
                            ->placeholder('story_v1'),
                    ]),

                Forms\Components\Section::make('Statut')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                Story::STATUS_DRAFT => 'Brouillon',
                                Story::STATUS_PENDING_REVIEW => 'À relire',
                                Story::STATUS_PUBLISHED => 'Publié',
                                Story::STATUS_ARCHIVED => 'Archivé',
                            ])
                            ->default(Story::STATUS_DRAFT)
                            ->required(),
                        Forms\Components\Select::make('reviewed_by')
                            ->label('Relu par')
                            ->relationship('reviewer', 'name')
                            ->searchable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('place.name')
                    ->label('Lieu')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Story::STATUS_PUBLISHED => 'success',
                        Story::STATUS_PENDING_REVIEW => 'warning',
                        Story::STATUS_DRAFT => 'gray',
                        Story::STATUS_ARCHIVED => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('ai_generated')
                    ->label('IA')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifié')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Story::STATUS_DRAFT => 'Brouillon',
                        Story::STATUS_PENDING_REVIEW => 'À relire',
                        Story::STATUS_PUBLISHED => 'Publié',
                        Story::STATUS_ARCHIVED => 'Archivé',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        Story::TYPE_PLACE => 'Lieu',
                        Story::TYPE_TRADITION => 'Tradition',
                        Story::TYPE_WALLON => 'Wallon',
                        Story::TYPE_PATRIMOINE => 'Patrimoine',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStories::route('/'),
            'create' => Pages\CreateStory::route('/create'),
            'edit' => Pages\EditStory::route('/{record}/edit'),
        ];
    }
}
