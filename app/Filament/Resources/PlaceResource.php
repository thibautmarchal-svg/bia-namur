<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlaceResource\Pages;
use App\Models\City;
use App\Models\Place;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlaceResource extends Resource
{
    protected static ?string $model = Place::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Contenu éditorial';

    protected static ?string $navigationLabel = 'Lieux';

    protected static ?string $modelLabel = 'lieu';

    protected static ?string $pluralModelLabel = 'lieux';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identité du lieu')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('city_id')
                            ->label('Ville')
                            ->relationship('city', 'name')
                            ->default(fn () => City::where('slug', 'namur')->value('id'))
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'cafe' => 'Café',
                                'restaurant' => 'Restaurant',
                                'bar' => 'Bar',
                                'boulangerie' => 'Boulangerie',
                                'librairie' => 'Librairie',
                                'patrimoine' => 'Patrimoine',
                                'parc' => 'Parc / espace vert',
                                'marche' => 'Marché',
                                'culture' => 'Lieu culturel',
                                'hidden_gem' => 'Hidden gem',
                            ])
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug (URL)')
                            ->required()
                            ->maxLength(200)
                            ->helperText('Identifiant URL, ex: citadelle-de-namur'),
                        Forms\Components\TextInput::make('neighborhood')
                            ->label('Quartier')
                            ->maxLength(80)
                            ->placeholder('Grognon, Jambes, Bouge…'),
                        Forms\Components\Textarea::make('description')
                            ->label('Description courte')
                            ->maxLength(500)
                            ->rows(3)
                            ->helperText('500 caractères max — l\'angle, pas la pub.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Localisation')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Adresse')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->step('any'),
                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->step('any'),
                    ]),

                Forms\Components\Section::make('Contact & ouverture')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Forms\Components\KeyValue::make('contact')
                            ->label('Contact')
                            ->keyLabel('Type')
                            ->valueLabel('Valeur')
                            ->keyPlaceholder('phone, email, website, instagram')
                            ->columnSpanFull(),
                        Forms\Components\KeyValue::make('opening_hours')
                            ->label('Horaires (JSON libre)')
                            ->keyLabel('Jour')
                            ->valueLabel('Horaire')
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('tags')
                            ->label('Tags / mood')
                            ->placeholder('terrasse, matin, bio, famille…')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Statut & source')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                Place::STATUS_DRAFT => 'Brouillon',
                                Place::STATUS_PUBLISHED => 'Publié',
                                Place::STATUS_ARCHIVED => 'Archivé',
                            ])
                            ->default(Place::STATUS_DRAFT)
                            ->required(),
                        Forms\Components\Select::make('source')
                            ->label('Source')
                            ->options([
                                Place::SOURCE_ADMIN => 'Curation admin',
                                Place::SOURCE_OPENDATA => 'OpenData Namur',
                                Place::SOURCE_CONTRIBUTION => 'Contribution utilisateur',
                            ])
                            ->default(Place::SOURCE_ADMIN)
                            ->required(),
                        Forms\Components\Select::make('story_id')
                            ->label('Story principale')
                            ->relationship('story', 'title')
                            ->searchable()
                            ->preload(),
                    ]),

                Forms\Components\Section::make('Encart sponsorisé')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('is_sponsored')
                            ->label('Encart éditorialisé actif')
                            ->helperText('Doit être marqué visiblement (badge sur la fiche).'),
                        Forms\Components\TextInput::make('sponsored_label')
                            ->label('Mention')
                            ->placeholder('« Présenté par … »'),
                        Forms\Components\DateTimePicker::make('sponsored_until')
                            ->label('Fin du sponso'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('neighborhood')
                    ->label('Quartier')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Place::STATUS_PUBLISHED => 'success',
                        Place::STATUS_DRAFT => 'warning',
                        Place::STATUS_ARCHIVED => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Place::SOURCE_ADMIN => 'primary',
                        Place::SOURCE_OPENDATA => 'info',
                        Place::SOURCE_CONTRIBUTION => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_sponsored')
                    ->label('Sponso')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifié')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Place::STATUS_DRAFT => 'Brouillon',
                        Place::STATUS_PUBLISHED => 'Publié',
                        Place::STATUS_ARCHIVED => 'Archivé',
                    ]),
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        Place::SOURCE_ADMIN => 'Curation admin',
                        Place::SOURCE_OPENDATA => 'OpenData',
                        Place::SOURCE_CONTRIBUTION => 'Contribution',
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
            'index' => Pages\ListPlaces::route('/'),
            'create' => Pages\CreatePlace::route('/create'),
            'edit' => Pages\EditPlace::route('/{record}/edit'),
        ];
    }
}
