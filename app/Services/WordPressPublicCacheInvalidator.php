<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WordPressPublicCacheInvalidator
{
    /**
     * Ask WordPress to clear in-process caches for these post IDs (clean_post_cache).
     */
    public function invalidatePostIds(array $postIds): void
    {
        $postIds = array_values(array_unique(array_filter(array_map('intval', $postIds))));
        if ($postIds === []) {
            return;
        }

        $url = config('wordpress.invalidate_url');
        $secret = config('wordpress.invalidate_secret');
        if (! is_string($url) || $url === '' || ! is_string($secret) || $secret === '') {
            return;
        }

        try {
            Http::timeout(12)
                ->asJson()
                ->withHeaders([
                    'X-Ajth-Secret' => $secret,
                    'Accept' => 'application/json',
                ])
                ->post($url, ['post_ids' => $postIds]);
        } catch (\Throwable $e) {
            Log::warning('WordPress catalog cache invalidate request failed.', [
                'message' => $e->getMessage(),
                'post_ids' => $postIds,
            ]);
        }
    }
}
