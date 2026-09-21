<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Redimensionne et re-encode les images destinees au front public : largeur
 * plafonnee, WebP des que GD le permet, sinon JPEG/PNG re-encode. Les fichiers
 * qui ne sont pas des images matricielles (SVG, GIF, video) sont stockes tels quels.
 *
 * Sans cette etape, les photos de la home partaient en 4000 px / 2 Mo vers un
 * emplacement affiche en 600 px, ce qui plombait le LCP mobile.
 */
class UploadedImageOptimizer
{
    public const MAX_WIDTH_DEFAULT = 1600;

    private const QUALITY = 82;

    /** Au-dela, GD depasserait la memoire PHP d'un hebergement mutualise. */
    private const MAX_PIXELS = 40_000_000;

    public function storeUploaded(UploadedFile $file, string $directory, int $maxWidth = self::MAX_WIDTH_DEFAULT, string $disk = 'public'): ?string
    {
        $encoded = $this->encode((string) $file->getRealPath(), $maxWidth);

        if ($encoded === null) {
            $stored = $file->store($directory, $disk);

            return is_string($stored) && $stored !== '' ? $stored : null;
        }

        [$binary, $extension] = $encoded;
        $path = trim($directory, '/') . '/' . Str::random(40) . '.' . $extension;

        return Storage::disk($disk)->put($path, $binary) ? $path : null;
    }

    /**
     * Optimise un fichier deja present sur le disque. Retourne le nouveau chemin,
     * ou null quand le fichier est inchange (format non gere, gain inferieur a 10 %).
     */
    public function optimizeStored(string $path, int $maxWidth = self::MAX_WIDTH_DEFAULT, string $disk = 'public'): ?string
    {
        $storage = Storage::disk($disk);
        $path = ltrim($path, '/');

        if ($path === '' || ! $storage->exists($path)) {
            return null;
        }

        $absolute = $storage->path($path);
        $encoded = $this->encode($absolute, $maxWidth);

        if ($encoded === null) {
            return null;
        }

        [$binary, $extension] = $encoded;
        $currentSize = (int) filesize($absolute);

        if ($currentSize > 0 && strlen($binary) > $currentSize * 0.9) {
            return null;
        }

        $base = (string) preg_replace('/\.[a-z0-9]+$/i', '', $path);
        $newPath = $base . '.' . $extension;
        if ($newPath === $path) {
            $newPath = $base . '-opt.' . $extension;
        }

        return $storage->put($newPath, $binary) ? $newPath : null;
    }

    /**
     * @return array{0: string, 1: string}|null  [binaire, extension]
     */
    public function encode(string $absolutePath, int $maxWidth): ?array
    {
        if (! function_exists('imagecreatefromstring') || $absolutePath === '' || ! is_file($absolutePath)) {
            return null;
        }

        $info = @getimagesize($absolutePath);
        if (! is_array($info)) {
            return null;
        }

        [$width, $height, $type] = $info;
        $width = (int) $width;
        $height = (int) $height;

        if ($width < 1 || $height < 1 || $width * $height > self::MAX_PIXELS) {
            return null;
        }

        if (! in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return null;
        }

        if ($type === IMAGETYPE_WEBP && ! function_exists('imagecreatefromwebp')) {
            return null;
        }

        $raw = (string) file_get_contents($absolutePath);
        $source = @imagecreatefromstring($raw);
        if ($source === false) {
            return null;
        }

        $hasAlpha = $type === IMAGETYPE_PNG ? $this->pngHasAlpha($raw) : $type === IMAGETYPE_WEBP;

        if ($type === IMAGETYPE_JPEG) {
            $source = $this->applyExifOrientation($source, $absolutePath);
            $width = imagesx($source);
            $height = imagesy($source);
        }

        $targetWidth = max(1, min($width, $maxWidth));
        $targetHeight = max(1, (int) round($height * $targetWidth / $width));

        if ($targetWidth !== $width) {
            $resized = imagecreatetruecolor($targetWidth, $targetHeight);
            if ($hasAlpha) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
            imagedestroy($source);
            $source = $resized;
        } elseif ($hasAlpha) {
            imagealphablending($source, false);
            imagesavealpha($source, true);
        }

        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($source, null, self::QUALITY);
            $extension = 'webp';
        } elseif ($hasAlpha) {
            imagepng($source, null, 8);
            $extension = 'png';
        } else {
            imagejpeg($source, null, self::QUALITY);
            $extension = 'jpg';
        }
        $binary = (string) ob_get_clean();
        imagedestroy($source);

        return $binary === '' ? null : [$binary, $extension];
    }

    /** Lit le type de couleur IHDR et la presence d'un chunk tRNS, sans parcourir les pixels. */
    private function pngHasAlpha(string $raw): bool
    {
        if (strlen($raw) < 26) {
            return true;
        }

        $colorType = ord($raw[25]);

        return in_array($colorType, [4, 6], true) || str_contains(substr($raw, 0, 4096), 'tRNS');
    }

    /** Les photos de telephone portent leur rotation en EXIF ; GD l'ignore. */
    private function applyExifOrientation(\GdImage $image, string $absolutePath)
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($absolutePath);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => false,
        };

        if ($rotated === false) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }
}
