<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlaceResource\Pages;
use App\Models\City;
use App\Models\Photo;
use App\Models\Place;
use App\Services\Media\PhotoUploadService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

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

                Forms\Components\Hidden::make('cover_photo_id'),

                Forms\Components\Section::make('Photo de couverture')
                    ->description('Indispensable pour publier — pas de fiche sans photo.')
                    ->schema([
                        Forms\Components\Placeholder::make('current_cover')
                            ->label('Photo actuelle')
                            ->content(function (?Place $record) {
                                if (! $record || ! $record->cover_photo_id) {
                                    return new HtmlString('<em style="color:#8B7E72">Aucune photo. Le lieu ne pourra pas etre publié sans photo de couverture.</em>');
                                }
                                $photo = $record->coverPhoto;
                                if (! $photo) {
                                    return 'Photo référencée mais introuvable.';
                                }

                                return new HtmlString(sprintf(
                                    '<img src="%s" alt="" style="max-width:480px;max-height:280px;border-radius:.75rem;border:1px solid #E8DDC5;" />',
                                    asset('storage/' . $photo->path),
                                ));
                            })
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('cover_photo_upload')
                            ->label('Remplacer / ajouter une photo')
                            ->image()
                            ->maxSize(5120)
                            ->directory('uploads/places/_temp')
                            ->disk('public')
                            ->imageEditor()
                            ->imageEditorAspectRatios(['16:9', '4:3', '1:1'])
                            ->helperText('JPG / PNG / WebP, 5 Mo max. EXIF (geoloc, appareil) auto supprimés.')
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, ?Place $record, callable $set) {
                                if (! $state || ! $record) {
                                    return;
                                }
                                self::handleCoverPhotoUpload($state, $record, $set);
                            })
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
                            ->required()
                            ->helperText('Pas de passage en "Publié" sans photo de couverture.')
                            ->rule(function (callable $get) {
                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                    if ($value === Place::STATUS_PUBLISHED) {
                                        $coverId = $get('cover_photo_id');
                                        if (empty($coverId)) {
                                            $fail('Impossible de publier sans photo de couverture. Ajoute une photo dans la section "Photo de couverture" plus haut.');
                                        }
                                    }
                                };
                            }),
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

    /**
     * Handler appele apres upload du Filament FileUpload : transfere le
     * fichier temp vers PhotoUploadService (strip EXIF + resize) puis
     * pose cover_photo_id sur le record.
     */
    protected static function handleCoverPhotoUpload($state, Place $record, callable $set): void
    {
        // $state peut etre un array (multiple) ou un string (single) selon Filament version
        $tempPath = is_array($state) ? array_values($state)[0] ?? null : $state;
        if (! $tempPath) {
            return;
        }

        $absoluteTemp = Storage::disk('public')->path($tempPath);
        if (! is_file($absoluteTemp)) {
            return;
        }

        // Reconstruit un UploadedFile a partir du fichier temp pour passer
        // au pipeline secure existant
        $uploaded = new UploadedFile(
            path: $absoluteTemp,
            originalName: basename($absoluteTemp),
            mimeType: mime_content_type($absoluteTemp) ?: 'application/octet-stream',
            test: true,
        );

        try {
            $photo = app(PhotoUploadService::class)->storeFor(
                file: $uploaded,
                uploadable: $record,
                uploadedBy: auth()->id(),
                credit: null,
            );

            // Supprime l'ancienne cover si elle existe
            if ($record->cover_photo_id && $record->cover_photo_id !== $photo->id) {
                $oldPhoto = Photo::find($record->cover_photo_id);
                if ($oldPhoto) {
                    Storage::disk($oldPhoto->disk)->delete($oldPhoto->path);
                    $oldPhoto->delete();
                }
            }

            $record->cover_photo_id = $photo->id;
            $record->save();
            $set('cover_photo_id', $photo->id);

            // Cleanup du temp Filament
            Storage::disk('public')->delete($tempPath);

            Notification::make()
                ->title('Photo de couverture mise à jour')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Échec upload photo')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
