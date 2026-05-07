<?php

namespace App\Filament\Resources\PlaceResource\Pages;

use App\Filament\Resources\PlaceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlace extends EditRecord
{
    protected static string $resource = PlaceResource::class;

    /** Path du fichier temp Filament (uploads/places/_temp/xxx.jpg) capturé avant save. */
    protected ?string $pendingPhotoPath = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Filament passe ici tous les champs du form avant de save l'Eloquent.
     * On extrait cover_photo_upload (qui n'est pas une colonne BDD) et on
     * le mémorise pour afterSave().
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingPhotoPath = $data['cover_photo_upload'] ?? null;
        unset($data['cover_photo_upload']);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->pendingPhotoPath) {
            PlaceResource::processCoverPhotoUpload($this->pendingPhotoPath, $this->record);
            $this->refreshFormData(['cover_photo_id', 'cover_photo_upload']);
        }
    }
}
