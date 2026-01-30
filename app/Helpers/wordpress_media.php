<?php

use App\Services\WordPressMediaService;

if (! function_exists('get_featured_image_url')) {
    /**
     * Get the featured image (thumbnail) URL for a WordPress post (e.g. hotel).
     * Reads _thumbnail_id → attachment → _wp_attached_file and builds URL.
     *
     * @param  int  $postId  Post ID (e.g. hotel ID)
     * @return string|null  Full URL or null if none
     */
    function get_featured_image_url(int $postId): ?string
    {
        return app(WordPressMediaService::class)->getFeaturedImageUrl($postId);
    }
}

if (! function_exists('get_gallery_urls')) {
    /**
     * Get gallery image URLs for a WordPress post (e.g. hotel).
     * Reads st_gallery (or gallery) postmeta and builds URLs from attachments.
     *
     * @param  int  $postId  Post ID (e.g. hotel ID)
     * @return array<int, array{id: int, url: string}>
     */
    function get_gallery_urls(int $postId): array
    {
        return app(WordPressMediaService::class)->getGalleryUrls($postId);
    }
}
