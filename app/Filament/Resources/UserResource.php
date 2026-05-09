<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Mail\MagicLinkMail;
use App\Models\MagicLink;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Communauté';

    protected static ?string $navigationLabel = 'Utilisateurs';

    protected static ?string $modelLabel = 'utilisateur';

    protected static ?string $pluralModelLabel = 'utilisateurs';

    protected static ?int $navigationSort = 5;

    /** Seul un admin peut acceder a la gestion des utilisateurs. */
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() === true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identité')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Toggle::make('email_verified')
                            ->label('Email vérifié')
                            ->afterStateHydrated(fn (Forms\Components\Toggle $component, ?User $record) => $component->state($record?->email_verified_at !== null))
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Rôle et permissions')
                    ->description('Le rôle détermine l\'accès à l\'admin Filament. Modérateur+ : accès admin. Admin : peut nommer d\'autres admins.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label('Rôle')
                            ->options([
                                User::ROLE_MEMBER => 'Membre',
                                User::ROLE_MODERATOR => 'Modérateur',
                                User::ROLE_ADMIN => 'Admin',
                            ])
                            ->required()
                            ->disabled(fn (?User $record) => $record && $record->id === auth()->id())
                            ->helperText(function (?User $record) {
                                if ($record && $record->id === auth()->id()) {
                                    return 'Tu ne peux pas changer ton propre rôle (anti-lockout).';
                                }

                                return 'Modérateur : voit le panneau admin, modère les contributions. Admin : peut tout faire + gérer les utilisateurs.';
                            }),
                        Forms\Components\Select::make('subscription_tier')
                            ->label('Tier abonnement')
                            ->options([
                                User::TIER_FREE => 'Gratuit',
                                User::TIER_PLUS => 'Bia +',
                                User::TIER_PATRON => 'Patron',
                            ])
                            ->required()
                            ->helperText('Modifié manuellement uniquement. À terme géré par Stripe.'),
                    ]),

                Forms\Components\Section::make('Métadonnées')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('locale')
                            ->label('Langue')
                            ->maxLength(8),
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Email vérifié le')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('subscription_started_at')
                            ->label('Abonnement débuté le')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('subscription_renews_at')
                            ->label('Renouvellement le')
                            ->disabled(),
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
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Rôle')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        User::ROLE_ADMIN => 'Admin',
                        User::ROLE_MODERATOR => 'Modérateur',
                        default => 'Membre',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        User::ROLE_ADMIN => 'danger',
                        User::ROLE_MODERATOR => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('subscription_tier')
                    ->label('Tier')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Vérifié')
                    ->boolean()
                    ->toggleable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('contributions_count')
                    ->label('Contribs')
                    ->counts('contributions')
                    ->badge()
                    ->color('primary')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Inscrit')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        User::ROLE_MEMBER => 'Membres',
                        User::ROLE_MODERATOR => 'Modérateurs',
                        User::ROLE_ADMIN => 'Admins',
                    ]),
                Tables\Filters\SelectFilter::make('subscription_tier')
                    ->label('Tier')
                    ->options([
                        User::TIER_FREE => 'Gratuit',
                        User::TIER_PLUS => 'Bia +',
                        User::TIER_PATRON => 'Patron',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('promote_admin')
                    ->label('Nommer admin')
                    ->icon('heroicon-m-shield-check')
                    ->color('danger')
                    ->visible(fn (User $record) => $record->role !== User::ROLE_ADMIN
                        && $record->id !== auth()->id())
                    ->requiresConfirmation()
                    ->modalHeading('Nommer cette personne admin ?')
                    ->modalDescription('Un admin peut tout faire : modérer, gérer les utilisateurs, nommer d\'autres admins. À ne donner qu\'à des personnes de confiance.')
                    ->modalSubmitActionLabel('Nommer admin')
                    ->action(function (User $record): void {
                        $record->update(['role' => User::ROLE_ADMIN]);
                        Notification::make()
                            ->title("« {$record->name} » est maintenant admin.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('demote')
                    ->label('Rétrograder')
                    ->icon('heroicon-m-arrow-down-circle')
                    ->color('warning')
                    ->visible(fn (User $record) => $record->role !== User::ROLE_MEMBER
                        && $record->id !== auth()->id())
                    ->requiresConfirmation()
                    ->modalHeading('Rétrograder en membre ?')
                    ->modalDescription('Cette personne perdra l\'accès au panneau admin.')
                    ->action(function (User $record): void {
                        // Garde-fou : il faut au moins 1 admin restant
                        if ($record->role === User::ROLE_ADMIN) {
                            $remainingAdmins = User::where('role', User::ROLE_ADMIN)
                                ->where('id', '!=', $record->id)
                                ->count();
                            if ($remainingAdmins === 0) {
                                Notification::make()
                                    ->title('Impossible de rétrograder')
                                    ->body('Il faut au moins un admin actif. Nomme quelqu\'un d\'autre admin avant.')
                                    ->danger()
                                    ->send();

                                return;
                            }
                        }
                        $record->update(['role' => User::ROLE_MEMBER]);
                        Notification::make()
                            ->title("« {$record->name} » est rétrogradé membre.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('send_magic_link')
                    ->label('Envoyer magic link')
                    ->icon('heroicon-m-envelope')
                    ->color('primary')
                    ->action(function (User $record): void {
                        [$rawToken, $tokenHash] = MagicLink::generateToken();

                        MagicLink::create([
                            'user_id' => $record->id,
                            'email' => $record->email,
                            'token_hash' => $tokenHash,
                            'expires_at' => now()->addMinutes(MagicLinkController::EXPIRES_MINUTES),
                            'requested_ip' => request()->ip(),
                            'requested_user_agent' => substr((string) request()->userAgent(), 0, 255),
                        ]);

                        try {
                            Mail::to($record->email)->send(new MagicLinkMail($rawToken, $record->name));
                            Notification::make()
                                ->title('Magic link envoyé')
                                ->body("Un lien de connexion a été envoyé à {$record->email}.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Échec de l\'envoi')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\EditAction::make()->label('Détails'),
                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer')
                    ->visible(fn (User $record) => $record->id !== auth()->id())
                    ->before(function (User $record, Tables\Actions\DeleteAction $action) {
                        if ($record->role === User::ROLE_ADMIN) {
                            $remaining = User::where('role', User::ROLE_ADMIN)
                                ->where('id', '!=', $record->id)
                                ->count();
                            if ($remaining === 0) {
                                Notification::make()
                                    ->title('Impossible de supprimer le dernier admin')
                                    ->danger()
                                    ->send();
                                $action->cancel();
                            }
                        }
                    }),
                Tables\Actions\RestoreAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
