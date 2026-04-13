<?php

namespace App\Services;

use App\Models\Wp\WpPost;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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
        $path = config('wordpress.uploads_path');
        if (! is_string($path) || $path === '') {
            $path = public_path('wp-content/uploads');
        }

        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Base URL publique pour les uploads (ex: https://domain.com/wp-content/uploads).
     * Ordre : WP_UPLOADS_URL → WP_PUBLIC_SITE_URL/wp-content/uploads → option WordPress siteurl → url().
     */
    public function getUploadsBaseUrl(): string
    {
        $explicit = config('wordpress.uploads_url');
        if (is_string($explicit) && $explicit !== '') {
            return rtrim($explicit, '/');
        }

        $publicSite = config('wordpress.public_site_url');
        if (is_string($publicSite) && $publicSite !== '') {
            return rtrim($publicSite, '/').'/wp-content/uploads';
        }

        $fromWp = $this->getWordPressSiteUrlFromDatabase();
        if ($fromWp !== null && $fromWp !== '') {
            return rtrim($fromWp, '/').'/wp-content/uploads';
        }

        return rtrim(url('/wp-content/uploads'), '/');
    }

    /**
     * URL du site WordPress (option siteurl), pour aligner les URLs médias sur le front.
     */
    protected function getWordPressSiteUrlFromDatabase(): ?string
    {
        try {
            $v = DB::connection('wp')->table('options')->where('option_name', 'siteurl')->value('option_value');

            return $v ? (string) $v : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * URL publique complète d’un fichier sous uploads (guid / prévisualisation admin).
     */
    public function buildAttachmentPublicUrl(string $relativePath): string
    {
        return $this->url($relativePath);
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

        return rtrim($this->uploadsUrl, '/').'/'.str_replace('\\', '/', $safe);
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
    public function createAttachment(string $relativePath, string $mimeType, string $guid, ?int $parentPostId = null): int
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
        $post->post_parent = $parentPostId ?? 0;
        $post->guid = $guid;
        $post->menu_order = 0;
        $post->post_type = 'attachment';
        $post->post_mime_type = $mimeType;
        $post->comment_count = 0;
        $post->save();

        $post->setMeta('_wp_attached_file', $relativePath);

        $absPath = $this->path($relativePath);
        $width = null;
        $height = null;
        if (is_readable($absPath) && function_exists('getimagesize')) {
            $info = @getimagesize($absPath);
            if ($info !== false) {
                $width = $info[0];
                $height = $info[1];
            }
        }
        $post->setMeta('_wp_attachment_metadata', serialize([
            'file' => $relativePath,
            'width' => $width,
            'height' => $height,
            'sizes' => [],
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
    public function uploadAndCreateAttachment(UploadedFile $file, ?int $parentPostId = null): int
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
                'parent_post_id' => $parentPostId,
            ]);
        }

        return $this->createAttachment($relativePath, $mimeType, $finalUrl, $parentPostId);
    }

    public function setHotelThumbnail(int $hotelPostId, int $attachmentId): void
    {
        $post = WpPost::query()->find($hotelPostId);
        if ($post) {
            $validId = $this->validateAttachmentIdForDisplay($attachmentId);
            if (! $validId) {
                // Ne jamais écraser/supprimer un thumbnail existant si le nouveau est invalide ou non vérifiable.
                return;
            }

            $post->setMeta('_thumbnail_id', (string) $validId);
        }
    }

    public function setHotelGallery(int $hotelPostId, array $attachmentIds): void
    {
        $ids = $this->filterValidAttachmentIdsForDisplay($attachmentIds);
        $value = implode(',', $ids);
        $post = WpPost::query()->find($hotelPostId);
        if ($post) {
            $post->setMeta('st_gallery', $value);
            $post->setMeta('gallery', $value);
        }
    }

    /**
     * URL de l'image à la une (lecture _thumbnail_id → _wp_attached_file, jamais guid).
     */
    public function getFeaturedImageUrl(int $postId): ?string
    {
        $thumbId = WpPost::query()->find($postId)?->getMeta('_thumbnail_id');
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
        $post = WpPost::query()->find($postId);
        $ids = $post?->getMeta('_gallery')
            ?: $post?->getMeta('st_gallery')
            ?: $post?->getMeta('gallery');
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
        $ids = $this->filterValidAttachmentIdsForDisplay($attachmentIds);
        $value = implode(',', $ids);
        $post = WpPost::query()->find($hotelPostId);
        if ($post) {
            $post->setMeta('_gallery', $value);
        }
    }

    /**
     * Validation stricte d'un attachment: existe en WP, a un _wp_attached_file non vide,
     * et le fichier physique est présent sous wp-content/uploads.
     */
    public function validateAttachmentIdForDisplay(int $attachmentId): ?int
    {
        $status = $this->getAttachmentDisplayStatus($attachmentId);
        if ($status['status'] !== 'valid') {
            return null;
        }

        return (int) $attachmentId;
    }

    /**
     * Statut d'affichage d'un attachment.
     *
     * - valid: attachment existe, _wp_attached_file non vide, fichier présent (si uploads_path dispo)
     * - invalid: attachment inexistant / méta vide / fichier absent (si vérifiable)
     * - unknown: impossible de vérifier le fichier physique (uploads_path non dispo) mais méta plausible
     *
     * @return array{status: 'valid'|'invalid'|'unknown', reason: string, attached_file?: string|null}
     */
    public function getAttachmentDisplayStatus(int $attachmentId): array
    {
        $attachmentId = (int) $attachmentId;
        if ($attachmentId <= 0) {
            return ['status' => 'invalid', 'reason' => 'attachment_id<=0'];
        }

        $att = WpPost::query()
            ->where('ID', $attachmentId)
            ->where('post_type', 'attachment')
            ->first();

        if (! $att) {
            return ['status' => 'invalid', 'reason' => 'attachment_not_found'];
        }

        $relativePath = $att->getMeta('_wp_attached_file');
        if (! is_string($relativePath) || trim($relativePath) === '') {
            return ['status' => 'invalid', 'reason' => '_wp_attached_file_empty', 'attached_file' => null];
        }

        $relativePath = trim($relativePath);

        // Si le serveur Laravel n'a pas accès aux uploads WP (uploads_path non configuré / non monté),
        // on ne peut pas vérifier le fichier physique: dans ce cas, ne surtout pas écraser des valeurs existantes.
        if (is_dir($this->uploadsPath)) {
            $fullPath = $this->path($relativePath);
            if (! is_file($fullPath) || ! is_readable($fullPath)) {
                return ['status' => 'invalid', 'reason' => 'file_missing', 'attached_file' => $relativePath];
            }

            return ['status' => 'valid', 'reason' => 'file_exists', 'attached_file' => $relativePath];
        }

        return ['status' => 'unknown', 'reason' => 'uploads_path_unavailable', 'attached_file' => $relativePath];
    }

    /**
     * Filtre une liste d'IDs d'attachments en ne gardant que ceux validés pour affichage.
     *
     * @param array<int, mixed> $attachmentIds
     * @return array<int, int>
     */
    public function filterValidAttachmentIdsForDisplay(array $attachmentIds): array
    {
        $out = [];
        foreach ($attachmentIds as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }
            $status = $this->getAttachmentDisplayStatus($id);
            // Règle: ne retirer que les IDs réellement invalides. Conserver "unknown" pour éviter de casser.
            if ($status['status'] !== 'invalid' && ! in_array($id, $out, true)) {
                $out[] = $id;
            }
        }

        return $out;
    }

    /**
     * Convertit attachment ID → URL publique via _wp_attached_file (jamais guid).
     */
    public function getAttachmentUrl(int $attachmentId): ?string
    {
        $relativePath = WpPost::query()->find($attachmentId)?->getMeta('_wp_attached_file');
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
        $relativePath = WpPost::query()->find($attachmentId)?->getMeta('_wp_attached_file');
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
        $thumbId = WpPost::query()->find($postId)?->getMeta('_thumbnail_id');
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
        $post = WpPost::query()->find($postId);
        $ids = $post?->getMeta('_gallery')
            ?: $post?->getMeta('st_gallery')
            ?: $post?->getMeta('gallery');
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
