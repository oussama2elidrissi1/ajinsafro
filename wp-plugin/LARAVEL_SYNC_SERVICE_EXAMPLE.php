<?php

/**
 * Laravel Service Example for WordPress Tour Sync
 * 
 * Place this file in: app/Services/WordPress/TourSyncService.php
 * 
 * Usage:
 * $syncService = app(\App\Services\WordPress\TourSyncService::class);
 * $result = $syncService->syncTourToWordPress($voyage);
 */

namespace App\Services\WordPress;

use App\Models\Voyage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TourSyncService
{
    private string $wpEndpoint;
    private string $hmacSecret;

    public function __construct()
    {
        $this->wpEndpoint = rtrim(config('wordpress.sync_endpoint'), '/');
        $this->hmacSecret = config('wordpress.hmac_secret');

        if (empty($this->wpEndpoint) || empty($this->hmacSecret)) {
            throw new \Exception('WordPress sync not configured. Check config/wordpress.php and .env');
        }
    }

    /**
     * Sync a single tour to WordPress
     */
    public function syncTourToWordPress(Voyage $voyage): array
    {
        $payload = $this->buildPayload($voyage);
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, $this->hmacSecret);

        Log::info('Syncing tour to WordPress', [
            'voyage_id' => $voyage->id,
            'endpoint' => $this->wpEndpoint,
        ]);

        $response = Http::timeout(30)
            ->withHeaders([
                'X-AJ-Signature' => $signature,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->post($this->wpEndpoint, $payload);

        if ($response->successful()) {
            $data = $response->json();
            
            // Update WordPress metadata in Laravel
            $voyage->update([
                'wp_post_id' => $data['data']['post_id'] ?? null,
                'wp_synced_at' => now(),
                'wp_sync_hash' => md5($body),
            ]);

            Log::info('Tour synced successfully to WordPress', [
                'voyage_id' => $voyage->id,
                'wp_post_id' => $data['data']['post_id'] ?? null,
                'action' => $data['data']['action'] ?? 'unknown',
            ]);

            return $data;
        }

        $errorBody = $response->body();
        $errorCode = $response->status();

        Log::error('WordPress sync failed', [
            'voyage_id' => $voyage->id,
            'status_code' => $errorCode,
            'error' => $errorBody,
        ]);

        throw new \Exception("WordPress sync failed (HTTP {$errorCode}): {$errorBody}");
    }

    /**
     * Sync multiple tours in batch
     */
    public function syncMultiple(iterable $voyages): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($voyages as $voyage) {
            try {
                $result = $this->syncTourToWordPress($voyage);
                $results['success'][] = [
                    'voyage_id' => $voyage->id,
                    'wp_post_id' => $result['data']['post_id'] ?? null,
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'voyage_id' => $voyage->id,
                    'error' => $e->getMessage(),
                ];
            }

            // Sleep to avoid overwhelming WordPress
            usleep(500000); // 0.5 seconds
        }

        return $results;
    }

    /**
     * Delete a tour from WordPress
     */
    public function deleteTourFromWordPress(Voyage $voyage): array
    {
        $payload = [
            'action' => 'delete',
            'entity_type' => 'tour',
            'laravel_id' => $voyage->id,
        ];

        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, $this->hmacSecret);

        $response = Http::timeout(30)
            ->withHeaders([
                'X-AJ-Signature' => $signature,
                'Content-Type' => 'application/json',
            ])
            ->post($this->wpEndpoint, $payload);

        if ($response->successful()) {
            $voyage->update([
                'wp_post_id' => null,
                'wp_synced_at' => null,
                'wp_sync_hash' => null,
            ]);

            return $response->json();
        }

        throw new \Exception("WordPress delete failed: " . $response->body());
    }

    /**
     * Build WordPress sync payload
     */
    private function buildPayload(Voyage $voyage): array
    {
        $voyage->load(['images', 'programDays']);

        return [
            'action' => 'upsert',
            'entity_type' => 'tour',
            'laravel_id' => $voyage->id,
            'slug' => $voyage->slug,
            'title' => $voyage->name,
            'content_html' => $this->buildContentHtml($voyage),
            'address' => $voyage->destination ?? '',
            'duration_day' => $voyage->duration_text ?? '',
            'adult_price' => $voyage->price_from ?? 0,
            'child_price' => 0, // TODO: Implement child pricing if needed
            'is_featured' => 'off', // TODO: Map from voyage data if needed
            'is_sale_schedule' => 'off',
            'discount_type' => $voyage->old_price ? 'percent' : '',
            'images' => [
                'featured' => $voyage->featured_image_url,
                'gallery' => $voyage->images->map(fn($img) => $img->url)->toArray(),
            ],
        ];
    }

    /**
     * Build rich HTML content for WordPress
     */
    private function buildContentHtml(Voyage $voyage): string
    {
        $html = '';

        // Main description
        if ($voyage->description) {
            $html .= '<div class="tour-description">';
            $html .= wpautop($voyage->description);
            $html .= '</div>';
        }

        // Accroche (tagline)
        if ($voyage->accroche) {
            $html .= '<div class="tour-tagline">';
            $html .= '<p><strong>' . esc_html($voyage->accroche) . '</strong></p>';
            $html .= '</div>';
        }

        // Program days
        if ($voyage->programDays->isNotEmpty()) {
            $html .= '<div class="tour-program">';
            $html .= '<h3>Programme détaillé</h3>';
            
            foreach ($voyage->programDays as $day) {
                $html .= '<div class="program-day">';
                $html .= '<h4>Jour ' . $day->day_number . ': ' . esc_html($day->title) . '</h4>';
                if ($day->description) {
                    $html .= wpautop($day->description);
                }
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Check if tour needs sync (has changed)
     */
    public function needsSync(Voyage $voyage): bool
    {
        if (empty($voyage->wp_sync_hash)) {
            return true;
        }

        $currentPayload = $this->buildPayload($voyage);
        $currentHash = md5(json_encode($currentPayload));

        return $currentHash !== $voyage->wp_sync_hash;
    }
}

/**
 * Helper function to escape HTML for WordPress
 */
function esc_html($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Helper function to auto-paragraph for WordPress
 */
function wpautop($text) {
    $text = trim($text);
    if (empty($text)) {
        return '';
    }
    
    $text = preg_replace("/\r\n|\r/", "\n", $text);
    $text = preg_replace("/\n\n+/", "\n\n", $text);
    $paragraphs = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
    $text = '';
    
    foreach ($paragraphs as $paragraph) {
        $text .= '<p>' . str_replace("\n", '<br>', trim($paragraph)) . "</p>\n";
    }
    
    return $text;
}
