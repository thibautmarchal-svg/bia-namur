<?php

namespace App\Services\Media;

use App\Models\Photo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Pipeline d'upload de photo contribution / admin.
 *
 * Securite (cf. agent security-namur) :
 *   - Whitelist MIME stricte (magic bytes via getimagesize, pas le
 *     Content-Type du header HTTP qui ment).
 *   - Strip EXIF systematique : GD ne copie pas les EXIF en
 *     re-encodant l'image, donc imagecreatefromjpeg + imagejpeg
 *     enleve la geoloc cachee, l'auteur, le serial appareil, etc.
 *   - Resize max 1600px (cf. brief §5 stockage R2 variantes).
 *   - Nom de fichier UUID — JAMAIS le nom donne par l'user
 *     (path traversal, caracteres speciaux).
 *
 * En S1/S2 : storage local 'public' (storage/app/public, expose
 * via /storage URL). En S3+ : disk 'r2' (Cloudflare R2 S3-compatible)
 * configure dans config/filesystems.php — switch via env FILESYSTEM_DISK.
 */
class PhotoUploadService
{
    public const MAX_WIDTH = 1600;

    public const JPEG_QUALITY = 82;

    /** @var array<string, string> mime → extension validee */
    public const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Stocke un upload, strip EXIF + resize, cree un Photo polymorphique
     * lie au model passe en parametre.
     */
    public function storeFor(UploadedFile $file, Model $uploadable, ?int $uploadedBy = null, ?string $credit = null): Photo
    {
        $info = $this->validateImage($file);

        $relativeDir = $this->dirFor($uploadable);
        Storage::disk('public')->makeDirectory($relativeDir);

        $filename = Str::uuid()->toString() . '.' . $info['ext'];
        $relativePath = "{$relativeDir}/{$filename}";
        $absolutePath = Storage::disk('public')->path($relativePath);

        $this->processAndSave($file, $absolutePath, $info);

        // Re-mesure les dimensions apres resize
        [$width, $height] = getimagesize($absolutePath);
        $size = filesize($absolutePath);

        return Photo::create([
            'uploadable_type' => $uploadable->getMorphClass(),
            'uploadable_id' => $uploadable->getKey(),
            'filename' => $filename,
            'path' => $relativePath,
            'disk' => 'public',
            'mime_type' => $info['mime'],
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'variants' => null,    // Variantes 800/1600 generees en S3 par PhotoVariantsJob
            'uploaded_by' => $uploadedBy,
            'license' => $credit !== null ? 'all_rights_reserved' : null,
            'credit' => $credit,
        ]);
    }

    /**
     * Verifie que le fichier est bien une image dont le format est dans
     * la whitelist. Utilise getimagesize() qui lit les magic bytes
     * (pas le mime_type du header HTTP qui ment).
     */
    protected function validateImage(UploadedFile $file): array
    {
        $size = getimagesize($file->getRealPath());
        if ($size === false) {
            throw new RuntimeException('Le fichier n\'est pas une image valide.');
        }

        $mime = $size['mime'] ?? '';
        if (! isset(self::ALLOWED_MIMES[$mime])) {
            throw new RuntimeException("Format non autorisé : {$mime}.");
        }

        return [
            'mime' => $mime,
            'ext' => self::ALLOWED_MIMES[$mime],
            'width' => $size[0],
            'height' => $size[1],
        ];
    }

    protected function processAndSave(UploadedFile $file, string $destination, array $info): void
    {
        $sourcePath = $file->getRealPath();

        // Charger via GD selon le mime — GD ne copie pas les EXIF
        $image = match ($info['mime']) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($sourcePath),
            'image/png' => imagecreatefrompng($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
        };

        if ($image === false) {
            throw new RuntimeException('Impossible de décoder l\'image.');
        }

        // Resize si plus large que MAX_WIDTH
        if ($info['width'] > self::MAX_WIDTH) {
            $resized = imagescale($image, self::MAX_WIDTH);
            imagedestroy($image);
            $image = $resized;
        }

        // Re-encoder en JPEG pour normaliser (sans EXIF, qualite controlee)
        $written = match ($info['ext']) {
            'jpg' => imagejpeg($image, $destination, self::JPEG_QUALITY),
            'png' => imagepng($image, $destination, 9),
            'webp' => imagewebp($image, $destination, self::JPEG_QUALITY),
            default => false,
        };

        imagedestroy($image);

        if ($written === false) {
            throw new RuntimeException('Echec sauvegarde de l\'image.');
        }
    }

    protected function dirFor(Model $uploadable): string
    {
        $morph = Str::lower(class_basename($uploadable));    // contribution / place / story
        $id = $uploadable->getKey();

        return "uploads/{$morph}s/{$id}";
    }

    /**
     * Re-attache une photo a un autre model (utilise quand admin approve
     * une contribution → la photo "déménage" vers le Place créé).
     */
    public function reattachTo(Photo $photo, Model $newOwner): Photo
    {
        $photo->update([
            'uploadable_type' => $newOwner->getMorphClass(),
            'uploadable_id' => $newOwner->getKey(),
        ]);

        return $photo;
    }
}
