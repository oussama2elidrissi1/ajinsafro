<?php

namespace App\Services\Sync;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WpSyncService
{
    public function pushToWp(array $payload): bool
    {
        $url = config('sync.wp_url');
        $token = config('sync.token');

        if (empty($url) || empty($token)) {
            Log::channel('sync')->warning('Outbound sync skipped (missing config)', [
                'action' => $payload['action'] ?? null,
                'entity_type' => $payload['entity_type'] ?? null,
                'wp_post_id' => $payload['wp_post_id'] ?? null,
                'slug' => $payload['slug'] ?? null,
            ]);
            return false;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'X-Sync-Origin' => 'laravel',
                    'Accept' => 'application/json',
                ])
                ->post($url, $payload);

            if (!$response->successful()) {
                Log::channel('sync')->error('Outbound sync failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'action' => $payload['action'] ?? null,
                    'entity_type' => $payload['entity_type'] ?? null,
                    'wp_post_id' => $payload['wp_post_id'] ?? null,
                    'slug' => $payload['slug'] ?? null,
                ]);
                return false;
            }

            if (config('sync.debug')) {
                Log::channel('sync')->info('Outbound sync ok', [
                    'status' => $response->status(),
                    'action' => $payload['action'] ?? null,
                    'entity_type' => $payload['entity_type'] ?? null,
                    'wp_post_id' => $payload['wp_post_id'] ?? null,
                    'slug' => $payload['slug'] ?? null,
                ]);
            }

            return true;
        } catch (\Throwable $e) {
            Log::channel('sync')->error('Outbound sync exception', [
                'error' => $e->getMessage(),
                'action' => $payload['action'] ?? null,
                'entity_type' => $payload['entity_type'] ?? null,
                'wp_post_id' => $payload['wp_post_id'] ?? null,
                'slug' => $payload['slug'] ?? null,
            ]);
            return false;
        }
    }
}
