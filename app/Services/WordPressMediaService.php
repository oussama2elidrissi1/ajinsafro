<?php

namespace App\Services;

use App\Models\WpPost;
use App\Models\WpPostmeta;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Service WordPress media: uploads vers wp-content/uploads, création attachments,
 * URLs construites uniquement via _wp_attached_file (jamais guid).
 * Compatible déploiement sous /booking (APP_URL avec base path).
 */
class WordPressMediaService
{
    protected string $uploadsPath;

    protected string $uploadsUrl;

    public function __construct()
    {
        $this->uploadsPath = $this->getUploadsBasePath();
        $this->uploadsUrl = $this->getUploadsBaseUrl();
    }

    /**
     * Base path serveur pour les uploads (ex: .../public/wp-content/uploads).
     * Configurable via WP_UPLOADS_PATH, défaut public_path('wp-content/uploads').
     */
    public function getUploadsBasePath(): string
    {
        $path = config('wordpress.uploads_path', public_path('wp-content/uploads'));

        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Base URL publique pour les uploads (ex: https://domain.com/booking/wp-content/uploads).
     * Ne dépend pas du guid WordPress. Configurable via WP_UPLOADS_URL,
     * défaut url('/wp-content/uploads') pour respecter la base /booking.
     */
    public function getUploadsBaseUrl(): string
    {
        $baseUrl = config('wordpress.uploads_url');
        if ($baseUrl !== null && $baseUrl !== '') {
            return rtrim($baseUrl, '/');
        }

        return rtrim(url('/wp-content/uploads'), '/');
    }

    /**
     * Chemin absolu pour un chemin relatif sous uploads (ex: "2026/01/file.jpg").
     */
    public function path(string $relativePath): string
    {
        $safe = str_replace(['..', "\0"], '', $relativePath);

        return $this->uploadsPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safe);
    }

    /**
     * URL publique pour un chemin relatif sous uploads.
     * Construite via base URL + _wp_attached_file (jamais guid).
     */
    public function url(string $relativePath): string
    {
        $safe = ltrim(str_replace(['..', "\0"], '', $relativePath), '/');

        return $this->uploadsUrl . '/' . $safe;
    }

    /**
     * Upload vers structure WordPress Y/m avec nom unique.
     * Retourne le chemin relatif (ex: 2026/01/abc.jpg).
     * Sécurité: pas de path traversal, extension validée.
     */
    public function uploadToWpUploads(UploadedFile $file): string
    {
        $now = Carbon::now();
        $ym = $now->format('Y') . '/' . $now->format('m');
        $dir = $this->uploadsPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $ym);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ext = $file->getClientOriginalExtension() ?: $file->guessExtension();
        $ext = strtolower(preg_replace('/[^a-z0-9]/', '', $ext) ?: 'jpg');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $ext = 'jpg';
        }
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = Str::slug(preg_replace('/[^\pL\pN\-_]/u', '', $baseName)) ?: 'file';
        $uniqueName = $safeName . '-' . Str::random(8) . '.' . $ext;
        $relativePath = $ym . '/' . $uniqueName;
        $fullPath = $this->path($relativePath);

        $file->move($dir, $uniqueName);

        if (config('app.debug')) {
            \Log::debug('WordPressMediaService::uploadToWpUploads', [
                'app_url' => config('app.url'),
                'relative_path' => $relativePath,
                'full_path' => $fullPath,
                'file_exists' => file_exists($fullPath),
            ]);
        }

        return $relativePath;
    }

    /**
     * Crée l'attachment WP (post + _wp_attached_file).
     * guid est rempli pour compat WP mais ne doit jamais être utilisé pour l'affichage.
     */
    public function createAttachment(string $relativePath, string $mimeType, string $guid): int
    {
        $now = Carbon::now();
        $nowGmt = $now->utc();
        $title = pathinfo($relativePath, PATHINFO_FILENAME);

        $post = new WpPost();
        $post->post_author = 1;
        $post->post_date = $now->format('Y-m-d H:i:s');
        $post->post_date_gmt = $nowGmt->format('Y-m-d H:i:s');
        $post->post_content = '';
        $post->post_title = $title;
        $post->post_excerpt = '';
        $post->post_status = 'inherit';
        $post->comment_status = 'open';
        $post->ping_status = 'closed';
        $post->post_password = '';
        $post->post_name = Str::slug($title) . '-' . Str::random(4);
        $post->to_ping = '';
        $post->pinged = '';
        $post->post_modified = $now->format('Y-m-d H:i:s');
        $post->post_modified_gmt = $nowGmt->format('Y-m-d H:i:s');
        $post->post_content_filtered = '';
        $post->post_parent = 0;
        $post->guid = $guid;
        $post->menu_order = 0;
        $post->post_type = 'attachment';
        $post->post_mime_type = $mimeType;
        $post->comment_count = 0;
        $post->save();

        WpPostmeta::setMeta($post->ID, '_wp_attached_file', $relativePath);
        WpPostmeta::setMeta($post->ID, '_wp_attachment_metadata', serialize([
            'file' => $relativePath,
            'width' => null,
            'height' => null,
        ]));

        return (int) $post->ID;
    }

    /**
     * Mime type avant tout déplacement du fichier (évite /tmp file not readable sur cPanel).
     * Ordre: getClientMimeType, finfo sur path actuel si lisible, fallback extension.
     */
    protected function getMimeBeforeMove(UploadedFile $file): string
    {
        $mime = $file->getClientMimeType();
        if ($mime && $mime !== 'application/octet-stream') {
            return $mime;
        }
        $path = $file->getPathname();
        if ($path && is_readable($path) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = finfo_file($finfo, $path);
                finfo_close($finfo);
                if ($detected) {
                    return $detected;
                }
            }
        }
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?? '');
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'application/octet-stream',
        };
        if (config('app.debug')) {
            \Log::debug('WordPressMediaService: mime from extension', ['ext' => $ext, 'mime' => $mime]);
        }

        return $mime;
    }

    /**
     * Upload + création attachment. Mime calculé AVANT move pour éviter erreur /tmp.
     * URL d'affichage: toujours construite via _wp_attached_file (pas guid).
     */
    public function uploadAndCreateAttachment(UploadedFile $file): int
    {
        $mimeType = $this->getMimeBeforeMove($file);

        $relativePath = $this->uploadToWpUploads($file);
        $finalUrl = $this->url($relativePath);
        $fullPath = $this->path($relativePath);

        if (config('app.debug')) {
            \Log::debug('WordPressMediaService::uploadAndCreateAttachment', [
                'app_url' => config('app.url'),
                'relative_path' => $relativePath,
                'full_path' => $fullPath,
                'final_url' => $finalUrl,
                'file_exists' => file_exists($fullPath),
            ]);
        }

        return $this->createAttachment($relativePath, $mimeType, $finalUrl);
    }

    public function setHotelThumbnail(int $hotelPostId, int $attachmentId): void
    {
        WpPostmeta::setMeta($hotelPostId, '_thumbnail_id', (string) $attachmentId);
    }

    public function setHotelGallery(int $hotelPostId, array $attachmentIds): void
    {
        $value = implode(',', array_map('intval', array_filter($attachmentIds)));
        WpPostmeta::setMeta($hotelPostId, 'st_gallery', $value);
        WpPostmeta::setMeta($hotelPostId, 'gallery', $value);
    }

    /**
     * URL de l'image à la une (lecture _thumbnail_id → _wp_attached_file, jamais guid).
     */
    public function getFeaturedImageUrl(int $postId): ?string
    {
        $thumbId = WpPostmeta::getMeta($postId, '_thumbnail_id');
        if (! $thumbId || ! is_numeric($thumbId)) {
            return null;
        }

        return $this->getAttachmentUrl((int) $thumbId);
    }

    /**
     * Galerie: IDs depuis _gallery / st_gallery / gallery, URLs via _wp_attached_file uniquement.
     */
    public function getGalleryUrls(int $postId): array
    {
        $ids = WpPostmeta::getMeta($postId, '_gallery')
            ?: WpPostmeta::getMeta($postId, 'st_gallery')
            ?: WpPostmeta::getMeta($postId, 'gallery');
        if (! $ids || trim($ids) === '') {
            return [];
        }
        $ids = array_map('intval', array_filter(explode(',', $ids)));
        $urls = [];
        foreach ($ids as $attachmentId) {
            $url = $this->getAttachmentUrl($attachmentId);
            if ($url) {
                $urls[] = ['id' => $attachmentId, 'url' => $url];
            }
        }

        return $urls;
    }

    public function setGalleryMeta(int $hotelPostId, array $attachmentIds): void
    {
        $value = implode(',', array_map('intval', array_filter($attachmentIds)));
        WpPostmeta::setMeta($hotelPostId, '_gallery', $value);
    }

    /**
     * Convertit attachment ID → URL publique via _wp_attached_file (jamais guid).
     */
    public function getAttachmentUrl(int $attachmentId): ?string
    {
        $relativePath = WpPostmeta::getMeta($attachmentId, '_wp_attached_file');
        if (! $relativePath || trim($relativePath) === '') {
            return null;
        }

        return $this->url(trim($relativePath));
    }

    /**
     * Retourne l'URL seulement si le fichier existe sur le disque (pour affichage sans "image could not be loaded").
     */
    public function getAttachmentUrlVerified(int $attachmentId): ?string
    {
        $relativePath = WpPostmeta::getMeta($attachmentId, '_wp_attached_file');
        if (! $relativePath || trim($relativePath) === '') {
            return null;
        }
        $fullPath = $this->path(trim($relativePath));
        if (! file_exists($fullPath) || ! is_readable($fullPath)) {
            return null;
        }

        return $this->url(trim($relativePath));
    }

    /**
     * Image à la une: URL seulement si le fichier existe sur le disque.
     */
    public function getFeaturedImageUrlVerified(int $postId): ?string
    {
        $thumbId = WpPostmeta::getMeta($postId, '_thumbnail_id');
        if (! $thumbId || ! is_numeric($thumbId)) {
            return null;
        }

        return $this->getAttachmentUrlVerified((int) $thumbId);
    }

    /**
     * Galerie: mêmes IDs mais URL seulement pour les attachments dont le fichier existe.
     */
    public function getGalleryUrlsVerified(int $postId): array
    {
        $ids = WpPostmeta::getMeta($postId, '_gallery')
            ?: WpPostmeta::getMeta($postId, 'st_gallery')
            ?: WpPostmeta::getMeta($postId, 'gallery');
        if (! $ids || trim($ids) === '') {
            return [];
        }
        $ids = array_map('intval', array_filter(explode(',', $ids)));
        $urls = [];
        foreach ($ids as $attachmentId) {
            $url = $this->getAttachmentUrlVerified($attachmentId);
            if ($url) {
                $urls[] = ['id' => $attachmentId, 'url' => $url];
            }
        }

        return $urls;
    }
}
