<?php

namespace App\Filament\Resources\BriefResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Sélections du brief';

    protected static ?string $modelLabel = 'sélection';

    protected static ?string $pluralModelLabel = 'sélections';

    protected static ?string $recordTitleAttribute = 'position';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('position')
                            ->label('Position')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(20)
                            ->required(),
                        Forms\Components\Select::make('event_id')
                            ->label('Événement lié')
                            ->relationship('event', 'title')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('place_id')
                            ->label('Lieu lié')
                            ->relationship('place', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('ai_text')
                            ->label('Texte généré (IA)')
                            ->rows(6)
                            ->helperText('Texte produit par Claude. Laisser tel quel ou éditer dans le champ ci-dessous.')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('edited_text')
                            ->label('Texte édité (override admin)')
                            ->rows(6)
                            ->helperText('Si rempli, c\'est ce texte qui s\'affiche en public. Sinon le texte IA est utilisé.')
                            ->columnSpanFull(),
                        Forms\Components\KeyValue::make('reasoning')
                            ->label('Reasoning IA (lieu, horaire, justification)')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('position')
            ->reorderable('position')
            ->defaultSort('position')
            ->columns([
                Tables\Columns\TextColumn::make('position')
                    ->label('#')
                    ->sortable()
                    ->width('48px')
                    ->weight('bold')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('event.title')
                    ->label('Événement')
                    ->placeholder('—')
                    ->limit(40),
                Tables\Columns\TextColumn::make('place.name')
                    ->label('Lieu')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('display_text')
                    ->label('Texte')
                    ->getStateUsing(fn ($record) => Str::limit(
                        strip_tags(str_replace(['**', '_'], '', $record->edited_text ?: $record->ai_text ?: '')),
                        100
                    ))
                    ->wrap()
                    ->extraAttributes(['class' => 'font-serif']),
                Tables\Columns\IconColumn::make('edited_text')
                    ->label('Édité')
                    ->boolean()
                    ->getStateUsing(fn ($record) => ! empty($record->edited_text))
                    ->trueIcon('heroicon-o-pencil-square')
                    ->falseIcon('heroicon-o-sparkles')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => $record->edited_text ? 'Édité par admin' : 'Texte IA brut'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajouter une sélection'),
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
}
