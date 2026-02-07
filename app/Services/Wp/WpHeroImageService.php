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
     * @return int Attachment post ID
     */
    public function storeUploadAndCreateAttachment(UploadedFile $file, int $tourId = 0): int
    {
        $basePath = config('wordpress.uploads_path');
        if (empty($basePath) || !is_dir($basePath)) {
            throw new \RuntimeException('WP uploads path is not configured or does not exist. Set WP_UPLOADS_PATH in .env.');
        }

        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension();
        $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        if (strlen($safeName) > 100) {
            $safeName = Str::limit($safeName, 100, '');
        }
        $filename = $safeName . '-' . Str::random(6) . '.' . ($extension ?: 'jpg');
        $relativePath = date('Y/m') . '/' . $filename;
        $fullPath = rtrim($basePath, '/') . '/' . $relativePath;

        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!$file->move($dir, $filename)) {
            throw new \RuntimeException('Failed to move uploaded file.');
        }

        $mime = $file->getMimeType();
        $uploadsUrl = config('wordpress.uploads_url') ?: (rtrim(config('wordpress.site_url'), '/') . '/wp-content/uploads');
        $guid = rtrim($uploadsUrl, '/') . '/' . $relativePath;

        $attachment = WpPost::create([
            'post_author' => 1,
            'post_date' => now()->format('Y-m-d H:i:s'),
            'post_date_gmt' => now('UTC')->format('Y-m-d H:i:s'),
            'post_content' => '',
            'post_title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
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

        return (int) $attachment->ID;
    }

    /**
     * Get attachment URL (guid or from _wp_attached_file). Prefer full size.
     */
    public static function getAttachmentUrl(int $attachmentId): ?string
    {
        $post = WpPost::on('wp')->where('ID', $attachmentId)->where('post_type', 'attachment')->first();
        if (!$post) {
            return null;
        }
        if (!empty($post->guid)) {
            return $post->guid;
        }
        $file = WpPostMeta::where('post_id', $attachmentId)->where('meta_key', '_wp_attached_file')->value('meta_value');
        if ($file) {
            $base = config('wordpress.uploads_url') ?: (rtrim(config('wordpress.site_url'), '/') . '/wp-content/uploads');
            return rtrim($base, '/') . '/' . $file;
        }
        return null;
    }
}
