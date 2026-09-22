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

        // Un PNG/GIF indexe donne une image a palette, qu'imagewebp() refuse
        // (« Paletter image not supported by webp »). La conversion garde l'alpha.
        if (! imageistruecolor($source)) {
            imagepalettetotruecolor($source);
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
        try {
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
        } catch (\Throwable $e) {
            // Laravel transforme les avertissements GD en exception : une image
            // exotique est ignoree, elle n'interrompt pas la commande de rattrapage.
            ob_end_clean();
            imagedestroy($source);
            \Log::warning('UploadedImageOptimizer: encodage impossible', [
                'path' => $absolutePath,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
        $binary = (string) ob_get_clean();
        imagedestroy($source);

        return $binary === '' ? null : [$binary, $extension];
    }

    /**
     * Declinaisons responsive du poster du hero, calees sur l'affichage reel :
     * mobile recadre en portrait (768x900), tablette 1280x720, bureau a la
     * taille source (plafond 1920). Retourne les fichiers ecrits avec leurs
     * dimensions, du plus petit au plus grand, ou [] sans GD.
     *
     * @return list<array{path: string, width: int, height: int}>
     */
    public function storeResponsiveSet(string $absolutePath, string $directory, string $disk = 'public'): array
    {
        if (! function_exists('imagecreatefromstring') || ! is_file($absolutePath)) {
            return [];
        }

        $info = @getimagesize($absolutePath);
        if (! is_array($info) || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return [];
        }
        if ($info[2] === IMAGETYPE_WEBP && ! function_exists('imagecreatefromwebp')) {
            return [];
        }

        $source = @imagecreatefromstring((string) file_get_contents($absolutePath));
        if ($source === false) {
            return [];
        }
        if ($info[2] === IMAGETYPE_JPEG) {
            $source = $this->applyExifOrientation($source, $absolutePath);
        }

        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);
        $storage = Storage::disk($disk);
        $base = trim($directory, '/') . '/' . Str::random(40);
        $extension = function_exists('imagewebp') ? 'webp' : 'jpg';

        $specs = [
            ['mobile', 768, 900, true],
            ['tablet', 1280, 720, true],
            ['desktop', min($srcWidth, 1920), (int) round($srcHeight * min($srcWidth, 1920) / $srcWidth), false],
        ];

        $written = [];
        foreach ($specs as [$label, $width, $height, $crop]) {
            if ($crop && ($width > $srcWidth * 1.5 || $height > $srcHeight * 1.5)) {
                continue; // source trop petite pour un recadrage propre
            }

            $canvas = imagecreatetruecolor($width, $height);
            if ($crop) {
                // Recadrage "cover" centre, comme object-fit: cover a l'affichage.
                $ratio = max($width / $srcWidth, $height / $srcHeight);
                $cropWidth = (int) round($width / $ratio);
                $cropHeight = (int) round($height / $ratio);
                $left = (int) floor(($srcWidth - $cropWidth) / 2);
                $top = (int) floor(($srcHeight - $cropHeight) / 2);
                imagecopyresampled($canvas, $source, 0, 0, $left, $top, $width, $height, $cropWidth, $cropHeight);
            } else {
                imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);
            }

            ob_start();
            $extension === 'webp' ? imagewebp($canvas, null, 78) : imagejpeg($canvas, null, 80);
            $binary = (string) ob_get_clean();
            imagedestroy($canvas);

            if ($binary === '') {
                continue;
            }

            $path = sprintf('%s-%s-%dx%d.%s', $base, $label, $width, $height, $extension);
            if ($storage->put($path, $binary)) {
                $written[] = ['path' => $path, 'width' => $width, 'height' => $height];
            }
        }

        imagedestroy($source);

        return $written;
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
