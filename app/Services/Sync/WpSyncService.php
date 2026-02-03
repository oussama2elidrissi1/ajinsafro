<?php

namespace App\Services\Sync;

use App\Models\Voyage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WpSyncService
{
    protected string $wpSyncUrl;
    protected string $syncToken;
    protected bool $debugMode;

    public function __construct()
    {
        $this->wpSyncUrl = config('sync.wp_sync_url');
        $this->syncToken = config('sync.token');
        $this->debugMode = config('sync.debug', false);
    }

    /**
     * Upsert a Voyage to WordPress st_tours.
     *
     * @param Voyage $voyage
     * @return array ['success' => bool, 'wp_post_id' => int|null, 'message' => string]
     */
    public function upsertVoyage(Voyage $voyage): array
    {
        try {
            // Build payload
            $payload = $this->buildVoyagePayload($voyage);
            
            // Compute sync hash
            $syncHash = hash('sha256', json_encode($payload));
            $payload['sync_hash'] = $syncHash;

            if ($this->debugMode) {
                Log::info('[WpSyncService] Pushing voyage to WordPress', [
                    'voyage_id' => $voyage->id,
                    'wp_post_id' => $voyage->wp_post_id,
                    'payload' => $payload,
                ]);
            }

            // Send to WordPress
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->syncToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->wpSyncUrl, $payload);

            if (!$response->successful()) {
                $error = $response->json('message') ?? $response->body();
                Log::error('[WpSyncService] WordPress sync failed', [
                    'voyage_id' => $voyage->id,
                    'status' => $response->status(),
                    'error' => $error,
                ]);

                return [
                    'success' => false,
                    'wp_post_id' => null,
                    'message' => "WordPress sync failed: {$error}",
                ];
            }

            $data = $response->json();
            $wpPostId = $data['wp_post_id'] ?? $voyage->wp_post_id;

            // Update voyage with sync metadata (without triggering observer again)
            $voyage->withoutEvents(function () use ($voyage, $wpPostId, $syncHash) {
                $voyage->update([
                    'wp_post_id' => $wpPostId,
                    'wp_synced_at' => now(),
                    'wp_sync_hash' => $syncHash,
                ]);
            });

            if ($this->debugMode) {
                Log::info('[WpSyncService] WordPress sync successful', [
                    'voyage_id' => $voyage->id,
                    'wp_post_id' => $wpPostId,
                ]);
            }

            return [
                'success' => true,
                'wp_post_id' => $wpPostId,
                'message' => 'Synced successfully to WordPress',
            ];

        } catch (\Exception $e) {
            Log::error('[WpSyncService] Exception during WordPress sync', [
                'voyage_id' => $voyage->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'wp_post_id' => null,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a Voyage from WordPress.
     *
     * @param Voyage $voyage
     * @return array
     */
    public function deleteVoyage(Voyage $voyage): array
    {
        if (!$voyage->wp_post_id) {
            return [
                'success' => true,
                'message' => 'No wp_post_id, skipping WordPress deletion',
            ];
        }

        try {
            $payload = [
                'action' => 'delete',
                'entity_type' => 'tour',
                'source' => 'laravel',
                'wp_post_id' => $voyage->wp_post_id,
                'laravel_id' => $voyage->id,
            ];

            if ($this->debugMode) {
                Log::info('[WpSyncService] Deleting from WordPress', [
                    'voyage_id' => $voyage->id,
                    'wp_post_id' => $voyage->wp_post_id,
                ]);
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->syncToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->wpSyncUrl, $payload);

            if (!$response->successful()) {
                $error = $response->json('message') ?? $response->body();
                Log::error('[WpSyncService] WordPress deletion failed', [
                    'voyage_id' => $voyage->id,
                    'wp_post_id' => $voyage->wp_post_id,
                    'error' => $error,
                ]);

                return [
                    'success' => false,
                    'message' => "WordPress deletion failed: {$error}",
                ];
            }

            return [
                'success' => true,
                'message' => 'Deleted from WordPress successfully',
            ];

        } catch (\Exception $e) {
            Log::error('[WpSyncService] Exception during WordPress deletion', [
                'voyage_id' => $voyage->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build payload for WordPress sync.
     *
     * @param Voyage $voyage
     * @return array
     */
    protected function buildVoyagePayload(Voyage $voyage): array
    {
        // Map Laravel Voyage to WordPress structure
        return [
            'action' => 'upsert',
            'entity_type' => 'tour',
            'source' => 'laravel',
            'wp_post_id' => $voyage->wp_post_id,
            'laravel_id' => $voyage->id,
            'slug' => $voyage->slug,
            'title' => $voyage->name,
            'content' => $voyage->description ?? '',
            'excerpt' => $voyage->accroche ?? '',
            'destination' => $voyage->destination ?? '',
            'duration_text' => $voyage->duration_text ?? '',
            'price_from' => $voyage->price_from, // Already in cents
            'old_price' => $voyage->old_price,
            'currency' => $voyage->currency ?? 'MAD',
            'status' => $voyage->status === 'actif' ? 'publish' : 'draft',
            'images' => [
                'featured' => $voyage->featured_image,
                'gallery' => $voyage->images->pluck('path')->toArray(),
            ],
        ];
    }

    /**
     * Check if sync should be skipped based on hash.
     *
     * @param Voyage $voyage
     * @return bool
     */
    public function shouldSkipSync(Voyage $voyage): bool
    {
        // Build payload and compute hash
        $payload = $this->buildVoyagePayload($voyage);
        $newHash = hash('sha256', json_encode($payload));

        // Compare with stored hash
        if ($voyage->wp_sync_hash === $newHash) {
            if ($this->debugMode) {
                Log::info('[WpSyncService] Skipping sync - no changes detected', [
                    'voyage_id' => $voyage->id,
                    'hash' => $newHash,
                ]);
            }
            return true;
        }

        return false;
    }
}
