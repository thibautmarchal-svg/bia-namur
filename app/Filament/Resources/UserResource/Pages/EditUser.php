<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Synchronise email_verified_at depuis le toggle "Email vérifié" du form.
     * Et garde-fou : empeche un admin de se rétrograder lui-même via le form.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Le toggle 'email_verified' est virtuel (pas une colonne) → on le
        // traduit en email_verified_at puis on le retire du payload avant save.
        $verified = (bool) ($data['email_verified'] ?? false);
        if ($verified && $this->record->email_verified_at === null) {
            $data['email_verified_at'] = now();
        } elseif (! $verified && $this->record->email_verified_at !== null) {
            $data['email_verified_at'] = null;
        }
        unset($data['email_verified']);

        // Anti self-lockout : on a deja desactive le select pour soi-meme,
        // mais on double-check au cas ou un payload manipule arriverait.
        if ($this->record->id === auth()->id()) {
            $data['role'] = $this->record->role;
        }

        // Last admin check : si on rétrograde un admin, vérifier qu'il en reste un
        if (
            $this->record->role === User::ROLE_ADMIN
            && ($data['role'] ?? null) !== User::ROLE_ADMIN
        ) {
            $remaining = User::where('role', User::ROLE_ADMIN)
                ->where('id', '!=', $this->record->id)
                ->count();
            if ($remaining === 0) {
                Notification::make()
                    ->title('Impossible')
                    ->body('Il faut au moins un admin restant. Nomme quelqu\'un d\'autre admin avant.')
                    ->danger()
                    ->send();
                $data['role'] = User::ROLE_ADMIN;    // revert
            }
        }

        return $data;
    }
}
