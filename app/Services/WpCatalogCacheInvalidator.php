<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WpCatalogCacheInvalidator
{
    public static function invalidate(array $keys): void
    {
        $wpUrl = rtrim((string) config('app.public_url', 'https://ajinsafro.net'), '/');
        $secret = config('app.wp_invalidate_secret', '');

        if ($secret === '' || $wpUrl === '') {
            return;
        }

        foreach ($keys as $key) {
            try {
                Http::withHeaders([
                    'X-Ajth-Secret' => $secret,
                ])->timeout(5)->post("{$wpUrl}/wp-json/ajth/v1/invalidate-cache", [
                    'key' => $key,
                ]);
            } catch (\Throwable $e) {
                Log::warning('WP cache invalidate failed', ['key' => $key, 'error' => $e->getMessage()]);
            }
        }
    }
}
