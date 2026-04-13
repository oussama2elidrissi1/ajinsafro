<?php

namespace App\Services\Wp;

use App\Models\Wp\WpPost;
use App\Models\Wp\WpPostMeta;
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

        if (!$file->move($dir, $filename)) {
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

        return [
            'width' => (int) $imageSize[0],
            'height' => (int) $imageSize[1],
            'file' => $relativePath,
            'sizes' => [],
            'image_meta' => [],
        ];
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
