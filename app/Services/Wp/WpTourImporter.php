<?php

namespace App\Services\Wp;

use App\Models\Voyage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WpTourImporter
{
    /**
     * WordPress table prefix (from WP database)
     */
    protected string $prefix = 'cFdgeZ_';

    /**
     * Import all published WordPress TravelerWP tours into Laravel voyages table.
     *
     * @param int $limit Maximum number of tours to import (0 = no limit)
     * @return array Summary: ['created' => int, 'updated' => int, 'skipped' => int, 'errors' => array]
     */
    public function importAll(int $limit = 0): array
    {
        $summary = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        try {
            // Fetch all published st_tours posts
            $postsQuery = DB::table($this->prefix . 'posts')
                ->where('post_type', 'st_tours')
                ->where('post_status', 'publish')
                ->select('ID', 'post_title', 'post_name', 'post_content', 'post_excerpt', 'post_status');

            if ($limit > 0) {
                $postsQuery->limit($limit);
            }

            $posts = $postsQuery->get();

            if ($posts->isEmpty()) {
                Log::info('[WpTourImporter] No published st_tours found to import.');
                return $summary;
            }

            $postIds = $posts->pluck('ID')->toArray();

            // Fetch st_tours table data (efficient: one query)
            $stToursData = DB::table($this->prefix . 'st_tours')
                ->whereIn('post_id', $postIds)
                ->select('post_id', 'adult_price', 'child_price', 'min_price', 'duration_day', 'address')
                ->get()
                ->keyBy('post_id');

            // Fetch postmeta (efficient: one query, only needed keys)
            $metaKeys = ['address', 'duration_day', 'adult_price', 'child_price', 'min_price', 'gallery', '_thumbnail_id'];
            $postMeta = DB::table($this->prefix . 'postmeta')
                ->whereIn('post_id', $postIds)
                ->whereIn('meta_key', $metaKeys)
                ->select('post_id', 'meta_key', 'meta_value')
                ->get()
                ->groupBy('post_id');

            // Process each post
            foreach ($posts as $post) {
                try {
                    $result = $this->importPost($post, $stToursData, $postMeta);
                    if ($result === 'created') {
                        $summary['created']++;
                    } elseif ($result === 'updated') {
                        $summary['updated']++;
                    } else {
                        $summary['skipped']++;
                    }
                } catch (\Exception $e) {
                    $summary['errors'][] = [
                        'wp_post_id' => $post->ID,
                        'message' => $e->getMessage(),
                    ];
                    Log::error("[WpTourImporter] Error importing post {$post->ID}: " . $e->getMessage());
                }
            }

            Log::info('[WpTourImporter] Import completed', $summary);
        } catch (\Exception $e) {
            Log::error('[WpTourImporter] Fatal error during import: ' . $e->getMessage());
            $summary['errors'][] = [
                'wp_post_id' => null,
                'message' => 'Fatal error: ' . $e->getMessage(),
            ];
        }

        return $summary;
    }

    /**
     * Import a single WordPress tour by WP post ID.
     *
     * @param int $wpPostId
     * @return array ['status' => 'created|updated|skipped|error', 'message' => string, 'voyage_id' => int|null]
     */
    public function importOne(int $wpPostId): array
    {
        try {
            // Fetch the post
            $post = DB::table($this->prefix . 'posts')
                ->where('ID', $wpPostId)
                ->where('post_type', 'st_tours')
                ->select('ID', 'post_title', 'post_name', 'post_content', 'post_excerpt', 'post_status')
                ->first();

            if (!$post) {
                return [
                    'status' => 'error',
                    'message' => "WordPress post {$wpPostId} not found or not a st_tours post.",
                    'voyage_id' => null,
                ];
            }

            // Fetch st_tours data
            $stToursData = DB::table($this->prefix . 'st_tours')
                ->where('post_id', $wpPostId)
                ->select('post_id', 'adult_price', 'child_price', 'min_price', 'duration_day', 'address')
                ->get()
                ->keyBy('post_id');

            // Fetch postmeta
            $metaKeys = ['address', 'duration_day', 'adult_price', 'child_price', 'min_price', 'gallery', '_thumbnail_id'];
            $postMeta = DB::table($this->prefix . 'postmeta')
                ->where('post_id', $wpPostId)
                ->whereIn('meta_key', $metaKeys)
                ->select('post_id', 'meta_key', 'meta_value')
                ->get()
                ->groupBy('post_id');

            $result = $this->importPost($post, $stToursData, $postMeta);

            $voyage = Voyage::where('wp_post_id', $wpPostId)->first();

            return [
                'status' => $result,
                'message' => ucfirst($result) . " successfully.",
                'voyage_id' => $voyage ? $voyage->id : null,
            ];
        } catch (\Exception $e) {
            Log::error("[WpTourImporter] Error importing post {$wpPostId}: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'voyage_id' => null,
            ];
        }
    }

    /**
     * Import a single post with preloaded data.
     *
     * @param object $post WP post object
     * @param \Illuminate\Support\Collection $stToursData st_tours table data keyed by post_id
     * @param \Illuminate\Support\Collection $postMeta postmeta grouped by post_id
     * @return string 'created', 'updated', or 'skipped'
     */
    protected function importPost($post, $stToursData, $postMeta): string
    {
        $wpPostId = $post->ID;

        // Get st_tours row if exists
        $stTour = $stToursData->get($wpPostId);

        // Get postmeta for this post
        $meta = [];
        if (isset($postMeta[$wpPostId])) {
            foreach ($postMeta[$wpPostId] as $metaRow) {
                $meta[$metaRow->meta_key] = $metaRow->meta_value;
            }
        }

        // Map data
        $mappedData = $this->mapWpPostToVoyage($post, $stTour, $meta);

        // Generate sync hash
        $syncHash = hash('sha256', json_encode($mappedData));

        // Check if already exists
        $existingVoyage = Voyage::where('wp_post_id', $wpPostId)->first();

        if ($existingVoyage) {
            // Check if data changed
            if ($existingVoyage->wp_sync_hash === $syncHash) {
                // No changes, skip
                return 'skipped';
            }

            // Update
            $existingVoyage->update(array_merge($mappedData, [
                'wp_synced_at' => now(),
                'wp_sync_hash' => $syncHash,
            ]));

            return 'updated';
        }

        // Create new voyage
        // Ensure slug is unique
        $slug = $this->ensureUniqueSlug($mappedData['slug'], $wpPostId);
        $mappedData['slug'] = $slug;

        Voyage::create(array_merge($mappedData, [
            'wp_post_id' => $wpPostId,
            'wp_synced_at' => now(),
            'wp_sync_hash' => $syncHash,
        ]));

        return 'created';
    }

    /**
     * Map WordPress post data to Voyage model attributes.
     *
     * @param object $post
     * @param object|null $stTour
     * @param array $meta
     * @return array
     */
    protected function mapWpPostToVoyage($post, $stTour, array $meta): array
    {
        // Name: post_title
        $name = $post->post_title ?: 'Untitled Tour';

        // Slug: post_name (will be checked for uniqueness later)
        $slug = $post->post_name ?: Str::slug($name);

        // Description: post_content
        $description = $post->post_content;

        // Accroche: post_excerpt
        $accroche = $post->post_excerpt;

        // Destination: COALESCE(st_tours.address, postmeta(address))
        $destination = null;
        if ($stTour && !empty($stTour->address)) {
            $destination = $stTour->address;
        } elseif (!empty($meta['address'])) {
            $destination = $meta['address'];
        }

        // Duration text: COALESCE(st_tours.duration_day, postmeta(duration_day))
        $durationText = null;
        if ($stTour && !empty($stTour->duration_day)) {
            $durationText = $stTour->duration_day;
        } elseif (!empty($meta['duration_day'])) {
            $durationText = $meta['duration_day'];
        }

        // Price from: first non-null among (st_tours.adult_price, st_tours.min_price, postmeta(adult_price), postmeta(min_price))
        $priceFrom = null;
        if ($stTour && !empty($stTour->adult_price) && $stTour->adult_price > 0) {
            $priceFrom = (int) $stTour->adult_price;
        } elseif ($stTour && !empty($stTour->min_price) && $stTour->min_price > 0) {
            $priceFrom = (int) $stTour->min_price;
        } elseif (!empty($meta['adult_price']) && $meta['adult_price'] > 0) {
            $priceFrom = (int) $meta['adult_price'];
        } elseif (!empty($meta['min_price']) && $meta['min_price'] > 0) {
            $priceFrom = (int) $meta['min_price'];
        }

        // Status: 'actif' for publish, else 'brouillon'
        $status = ($post->post_status === 'publish') ? 'actif' : 'brouillon';

        // Currency: default MAD
        $currency = 'MAD';

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'accroche' => $accroche,
            'destination' => $destination,
            'duration_text' => $durationText,
            'price_from' => $priceFrom,
            'old_price' => null,
            'currency' => $currency,
            'min_people' => null,
            'departure_policy' => null,
            'status' => $status,
            'featured_image' => null, // Can be enhanced to import WP thumbnail
        ];
    }

    /**
     * Ensure slug uniqueness. If slug exists for another voyage, append "-{wp_post_id}".
     *
     * @param string $slug
     * @param int $wpPostId
     * @return string
     */
    protected function ensureUniqueSlug(string $slug, int $wpPostId): string
    {
        // Check if slug is taken by another voyage (not the current wp_post_id)
        $exists = Voyage::where('slug', $slug)
            ->where('wp_post_id', '!=', $wpPostId)
            ->exists();

        if ($exists) {
            // Append wp_post_id to make it unique
            $slug = $slug . '-' . $wpPostId;
        }

        return $slug;
    }
}
