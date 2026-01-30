<?php

namespace App\Services;

use App\Models\WpPost;
use App\Models\WpPostmeta;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class WordPressMediaService
{
    protected string $uploadsPath;

    protected string $uploadsUrl;

    public function __construct()
    {
        $this->uploadsPath = rtrim(config('wordpress.uploads_path', public_path('wp-content/uploads')), DIRECTORY_SEPARATOR);
        $baseUrl = config('wordpress.uploads_url');
        $this->uploadsUrl = $baseUrl ? rtrim($baseUrl, '/') : (rtrim(config('app.url'), '/') . '/wp-content/uploads');
    }

    /**
     * Get absolute path for a relative path under uploads (e.g. "2026/01/file.jpg").
     */
    public function path(string $relativePath): string
    {
        return $this->uploadsPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    /**
     * Get public URL for a relative path under uploads.
     */
    public function url(string $relativePath): string
    {
        return $this->uploadsUrl . '/' . ltrim($relativePath, '/');
    }

    /**
     * Upload file to WordPress structure Y/m with unique name.
     * Returns relative path like "2026/01/abc123.jpg".
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
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = Str::slug($baseName);
        if ($safeName === '') {
            $safeName = 'file';
        }
        $uniqueName = $safeName . '-' . Str::random(8) . '.' . ($ext ?: 'jpg');
        $file->move($dir, $uniqueName);

        return $ym . '/' . $uniqueName;
    }

    /**
     * Create WordPress attachment post + postmeta _wp_attached_file (and optional metadata).
     * Returns the attachment post ID.
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
     * Set hotel featured image (thumbnail): postmeta _thumbnail_id = attachment ID.
     */
    public function setHotelThumbnail(int $hotelPostId, int $attachmentId): void
    {
        WpPostmeta::setMeta($hotelPostId, '_thumbnail_id', (string) $attachmentId);
    }

    /**
     * Set hotel gallery: postmeta st_gallery (or gallery) = comma-separated attachment IDs.
     */
    public function setHotelGallery(int $hotelPostId, array $attachmentIds): void
    {
        $value = implode(',', array_map('intval', array_filter($attachmentIds)));
        WpPostmeta::setMeta($hotelPostId, 'st_gallery', $value);
        WpPostmeta::setMeta($hotelPostId, 'gallery', $value);
    }

    /**
     * Get featured image URL for a post (hotel). Returns null if none.
     */
    public function getFeaturedImageUrl(int $postId): ?string
    {
        $thumbId = WpPostmeta::getMeta($postId, '_thumbnail_id');
        if (! $thumbId || ! is_numeric($thumbId)) {
            return null;
        }
        $relativePath = WpPostmeta::getMeta((int) $thumbId, '_wp_attached_file');
        if (! $relativePath) {
            return null;
        }
        return $this->url($relativePath);
    }

    /**
     * Get gallery image URLs for a post (hotel). Reads st_gallery then gallery fallback.
     */
    public function getGalleryUrls(int $postId): array
    {
        $ids = WpPostmeta::getMeta($postId, 'st_gallery') ?: WpPostmeta::getMeta($postId, 'gallery');
        if (! $ids || trim($ids) === '') {
            return [];
        }
        $ids = array_map('intval', array_filter(explode(',', $ids)));
        $urls = [];
        foreach ($ids as $attachmentId) {
            $relativePath = WpPostmeta::getMeta($attachmentId, '_wp_attached_file');
            if ($relativePath) {
                $urls[] = ['id' => $attachmentId, 'url' => $this->url($relativePath)];
            }
        }
        return $urls;
    }

    /**
     * Full flow: upload file, create attachment, return attachment ID.
     */
    public function uploadAndCreateAttachment(UploadedFile $file): int
    {
        $relativePath = $this->uploadToWpUploads($file);
        $mimeType = $file->getMimeType();
        $guid = $this->url($relativePath);

        return $this->createAttachment($relativePath, $mimeType, $guid);
    }
}
