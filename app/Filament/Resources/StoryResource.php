<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoryResource\Pages;
use App\Models\City;
use App\Models\Photo;
use App\Models\Story;
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
                            ->default(fn () => City::where('slug', 'namur')->value('id'))
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

                Forms\Components\Hidden::make('cover_photo_id'),

                Forms\Components\Section::make('Photo de couverture')
                    ->description('Indispensable pour publier — pas de story sans photo.')
                    ->schema([
                        Forms\Components\Placeholder::make('current_cover')
                            ->label('Photo actuelle')
                            ->content(function (?Story $record) {
                                if (! $record || ! $record->cover_photo_id) {
                                    return new HtmlString('<em style="color:#8B7E72">Aucune photo. La story ne pourra pas etre publiée sans photo.</em>');
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
                            ->directory('uploads/stories/_temp')
                            ->disk('public')
                            ->imageEditor()
                            ->imageEditorAspectRatios(['16:9', '4:3', '1:1'])
                            ->helperText('JPG / PNG / WebP, 5 Mo max. EXIF (geoloc, appareil) auto supprimés. La photo est traitée à l\'enregistrement.')
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
                            ->helperText('Pas de passage en "Publié" sans photo de couverture.')
                            ->rule(function (callable $get) {
                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                    if ($value === Story::STATUS_PUBLISHED) {
                                        $coverId = $get('cover_photo_id');
                                        $upload = $get('cover_photo_upload');
                                        if (empty($coverId) && empty($upload)) {
                                            $fail('Impossible de publier sans photo de couverture. Ajoute une photo dans la section "Photo de couverture" plus haut.');
                                        }
                                    }
                                };
                            })
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

    /**
     * Handler appele apres upload du Filament FileUpload : transfere le
     * fichier temp vers PhotoUploadService (strip EXIF + resize) puis
     * pose cover_photo_id sur le record.
     */
    public static function processCoverPhotoUpload(?string $tempPath, Story $record): void
    {
        if (empty($tempPath)) {
            return;
        }
        self::handleCoverPhotoUpload($tempPath, $record, fn () => null);
    }

    protected static function handleCoverPhotoUpload($state, Story $record, callable $set): void
    {
        $tempPath = is_array($state) ? array_values($state)[0] ?? null : $state;
        if (! $tempPath) {
            return;
        }

        $absoluteTemp = Storage::disk('public')->path($tempPath);
        if (! is_file($absoluteTemp)) {
            return;
        }

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
