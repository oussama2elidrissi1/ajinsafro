<?php

namespace App\Services\Wp;

use App\Models\Wp\WpPost;
use App\Models\Wp\WpPostMeta;
use App\Services\UploadedImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class WpHeroImageService
{
    /**
     * Max file size in bytes (5MB).
     */
    public const MAX_FILE_SIZE = 5 * 1024 * 1024;

    /**
     * Allowed mime types for hero image.
     */
    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Largeur maximale conservee dans wp-content/uploads : au-dela, le front
     * telecharge des pixels qu'il n'affiche jamais (cartes voyage en 768 px).
     */
    private const MAX_STORED_WIDTH = 1600;

    /** Tailles intermediaires WordPress par defaut, celles dont srcset a besoin. */
    private const INTERMEDIATE_SIZES = ['medium' => 300, 'medium_large' => 768, 'large' => 1024];

    private const MIME_BY_EXTENSION = ['webp' => 'image/webp', 'jpg' => 'image/jpeg', 'png' => 'image/png'];

    /**
     * Store uploaded file in WP uploads folder and create attachment post.
     * Returns attachment ID or throws on failure.
     *
     * @param UploadedFile $file
     * @param int $tourId Tour post ID (for post_parent if needed)
     * @return array{attachment_id: int, relative_path: string}
     */
    public function storeUploadAndCreateAttachment(UploadedFile $file, int $tourId = 0): array
    {
        $basePath = config('wordpress.uploads_path');
        if (empty($basePath) || !is_dir($basePath)) {
            \Log::error('WpHeroImageService: WP uploads path not configured or missing', ['path' => $basePath ?? 'null']);
            throw new \RuntimeException('Dossier des uploads WordPress non configuré ou introuvable. Définissez WP_UPLOADS_PATH dans .env.');
        }

        // Read mime and title before move() — after move the temp file is gone and getMimeType() can fail
        $mime = $file->getMimeType();
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension();
        $ext = $extension ?: 'jpg';

        // Les visuels arrivent en 2000 px / 3 Mo pour un affichage en 768 px : on
        // redimensionne et re-encode (WebP des que GD le permet) avant d'ecrire.
        // Sans GD, $optimized vaut null et le fichier d'origine est deplace tel quel.
        $optimized = app(UploadedImageOptimizer::class)->encode((string) $file->getRealPath(), self::MAX_STORED_WIDTH);
        if ($optimized !== null) {
            [$optimizedBinary, $ext] = $optimized;
            $mime = self::MIME_BY_EXTENSION[$ext] ?? $mime;
        }

        // Nom unique : hero-{tourId}-{timestamp}.{ext}
        $filename = 'hero-' . $tourId . '-' . time() . '.' . $ext;
        $relativePath = date('Y/m') . '/' . $filename;
        $fullPath = rtrim($basePath, '/') . '/' . $relativePath;

        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                \Log::error('WpHeroImageService: impossible de créer le dossier uploads', ['dir' => $dir, 'tour_id' => $tourId]);
                throw new \RuntimeException('Impossible de créer le dossier des uploads. Vérifiez les droits sur wp-content/uploads.');
            }
        }

        $written = $optimized !== null
            ? @file_put_contents($fullPath, $optimizedBinary) !== false
            : (bool) $file->move($dir, $filename);
        if (!$written) {
            \Log::error('WpHeroImageService: échec écriture fichier', ['fullPath' => $fullPath, 'tour_id' => $tourId]);
            throw new \RuntimeException('Impossible d\'enregistrer le fichier dans les uploads WordPress. Vérifiez les droits du dossier.');
        }
        if (!is_file($fullPath) || !is_readable($fullPath)) {
            \Log::error('WpHeroImageService: fichier manquant après move()', ['fullPath' => $fullPath, 'tour_id' => $tourId]);
            throw new \RuntimeException('Upload WP échoué: fichier introuvable après écriture.');
        }
        $baseUploadsUrl = self::getUploadsBaseUrl();
        $guid = rtrim($baseUploadsUrl, '/') . '/' . ltrim($relativePath, '/');

        $attachment = WpPost::create([
            'post_author' => 1,
            'post_date' => now()->format('Y-m-d H:i:s'),
            'post_date_gmt' => now('UTC')->format('Y-m-d H:i:s'),
            'post_content' => '',
            'post_title' => pathinfo($filename, PATHINFO_FILENAME),
            'post_excerpt' => '',
            'post_status' => 'inherit',
            'comment_status' => 'open',
            'ping_status' => 'closed',
            'post_password' => '',
            'post_name' => Str::slug(pathinfo($filename, PATHINFO_FILENAME)) . '-' . Str::random(4),
            'to_ping' => '',
            'pinged' => '',
            'post_modified' => now()->format('Y-m-d H:i:s'),
            'post_modified_gmt' => now('UTC')->format('Y-m-d H:i:s'),
            'post_content_filtered' => '',
            'post_parent' => $tourId,
            'guid' => $guid,
            'menu_order' => 0,
            'post_type' => 'attachment',
            'post_mime_type' => $mime,
            'comment_count' => 0,
        ]);

        WpPostMeta::create([
            'post_id' => $attachment->ID,
            'meta_key' => '_wp_attached_file',
            'meta_value' => $relativePath,
        ]);

        // _wp_attachment_metadata (optionnel) : width/height pour que WP affiche correctement
        $metadata = $this->buildAttachmentMetadata($fullPath, $relativePath);
        if (!empty($metadata)) {
            WpPostMeta::create([
                'post_id' => $attachment->ID,
                'meta_key' => '_wp_attachment_metadata',
                'meta_value' => serialize($metadata),
            ]);
        }

        return [
            'attachment_id' => (int) $attachment->ID,
            'relative_path' => $relativePath,
        ];
    }

    /**
     * Build minimal _wp_attachment_metadata (width, height, file) for the attachment.
     */
    private function buildAttachmentMetadata(string $fullPath, string $relativePath): array
    {
        if (!is_file($fullPath) || !is_readable($fullPath)) {
            return [];
        }
        $imageSize = @getimagesize($fullPath);
        if (!$imageSize || !isset($imageSize[0], $imageSize[1])) {
            return [];
        }

        $width = (int) $imageSize[0];
        $height = (int) $imageSize[1];

        return [
            'width' => $width,
            'height' => $height,
            'file' => $relativePath,
            'filesize' => (int) @filesize($fullPath),
            'sizes' => $this->generateIntermediateSizes($fullPath, $width, $height),
            'image_meta' => [],
        ];
    }

    /**
     * Declinaisons intermediaires ecrites a cote du fichier, au nom attendu par
     * WordPress ({base}-{largeur}x{hauteur}.{ext}). Sans elles, wp_get_attachment_image()
     * n'a aucun srcset et sert le fichier pleine taille dans une carte de 768 px.
     * Retourne [] quand GD est absent : WordPress retombe alors sur le fichier complet.
     *
     * @return array<string, array{file: string, width: int, height: int, mime-type: string, filesize: int}>
     */
    private function generateIntermediateSizes(string $fullPath, int $width, int $height): array
    {
        if ($width < 1 || $height < 1) {
            return [];
        }

        $optimizer = app(UploadedImageOptimizer::class);
        $directory = dirname($fullPath);
        $base = pathinfo($fullPath, PATHINFO_FILENAME);
        $sizes = [];

        foreach (self::INTERMEDIATE_SIZES as $name => $targetWidth) {
            if ($targetWidth >= $width) {
                continue;
            }

            $encoded = $optimizer->encode($fullPath, $targetWidth);
            if ($encoded === null) {
                continue;
            }

            [$binary, $extension] = $encoded;
            $dimensions = @getimagesizefromstring($binary);
            if (! is_array($dimensions)) {
                continue;
            }

            $file = sprintf('%s-%dx%d.%s', $base, (int) $dimensions[0], (int) $dimensions[1], $extension);
            if (@file_put_contents($directory . '/' . $file, $binary) === false) {
                continue;
            }

            $sizes[$name] = [
                'file' => $file,
                'width' => (int) $dimensions[0],
                'height' => (int) $dimensions[1],
                'mime-type' => self::MIME_BY_EXTENSION[$extension] ?? 'image/jpeg',
                'filesize' => strlen($binary),
            ];
        }

        return $sizes;
    }

    /**
     * Re-encode un fichier deja present dans wp-content/uploads (images heritees,
     * televersees avant l'optimisation a l'upload) et regenere ses declinaisons.
     *
     * Le fichier d'origine est conserve : seuls _wp_attached_file, le guid, le type
     * MIME et _wp_attachment_metadata pointent vers la nouvelle version, ce qui evite
     * de casser un contenu qui referencerait encore l'ancienne URL.
     *
     * Retourne null quand il n'y a rien a faire (GD absent, fichier introuvable,
     * gain inferieur a 10 %).
     *
     * @return array{from: string, to: string, before: int, after: int}|null
     */
    /**
     * Reconstruit les métadonnées d'un attachment dont le fichier est présent mais dont
     * `_wp_attachment_metadata` n'a ni largeur ni hauteur : WordPress ne peut alors calculer
     * aucun srcset et sert le fichier pleine taille. Les déclinaisons intermédiaires sont
     * régénérées au passage. Le chemin (`_wp_attached_file`), le guid et le type MIME ne sont
     * jamais modifiés : rien n'est réencodé.
     *
     * Retourne les métadonnées écrites, ou null si le fichier est absent ou illisible.
     *
     * @return array<string, mixed>|null
     */
    public function rebuildAttachmentMetadata(int $attachmentId): ?array
    {
        $relative = self::getAttachedFile($attachmentId);
        $fullPath = self::attachmentAbsolutePath($attachmentId);
        if ($relative === null || $relative === '' || $fullPath === null) {
            return null;
        }

        $metadata = $this->buildAttachmentMetadata($fullPath, $relative);
        if ($metadata === []) {
            return null;
        }

        WpPostMeta::updateOrCreate(
            ['post_id' => $attachmentId, 'meta_key' => '_wp_attachment_metadata'],
            ['meta_value' => serialize($metadata)]
        );

        return $metadata;
    }
    public function optimizeExistingAttachment(int $attachmentId, int $maxWidth = self::MAX_STORED_WIDTH): ?array
    {
        $fullPath = self::attachmentAbsolutePath($attachmentId);
        $relativePath = self::getAttachedFile($attachmentId);
        if ($fullPath === null || $relativePath === null) {
            return null;
        }

        $before = (int) filesize($fullPath);
        $encoded = app(UploadedImageOptimizer::class)->encode($fullPath, $maxWidth);
        if ($encoded === null) {
            return null;
        }

        [$binary, $ext] = $encoded;
        if ($before > 0 && strlen($binary) > $before * 0.9) {
            return null;
        }

        $newRelative = (string) preg_replace('/\.[a-z0-9]+$/i', '', $relativePath) . '.' . $ext;
        if ($newRelative === $relativePath) {
            $newRelative = (string) preg_replace('/\.[a-z0-9]+$/i', '', $relativePath) . '-opt.' . $ext;
        }

        $basePath = rtrim((string) config('wordpress.uploads_path'), '/');
        $newFullPath = $basePath . '/' . ltrim($newRelative, '/');
        if (@file_put_contents($newFullPath, $binary) === false) {
            \Log::error('WpHeroImageService: écriture de la version optimisée impossible', [
                'attachment_id' => $attachmentId,
                'path' => $newFullPath,
            ]);

            return null;
        }

        $metadata = $this->buildAttachmentMetadata($newFullPath, $newRelative);

        WpPostMeta::updateOrCreate(
            ['post_id' => $attachmentId, 'meta_key' => '_wp_attached_file'],
            ['meta_value' => $newRelative]
        );
        if (! empty($metadata)) {
            WpPostMeta::updateOrCreate(
                ['post_id' => $attachmentId, 'meta_key' => '_wp_attachment_metadata'],
                ['meta_value' => serialize($metadata)]
            );
        }
        WpPost::query()->where('ID', $attachmentId)->update([
            'guid' => rtrim(self::getUploadsBaseUrl(), '/') . '/' . ltrim($newRelative, '/'),
            'post_mime_type' => self::MIME_BY_EXTENSION[$ext] ?? 'image/jpeg',
        ]);

        return [
            'from' => $relativePath,
            'to' => $newRelative,
            'before' => $before,
            'after' => strlen($binary),
        ];
    }

    /**
     * Chemin disque d'un attachment, ou null s'il est hors de wp-content/uploads
     * (URL absolue stockee, dossier non configure, fichier absent).
     */
    public static function attachmentAbsolutePath(int $attachmentId): ?string
    {
        $basePath = config('wordpress.uploads_path');
        $relativePath = self::getAttachedFile($attachmentId);

        if (empty($basePath) || ! is_string($relativePath) || $relativePath === '') {
            return null;
        }
        if (str_starts_with($relativePath, 'http://') || str_starts_with($relativePath, 'https://')) {
            return null;
        }

        $fullPath = rtrim($basePath, '/') . '/' . ltrim($relativePath, '/');

        return is_file($fullPath) && is_readable($fullPath) ? $fullPath : null;
    }

    /**
     * URL publique stable à partir de _wp_attached_file uniquement (jamais guid).
     * Input: attachment_id. Output: URL absolue ou null.
     */
    public static function getAttachmentUrl(int $attachmentId): ?string
    {
        $attachedFile = WpPostMeta::on('wp')
            ->where('post_id', $attachmentId)
            ->where('meta_key', '_wp_attached_file')
            ->value('meta_value');

        if (empty($attachedFile) || !is_string($attachedFile)) {
            return null;
        }

        $attachedFile = trim($attachedFile);

        if (str_starts_with($attachedFile, 'http://') || str_starts_with($attachedFile, 'https://')) {
            return $attachedFile;
        }

        if (str_starts_with($attachedFile, '/wp-content/uploads')) {
            $siteUrl = rtrim((string) config('wordpress.site_url', ''), '/');
            if ($siteUrl !== '') {
                return $siteUrl.$attachedFile;
            }
            $base = self::getUploadsBaseUrl();
            if ($base === '') {
                return null;
            }
            $suffix = preg_replace('#^/wp-content/uploads/?#', '', $attachedFile) ?? '';

            return rtrim($base, '/').'/'.ltrim((string) $suffix, '/');
        }

        $base = self::getUploadsBaseUrl();
        if ($base === '') {
            return null;
        }

        return rtrim($base, '/').'/'.ltrim($attachedFile, '/');
    }

    /**
     * URL publique fiable pour un attachment : {@see WpPost::$guid} (URL enregistrée à l’upload),
     * puis construction depuis {@see getAttachmentUrl()} avec une base unifiée.
     *
     * À utiliser partout (fiche voyage, workspace) pour éviter les 404 dus à un mélange
     * config('app.wp_upload_url') vs {@see getUploadsBaseUrl()} (héros hero-* uploadés via Laravel).
     */
    public static function publicUrlForAttachmentId(int $attachmentId): ?string
    {
        if ($attachmentId <= 0) {
            return null;
        }

        $guid = WpPost::query()
            ->where('ID', $attachmentId)
            ->where('post_type', 'attachment')
            ->value('guid');

        if (is_string($guid)) {
            $guid = trim($guid);
            if (str_starts_with($guid, 'http://') || str_starts_with($guid, 'https://')) {
                return $guid;
            }
        }

        return self::getAttachmentUrl($attachmentId);
    }

    /**
     * Valeur _wp_attached_file pour un attachment (ex: 2026/02/hero-974-xxx.webp).
     */
    public static function getAttachedFile(int $attachmentId): ?string
    {
        $value = WpPostMeta::on('wp')
            ->where('post_id', $attachmentId)
            ->where('meta_key', '_wp_attached_file')
            ->value('meta_value');

        return $value ? trim((string) $value) : null;
    }

    /**
     * Base URL publique du dossier uploads.
     *
     * Ordre (aligné partout : upload guid, getAttachmentUrl, fiche voyage) :
     * 1. WP_UPLOADS_URL
     * 2. APP wp_upload_url (évite les 404 quand WP_SITE_URL ≠ domaine réel des fichiers)
     * 3. WP_SITE_URL + /wp-content/uploads
     */
    public static function getUploadsBaseUrl(): string
    {
        $uploads = config('wordpress.uploads_url');
        if (is_string($uploads) && $uploads !== '') {
            return rtrim($uploads, '/');
        }

        $appUpload = config('app.wp_upload_url');
        if (is_string($appUpload) && $appUpload !== '') {
            return rtrim($appUpload, '/');
        }

        $siteUrl = rtrim((string) config('wordpress.site_url', ''), '/');

        return $siteUrl !== '' ? $siteUrl.'/wp-content/uploads' : '';
    }
}
