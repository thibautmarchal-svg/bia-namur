<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContributionResource\Pages;
use App\Models\City;
use App\Models\Contribution;
use App\Models\Photo;
use App\Models\Place;
use App\Services\Media\PhotoUploadService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

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
                        Forms\Components\Placeholder::make('photo_preview')
                            ->label('Photo soumise')
                            ->content(function (?Contribution $record) {
                                if (! $record) {
                                    return 'Aucune (création en cours)';
                                }
                                $photo = Photo::where('uploadable_type', $record->getMorphClass())
                                    ->where('uploadable_id', $record->id)
                                    ->first();
                                if (! $photo) {
                                    return new HtmlString('<em style="color:#8B7E72">Aucune photo soumise</em>');
                                }

                                return new HtmlString(sprintf(
                                    '<img src="%s" alt="Photo soumise" style="max-width:480px;max-height:320px;border-radius:.75rem;border:1px solid #E8DDC5;" />',
                                    asset('storage/' . $photo->path),
                                ));
                            })
                            ->columnSpanFull(),
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
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (Contribution $record) => in_array(
                        $record->status,
                        [Contribution::STATUS_PENDING, Contribution::STATUS_MANUAL_REVIEW, Contribution::STATUS_AUTO_APPROVED],
                        true,
                    ))
                    ->requiresConfirmation()
                    ->modalHeading('Approuver cette contribution ?')
                    ->modalDescription('Crée une fiche lieu en brouillon depuis le payload. Tu pourras la peaufiner avant publication.')
                    ->modalSubmitActionLabel('Créer le lieu en brouillon')
                    ->action(function (Contribution $record): void {
                        self::approveAndCreatePlace($record);
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (Contribution $record) => $record->status !== Contribution::STATUS_REJECTED
                        && $record->status !== Contribution::STATUS_MERGED)
                    ->form([
                        Forms\Components\Textarea::make('reviewer_notes')
                            ->label('Note (optionnelle, visible en interne)')
                            ->rows(3),
                    ])
                    ->action(function (Contribution $record, array $data): void {
                        $record->update([
                            'status' => Contribution::STATUS_REJECTED,
                            'reviewer_notes' => $data['reviewer_notes'] ?? null,
                            'reviewer_id' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                        Notification::make()
                            ->title('Contribution rejetée')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->label('Détails'),
            ]);
    }

    /**
     * Convertit une contribution approuvee en Place draft, lie les deux,
     * marque la contribution comme 'merged'.
     */
    protected static function approveAndCreatePlace(Contribution $contribution): void
    {
        $payload = $contribution->payload ?? [];

        $namur = City::where('slug', 'namur')->first();
        if (! $namur) {
            Notification::make()
                ->title('Ville Namur introuvable')
                ->danger()
                ->send();

            return;
        }

        $name = $payload['name'] ?? 'Lieu sans nom';
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $i = 1;
        while (Place::where('city_id', $namur->id)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . (++$i);
        }

        $place = Place::create([
            'city_id' => $namur->id,
            'slug' => $slug,
            'name' => $name,
            'type' => $payload['type'] ?? 'hidden_gem',
            'description' => Str::limit($payload['description'] ?? '', 480),
            'address' => $payload['address'] ?? null,
            'neighborhood' => $payload['neighborhood'] ?? null,
            'tags' => [],
            'source' => Place::SOURCE_CONTRIBUTION,
            'status' => Place::STATUS_DRAFT,
        ]);

        // Si une photo est attachée à la contribution, on la déménage
        // vers le Place cree (uploadable_* update) et on la pose en cover
        $photo = Photo::where('uploadable_type', $contribution->getMorphClass())
            ->where('uploadable_id', $contribution->id)
            ->first();
        if ($photo) {
            app(PhotoUploadService::class)->reattachTo($photo, $place);
            $place->update(['cover_photo_id' => $photo->id]);
        }

        $contribution->update([
            'status' => Contribution::STATUS_MERGED,
            'target_place_id' => $place->id,
            'reviewer_id' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        Notification::make()
            ->title('Lieu créé en brouillon')
            ->body("« {$name} » est prêt à être édité.")
            ->success()
            ->actions([
                Action::make('open')
                    ->label('Ouvrir')
                    ->url(PlaceResource::getUrl('edit', ['record' => $place])),
            ])
            ->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContributions::route('/'),
            'edit' => Pages\EditContribution::route('/{record}/edit'),
        ];
    }
}
