<?php

namespace App\Filament\Resources\PlaceResource\Pages;

use App\Filament\Resources\PlaceResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlace extends CreateRecord
{
    protected static string $resource = PlaceResource::class;

    protected ?string $pendingPhotoPath = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingPhotoPath = $data['cover_photo_upload'] ?? null;
        unset($data['cover_photo_upload']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->pendingPhotoPath) {
            PlaceResource::processCoverPhotoUpload($this->pendingPhotoPath, $this->record);
        }
    }
}
