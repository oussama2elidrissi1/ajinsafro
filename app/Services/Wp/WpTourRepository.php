<?php

namespace App\Services\Wp;

use App\Models\Wp\WpPost;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class WpTourRepository
{
    /**
     * List all tours with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listTours(int $perPage = 20): LengthAwarePaginator
    {
        return WpPost::tours()
            ->orderByDesc('ID')
            ->paginate($perPage);
    }

    /**
     * Find tour by ID.
     *
     * @param int $id
     * @return WpPost
     */
    public function findTour(int $id): WpPost
    {
        return WpPost::tours()->findOrFail($id);
    }

    /**
     * Find tour by slug.
     *
     * @param string $slug
     * @return WpPost|null
     */
    public function findTourBySlug(string $slug): ?WpPost
    {
        return WpPost::tours()->where('post_name', $slug)->first();
    }

    /**
     * Create a new tour.
     *
     * @param array $data
     * @return WpPost
     */
    public function createTour(array $data): WpPost
    {
        // Prepare post data
        $postData = [
            'post_title' => $data['title'] ?? 'Untitled Tour',
            'post_name' => $this->ensureUniqueSlug($data['slug'] ?? Str::slug($data['title'] ?? 'tour')),
            'post_content' => $data['content'] ?? '',
            'post_excerpt' => $data['excerpt'] ?? $data['accroche'] ?? '',
            'post_status' => $data['post_status'] ?? 'publish',
            'post_type' => 'st_tours',
            'post_author' => $data['author_id'] ?? 1,
            'post_date' => now(),
            'post_date_gmt' => now('UTC'),
            'post_modified' => now(),
            'post_modified_gmt' => now('UTC'),
            'guid' => '', // Will be set after creation
            'comment_status' => 'open',
            'ping_status' => 'open',
        ];

        // Create post
        $post = WpPost::create($postData);

        // Update GUID (WordPress convention)
        $post->update([
            'guid' => config('app.url') . '/?post_type=st_tours&p=' . $post->ID,
        ]);

        // Set metas
        $this->updateTourMetas($post, $data);

        return $post->fresh();
    }

    /**
     * Update an existing tour.
     *
     * @param int $id
     * @param array $data
     * @return WpPost
     */
    public function updateTour(int $id, array $data): WpPost
    {
        $post = $this->findTour($id);

        // Prepare post data
        $postData = [
            'post_modified' => now(),
            'post_modified_gmt' => now('UTC'),
        ];

        if (isset($data['title'])) {
            $postData['post_title'] = $data['title'];
        }

        if (isset($data['slug'])) {
            $newSlug = $this->ensureUniqueSlug($data['slug'], $id);
            $postData['post_name'] = $newSlug;
        }

        if (isset($data['content'])) {
            $postData['post_content'] = $data['content'];
        }

        if (isset($data['excerpt']) || isset($data['accroche'])) {
            $postData['post_excerpt'] = $data['excerpt'] ?? $data['accroche'] ?? '';
        }

        if (isset($data['post_status'])) {
            $postData['post_status'] = $data['post_status'];
        }

        // Update post
        $post->update($postData);

        // Update metas
        $this->updateTourMetas($post, $data);

        return $post->fresh();
    }

    /**
     * Delete a tour.
     *
     * @param int $id
     * @return bool
     */
    public function deleteTour(int $id): bool
    {
        $post = $this->findTour($id);

        // Delete all metas first
        $post->metas()->delete();

        // Delete post
        return $post->delete();
    }

    /**
     * Update tour metas from data array.
     *
     * @param WpPost $post
     * @param array $data
     * @return void
     */
    protected function updateTourMetas(WpPost $post, array $data): void
    {
        $metaMapping = [
            // Existing fields
            'destination' => 'address',
            'duration_text' => 'duration_day',
            'adult_price' => 'adult_price',
            'child_price' => 'child_price',
            'min_price' => 'min_price',
            'min_people' => 'min_people',
            'gallery_ids' => 'gallery',
            'thumbnail_id' => '_thumbnail_id',
            'status' => 'status',
            
            // NEW: Traveler fields (23/23 metas)
            'max_people' => 'max_people',
            'tour_price_by' => 'tour_price_by',
            'is_featured' => 'is_featured',
            'st_google_map' => 'st_google_map',
            'multi_location' => 'multi_location',
            'discount_by_people_type' => 'discount_by_people_type',
            'discount_type' => 'discount_type',
            'calculator_discount_by_people_type' => 'calculator_discount_by_people_type',
            'tours_program_style' => 'tours_program_style',
            'hide_adult_in_booking_form' => 'hide_adult_in_booking_form',
            'st_tour_external_booking' => 'st_tour_external_booking',
            
            // Payment gateways
            'is_meta_payment_gateway_st_onepay_atm' => 'is_meta_payment_gateway_st_onepay_atm',
            'is_meta_payment_gateway_st_onepay' => 'is_meta_payment_gateway_st_onepay',
            'is_meta_payment_gateway_st_paypal' => 'is_meta_payment_gateway_st_paypal',
            'is_meta_payment_gateway_st_payu' => 'is_meta_payment_gateway_st_payu',
            'is_meta_payment_gateway_st_payulatam' => 'is_meta_payment_gateway_st_payulatam',
            'is_meta_payment_gateway_st_payumoney' => 'is_meta_payment_gateway_st_payumoney',
            'is_meta_payment_gateway_st_razor' => 'is_meta_payment_gateway_st_razor',
        ];

        foreach ($metaMapping as $inputKey => $metaKey) {
            if (array_key_exists($inputKey, $data)) {
                $value = $data[$inputKey];
                
                // Skip null/empty except for checkboxes
                if (($value === null || $value === '') && !str_starts_with($inputKey, 'is_')) {
                    continue;
                }

                // Special handling for gallery (array to CSV)
                if ($inputKey === 'gallery_ids' && is_array($value)) {
                    $value = implode(',', $value);
                }
                
                // Boolean fields: convert to 'on' or ''
                if (str_starts_with($inputKey, 'is_')) {
                    $value = !empty($value) ? 'on' : '';
                }

                $post->setMeta($metaKey, $value);
            }
        }
        
        // Text arrays (tours_include, tours_exclude, tours_highlight)
        foreach (['tours_include', 'tours_exclude', 'tours_highlight'] as $field) {
            if (isset($data[$field])) {
                // If string with line breaks, convert to array
                if (is_string($data[$field])) {
                    $lines = array_filter(array_map('trim', explode("\n", $data[$field])));
                    $value = implode("\n", $lines); // Store as plain text
                } else {
                    $value = $data[$field];
                }
                $post->setMeta($field, $value);
            }
        }

        // Handle featured image separately if provided
        if (isset($data['featured_image'])) {
            $post->setMeta('_thumbnail_id', $data['featured_image']);
        }
        
        // Update taxonomies
        $this->updateTourTaxonomies($post, $data);
    }
    
    /**
     * Update tour taxonomies.
     *
     * @param WpPost $post
     * @param array $data
     * @return void
     */
    protected function updateTourTaxonomies(WpPost $post, array $data): void
    {
        $taxonomies = ['language', 'languages', 'durations', 'st_tour_type'];
        
        foreach ($taxonomies as $taxonomy) {
            if (isset($data[$taxonomy])) {
                $termIds = is_array($data[$taxonomy]) ? $data[$taxonomy] : [$data[$taxonomy]];
                $termIds = array_filter(array_map('intval', $termIds));
                
                if (!empty($termIds)) {
                    $this->setPostTerms($post->ID, $termIds, $taxonomy);
                }
            }
        }
    }
    
    /**
     * Set terms for a post (replaces existing).
     *
     * @param int $postId
     * @param array $termIds
     * @param string $taxonomy
     * @return void
     */
    protected function setPostTerms(int $postId, array $termIds, string $taxonomy): void
    {
        try {
            // Delete existing relationships
            \DB::connection('wp')->table('term_relationships')
                ->where('object_id', $postId)
                ->whereIn('term_taxonomy_id', function($query) use ($taxonomy) {
                    $query->select('term_taxonomy_id')
                        ->from('term_taxonomy')
                        ->where('taxonomy', $taxonomy);
                })
                ->delete();
            
            // Insert new relationships
            foreach ($termIds as $termId) {
                // Get term_taxonomy_id
                $termTaxonomy = \DB::connection('wp')->table('term_taxonomy')
                    ->where('term_id', $termId)
                    ->where('taxonomy', $taxonomy)
                    ->first();
                
                if ($termTaxonomy) {
                    \DB::connection('wp')->table('term_relationships')->insert([
                        'object_id' => $postId,
                        'term_taxonomy_id' => $termTaxonomy->term_taxonomy_id,
                        'term_order' => 0,
                    ]);
                    
                    // Update term count
                    \DB::connection('wp')->table('term_taxonomy')
                        ->where('term_taxonomy_id', $termTaxonomy->term_taxonomy_id)
                        ->increment('count');
                }
            }
        } catch (\Exception $e) {
            \Log::error("Error setting terms for taxonomy '$taxonomy' on post $postId", [
                'error' => $e->getMessage(),
                'term_ids' => $termIds
            ]);
            // Don't throw, continue gracefully
        }
    }

    /**
     * Ensure slug is unique by appending number if needed.
     *
     * @param string $slug
     * @param int|null $excludeId
     * @return string
     */
    protected function ensureUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $originalSlug = $slug;
        $counter = 2;

        while (true) {
            $query = WpPost::where('post_name', $slug)
                ->where('post_type', 'st_tours');

            if ($excludeId) {
                $query->where('ID', '!=', $excludeId);
            }

            if (!$query->exists()) {
                return $slug;
            }

            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
    }

    /**
     * Get tour with all metas loaded.
     *
     * @param int $id
     * @return array
     */
    public function getTourWithMetas(int $id): array
    {
        $post = $this->findTour($id);
        $metas = $post->getAllMetas();

        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'status' => $post->post_status,
            'author_id' => $post->post_author,
            'created_at' => $post->post_date,
            'updated_at' => $post->post_modified,
            'destination' => $metas['address'] ?? null,
            'duration_text' => $metas['duration_day'] ?? null,
            'adult_price' => $metas['adult_price'] ?? null,
            'child_price' => $metas['child_price'] ?? null,
            'min_price' => $metas['min_price'] ?? null,
            'min_people' => $metas['min_people'] ?? null,
            'thumbnail_id' => $metas['_thumbnail_id'] ?? null,
            'gallery' => isset($metas['gallery']) ? explode(',', $metas['gallery']) : [],
            'metas' => $metas,
        ];
    }
}
