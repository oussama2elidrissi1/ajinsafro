<?php

namespace App\Services\Wp;

use App\Models\Wp\WpPost;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
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
     * Get tour post by ID (null if not found).
     *
     * @param int $id Post ID
     * @return WpPost|null
     */
    public function getPost(int $id): ?WpPost
    {
        return WpPost::tours()->find($id);
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
        $defaults = [
            'post_date' => now(),
            'post_date_gmt' => now('UTC'),
            'post_modified' => now(),
            'post_modified_gmt' => now('UTC'),
            'comment_status' => 'open',
            'ping_status' => 'open',
        ];
        $postData = WpPostPayloadBuilder::buildWpPostPayload([
            'post_title' => $data['title'] ?? 'Untitled Tour',
            'post_name' => $this->ensureUniqueSlug($data['slug'] ?? Str::slug($data['title'] ?? 'tour')),
            'post_content' => $data['content'] ?? '',
            'post_excerpt' => $data['excerpt'] ?? $data['accroche'] ?? '',
            'post_status' => $data['post_status'] ?? 'publish',
            'post_type' => 'st_tours',
            'post_author' => $data['author_id'] ?? 1,
        ], $defaults);

        // Create post (payload includes all NOT NULL columns for MySQL strict)
        $post = WpPost::create($postData);
        Log::info('WP tour post created (INSERT ok)', ['post_id' => $post->ID, 'post_type' => 'st_tours']);

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
        // COMPLETE Traveler meta mapping
        $metaMapping = [
            // Basic (existing)
            'destination' => 'address',
            'duration_text' => 'duration_day',
            'duration_day' => 'duration_day', // Direct mapping
            'status' => 'status',
            
            // LOCATION
            'address' => 'address',
            'id_location' => 'id_location',
            'location_id' => 'location_id',
            // 'multi_location' handled separately below (special format)
            'map_lat' => 'map_lat',
            'map_lng' => 'map_lng',
            'map_zoom' => 'map_zoom',
            'map_type' => 'map_type',
            
            // GENERAL
            'is_featured' => 'is_featured',
            'tour_price_by' => 'tour_price_by',
            'st_tour_external_booking' => 'st_tour_external_booking',
            'hide_adult_in_booking_form' => 'hide_adult_in_booking_form',
            'max_people' => 'max_people',
            'min_people' => 'min_people',
            'places' => 'places',
            
            // CONTACT
            'contact_email' => 'contact_email',
            'phone' => 'phone',
            'fax' => 'fax',
            'website' => 'website',
            
            // PRICE
            'min_price' => 'min_price',
            'base_price' => 'base_price',
            'sale_price' => 'sale_price',
            'adult_price' => 'adult_price',
            'child_price' => 'child_price',
            'infant_price' => 'infant_price',
            'room_price_double' => 'room_price_double',
            'room_price_twin' => 'room_price_twin',
            'room_price_single' => 'room_price_single',
            'commission_adulte' => 'commission_adulte',
            'commission_enfant' => 'commission_enfant',
            'discount' => 'discount',
            'discount_type' => 'discount_type',
            'discount_by_people_type' => 'discount_by_people_type',
            'calculator_discount_by_people_type' => 'calculator_discount_by_people_type',
            
            // INFORMATION
            'tours_program_style' => 'tours_program_style',
            'tours_faq' => 'tours_faq',
            
            // AVAILABILITY
            'tours_booking_period' => 'tours_booking_period',
            'st_booking_option_type' => 'st_booking_option_type',
            'check_in' => 'check_in',
            'check_out' => 'check_out',
            
            // CANCEL BOOKING
            'st_allow_cancel' => 'st_allow_cancel',
            'st_cancel_percent' => 'st_cancel_percent',
            'st_cancel_number_day' => 'st_cancel_number_day',
            
            // ICAL
            'ical_url' => 'ical_url',
            
            // MEDIA
            'gallery_ids' => 'gallery',
            'video' => 'video',
            
            // MAP
            'st_google_map' => 'st_google_map',
            
            // PAYMENT GATEWAYS
            'is_meta_payment_gateway_st_paypal' => 'is_meta_payment_gateway_st_paypal',
            'is_meta_payment_gateway_st_onepay' => 'is_meta_payment_gateway_st_onepay',
            'is_meta_payment_gateway_st_onepay_atm' => 'is_meta_payment_gateway_st_onepay_atm',
            'is_meta_payment_gateway_st_payu' => 'is_meta_payment_gateway_st_payu',
            'is_meta_payment_gateway_st_payulatam' => 'is_meta_payment_gateway_st_payulatam',
            'is_meta_payment_gateway_st_payumoney' => 'is_meta_payment_gateway_st_payumoney',
            'is_meta_payment_gateway_st_razor' => 'is_meta_payment_gateway_st_razor',
        ];

        foreach ($metaMapping as $inputKey => $metaKey) {
            if (!array_key_exists($inputKey, $data)) {
                continue;
            }
            $value = $data[$inputKey];

            // Boolean/checkbox fields: convert to 'on' or ''
            if (str_starts_with($inputKey, 'is_') || str_starts_with($inputKey, 'st_allow_')) {
                $value = !empty($value) ? 'on' : '';
                $post->setMeta($metaKey, $value);
                continue;
            }

            // Special handling for gallery (array to CSV)
            if ($inputKey === 'gallery_ids' && is_array($value)) {
                $value = implode(',', $value);
            }

            // Toujours enregistrer la valeur (y compris null/'') pour vider les champs quand l'utilisateur les vide
            $post->setMeta($metaKey, $value === null ? '' : (string) $value);
        }
        
        // HTML/Text fields (tours_include, tours_exclude, tours_highlight, tours_faq)
        foreach (['tours_include', 'tours_exclude', 'tours_highlight', 'tours_faq'] as $field) {
            if (isset($data[$field])) {
                $value = $data[$field]; // Store as-is (HTML or plain text)
                $post->setMeta($field, $value);
            }
        }

        // Handle featured image separately if provided
        if (isset($data['featured_image'])) {
            $post->setMeta('_thumbnail_id', $data['featured_image']);
        }

        if (array_key_exists('thumbnail_id', $data)) {
            if ($data['thumbnail_id'] === '' || $data['thumbnail_id'] === null) {
                $post->deleteMeta('_thumbnail_id');
            } else {
                $post->setMeta('_thumbnail_id', (string) (int) $data['thumbnail_id']);
            }
        }

        // Image principale (hero) : enregistrer ou supprimer la meta
        if (array_key_exists('hero_image_id', $data)) {
            if ($data['hero_image_id'] === '' || $data['hero_image_id'] === null) {
                $post->deleteMeta('_tour_hero_image_id');
            } else {
                $post->setMeta('_tour_hero_image_id', (string) (int) $data['hero_image_id']);
            }
        }

        // Galerie Hero (5 images) : enregistrer ou supprimer la meta
        if (array_key_exists('hero_gallery_ids', $data)) {
            if (empty($data['hero_gallery_ids']) || $data['hero_gallery_ids'] === '') {
                $post->deleteMeta('_tour_hero_gallery_ids');
            } else {
                // Convertir CSV en array, nettoyer, limiter à 5, puis reconvertir en CSV
                $ids = is_array($data['hero_gallery_ids']) 
                    ? $data['hero_gallery_ids'] 
                    : explode(',', $data['hero_gallery_ids']);
                $ids = array_filter(array_map('trim', $ids));
                $ids = array_slice($ids, 0, 5); // Max 5 images
                $post->setMeta('_tour_hero_gallery_ids', implode(',', $ids));
            }
        }

        // Handle multi_location (array of IDs -> WP format "_ID1_,_ID2_,_ID3_")
        // Accept both 'locations' (from form) and 'multi_location' (legacy)
        if (isset($data['locations']) || isset($data['multi_location'])) {
            $locationIds = $data['locations'] ?? $data['multi_location'] ?? [];
            $locationIds = is_array($locationIds) ? $locationIds : [];
            $formattedValue = $this->formatMultiLocation($locationIds);
            $post->setMeta('multi_location', $formattedValue);
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
     * Taxonomies supported for tours (terms CRUD).
     */
    public const TOUR_TAXONOMIES = ['language', 'languages', 'durations', 'st_tour_type'];

    /**
     * Get terms for a single taxonomy.
     *
     * @param string $taxonomy
     * @return \Illuminate\Support\Collection
     */
    public function getTermsByTaxonomy(string $taxonomy): \Illuminate\Support\Collection
    {
        if (!in_array($taxonomy, self::TOUR_TAXONOMIES, true)) {
            return collect();
        }
        try {
            return \DB::connection('wp')
                ->table('terms as t')
                ->join('term_taxonomy as tt', 't.term_id', '=', 'tt.term_id')
                ->where('tt.taxonomy', $taxonomy)
                ->select('t.term_id', 't.name', 't.slug')
                ->orderBy('t.name')
                ->get();
        } catch (\Exception $e) {
            Log::warning("Taxonomy '$taxonomy' terms load failed", ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * Create a taxonomy term.
     *
     * @param string $name
     * @param string $taxonomy
     * @param string $slug
     * @return object { term_id, name, slug }
     */
    public function createTaxonomyTerm(string $name, string $taxonomy, string $slug = ''): object
    {
        if (!in_array($taxonomy, self::TOUR_TAXONOMIES, true)) {
            throw new \InvalidArgumentException("Taxonomy not allowed: {$taxonomy}");
        }
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Term name is required.');
        }
        $slug = $slug !== '' ? Str::slug($slug) : Str::slug($name);
        $slug = $this->ensureTermSlugUnique($slug, $taxonomy, null);

        $termId = \DB::connection('wp')->table('terms')->insertGetId([
            'name' => $name,
            'slug' => $slug,
        ]);
        \DB::connection('wp')->table('term_taxonomy')->insert([
            'term_id' => $termId,
            'taxonomy' => $taxonomy,
            'description' => '',
            'parent' => 0,
            'count' => 0,
        ]);

        return (object) ['term_id' => $termId, 'name' => $name, 'slug' => $slug];
    }

    /**
     * Update a taxonomy term.
     *
     * @param int $termId
     * @param string $name
     * @param string $slug
     * @return bool
     */
    public function updateTaxonomyTerm(int $termId, string $name, string $slug = ''): bool
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Term name is required.');
        }
        $tt = \DB::connection('wp')->table('term_taxonomy')->where('term_id', $termId)->first();
        $taxonomy = $tt ? $tt->taxonomy : null;
        $slug = $slug !== '' ? Str::slug($slug) : Str::slug($name);
        $slug = $this->ensureTermSlugUnique($slug, $taxonomy, $termId);

        \DB::connection('wp')->table('terms')
            ->where('term_id', $termId)
            ->update(['name' => $name, 'slug' => $slug]);

        return true;
    }

    /**
     * Delete a taxonomy term (and its term_taxonomy, term_relationships).
     *
     * @param int $termId
     * @return bool
     */
    public function deleteTaxonomyTerm(int $termId): bool
    {
        $termTaxonomyIds = \DB::connection('wp')->table('term_taxonomy')
            ->where('term_id', $termId)
            ->pluck('term_taxonomy_id');

        \DB::connection('wp')->table('term_relationships')
            ->whereIn('term_taxonomy_id', $termTaxonomyIds)
            ->delete();
        \DB::connection('wp')->table('term_taxonomy')->where('term_id', $termId)->delete();
        \DB::connection('wp')->table('terms')->where('term_id', $termId)->delete();

        return true;
    }

    /**
     * Ensure term slug is unique within taxonomy (or globally if taxonomy null).
     *
     * @param string $slug
     * @param string|null $taxonomy
     * @param int|null $excludeTermId
     * @return string
     */
    protected function ensureTermSlugUnique(string $slug, ?string $taxonomy = null, ?int $excludeTermId = null): string
    {
        $base = $slug;
        $n = 2;
        while (true) {
            $q = \DB::connection('wp')->table('terms as t')->where('t.slug', $slug);
            if ($excludeTermId) {
                $q->where('t.term_id', '!=', $excludeTermId);
            }
            if ($taxonomy) {
                $q->join('term_taxonomy as tt', 't.term_id', '=', 'tt.term_id')
                    ->where('tt.taxonomy', $taxonomy);
            }
            if (!$q->exists()) {
                return $slug;
            }
            $slug = $base . '-' . $n;
            $n++;
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
    
    /**
     * Get all locations as tree structure for tour selection.
     * Returns empty array if WP connection or query fails.
     *
     * @return array
     */
    public function getLocationsTree(): array
    {
        try {
            $locations = \DB::connection('wp')
                ->table('posts')
                ->where('post_type', 'location')
                ->where('post_status', 'publish')
                ->select('ID', 'post_title', 'post_parent')
                ->orderBy('post_parent')
                ->orderBy('post_title')
                ->get()
                ->toArray();

            return $this->buildLocationTree($locations);
        } catch (\Throwable $e) {
            \Log::warning('WpTourRepository::getLocationsTree failed', [
                'message' => $e->getMessage(),
                'connection' => 'wp',
            ]);
            return [];
        }
    }
    
    /**
     * Build hierarchical tree from flat location array.
     *
     * @param array $locations
     * @param int $parentId
     * @return array
     */
    protected function buildLocationTree(array $locations, int $parentId = 0): array
    {
        $tree = [];
        
        foreach ($locations as $location) {
            if ($location->post_parent == $parentId) {
                $children = $this->buildLocationTree($locations, $location->ID);
                
                $tree[] = [
                    'id' => $location->ID,
                    'title' => $location->post_title,
                    'parent_id' => $location->post_parent,
                    'children' => $children,
                ];
            }
        }
        
        return $tree;
    }
    
    /**
     * Parse multi_location meta value from WP format "_ID1_,_ID2_,_ID3_"
     *
     * @param string|null $multiLocationValue
     * @return array
     */
    public function parseMultiLocation(?string $multiLocationValue): array
    {
        if (empty($multiLocationValue)) {
            return [];
        }
        
        // Extract IDs from format "_54_,_55_,_56_"
        preg_match_all('/_(\d+)_/', $multiLocationValue, $matches);
        
        return array_map('intval', $matches[1] ?? []);
    }

    /**
     * Get a display string of location names from multi_location meta (for list table Destination column).
     *
     * @param string|null $multiLocationValue
     * @return string
     */
    public function getLocationNamesFromMultiLocation(?string $multiLocationValue): string
    {
        $ids = $this->parseMultiLocation($multiLocationValue);
        if (empty($ids)) {
            return '';
        }
        $locations = \DB::connection('wp')
            ->table('posts')
            ->where('post_type', 'location')
            ->whereIn('ID', $ids)
            ->pluck('post_title', 'ID');
        $names = [];
        foreach ($ids as $id) {
            if (isset($locations[$id])) {
                $names[] = $locations[$id];
            }
        }
        return implode(', ', $names);
    }
    
    /**
     * Format location IDs to WP multi_location format "_ID1_,_ID2_,_ID3_"
     *
     * @param array $locationIds
     * @return string
     */
    public function formatMultiLocation(array $locationIds): string
    {
        if (empty($locationIds)) {
            return '';
        }
        
        // Remove duplicates and sort
        $locationIds = array_unique(array_map('intval', $locationIds));
        sort($locationIds);
        
        // Format: "_54_,_55_,_56_" (NO spaces)
        $formatted = array_map(function($id) {
            return '_' . $id . '_';
        }, $locationIds);
        
        return implode(',', $formatted);
    }

    /**
     * Normalize string for location matching (lowercase, remove accents).
     */
    protected function normalizeLocationTitle(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $accents = ['à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','æ'=>'ae','ç'=>'c','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ñ'=>'n','ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ý'=>'y','ÿ'=>'y','œ'=>'oe'];
        return strtr($s, $accents);
    }

    /**
     * Create a location post in WordPress (Traveler location).
     *
     * @param int $parentId post_parent (0 for country)
     * @param string $title post_title
     * @return WpPost
     */
    public function createLocation(int $parentId, string $title): WpPost
    {
        $slug = Str::slug($title);
        $existing = \DB::connection('wp')->table('posts')
            ->where('post_type', 'location')
            ->where('post_name', $slug)
            ->exists();
        if ($existing) {
            $slug = $slug . '-' . substr(uniqid(), -4);
        }
        $now = now();
        $nowGmt = now('UTC');
        $post = WpPost::create([
            'post_author' => 1,
            'post_date' => $now,
            'post_date_gmt' => $nowGmt,
            'post_content' => '',
            'post_title' => $title,
            'post_excerpt' => '',
            'post_status' => 'publish',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_password' => '',
            'post_name' => $slug,
            'to_ping' => '',
            'pinged' => '',
            'post_modified' => $now,
            'post_modified_gmt' => $nowGmt,
            'post_content_filtered' => '',
            'post_parent' => $parentId,
            'guid' => '',
            'menu_order' => 0,
            'post_type' => 'location',
            'post_mime_type' => '',
            'comment_count' => 0,
        ]);
        $post->update(['guid' => config('app.url', '') . '/?post_type=location&p=' . $post->ID]);
        return $post->fresh();
    }

    /**
     * Find a location by parent and title (normalized match).
     *
     * @param int $parentId
     * @param string $title
     * @return object|null { ID, post_title } or null
     */
    public function findLocationByParentAndTitle(int $parentId, string $title): ?object
    {
        $locations = \DB::connection('wp')->table('posts')
            ->where('post_type', 'location')
            ->where('post_status', 'publish')
            ->where('post_parent', $parentId)
            ->select('ID', 'post_title')
            ->get();
        $key = $this->normalizeLocationTitle($title);
        foreach ($locations as $loc) {
            if ($this->normalizeLocationTitle($loc->post_title) === $key) {
                return $loc;
            }
        }
        return null;
    }

    /**
     * Ensure country location exists in WP; return its ID.
     *
     * @param string $countryCode ISO 3166-1 alpha-2 (e.g. FR, MA)
     * @return int WP post ID
     */
    public function ensureCountryLocation(string $countryCode): int
    {
        $countries = config('countries', []);
        $name = $countries[$countryCode] ?? $countryCode;
        $existing = $this->findLocationByParentAndTitle(0, $name);
        if ($existing) {
            return (int) $existing->ID;
        }
        $post = $this->createLocation(0, $name);
        return (int) $post->ID;
    }

    /**
     * Ensure city location exists in WP (under country); return its ID.
     *
     * @param string $countryCode ISO 3166-1 alpha-2
     * @param string $cityName
     * @return int WP post ID (city)
     */
    public function ensureCityLocation(string $countryCode, string $cityName): int
    {
        $countryId = $this->ensureCountryLocation($countryCode);
        $existing = $this->findLocationByParentAndTitle($countryId, $cityName);
        if ($existing) {
            return (int) $existing->ID;
        }
        $post = $this->createLocation($countryId, $cityName);
        return (int) $post->ID;
    }
    
    /**
     * Safely decode WordPress PHP serialized data.
     *
     * @param mixed $value
     * @return array
     */
    public function decodeWpSerialized($value): array
    {
        if (empty($value) || !is_string($value)) {
            return [];
        }
        
        // Check if it's serialized (starts with a:, s:, O:, etc.)
        if (!preg_match('/^[aOs]:\d+:/', $value)) {
            return [];
        }
        
        try {
            // Unserialize with security: block objects
            $decoded = @unserialize($value, ['allowed_classes' => false]);
            
            if ($decoded === false) {
                return [];
            }
            
            return is_array($decoded) ? $decoded : [];
        } catch (\Exception $e) {
            \Log::warning('Failed to unserialize WP data', [
                'error' => $e->getMessage(),
                'value_preview' => substr($value, 0, 100)
            ]);
            return [];
        }
    }
    
    /**
     * Encode array to WordPress PHP serialized format.
     *
     * @param array $data
     * @return string
     */
    public function encodeWpSerialized(array $data): string
    {
        return serialize($data);
    }
    
    /**
     * Get tour program from WordPress.
     *
     * @param int $postId
     * @return array ['style' => string, 'items' => array]
     */
    public function getTourProgram(int $postId): array
    {
        $post = WpPost::tours()->find($postId);
        
        if (!$post) {
            return ['style' => 'style1', 'items' => []];
        }
        
        $style = $post->getMeta('tours_program_style') ?: 'style1';
        $programSerialized = $post->getMeta('tours_program');
        
        $items = $this->decodeWpSerialized($programSerialized);
        
        // Normalize items structure
        $normalized = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $normalized[] = [
                    'title' => $item['title'] ?? '',
                    'desc' => $item['desc'] ?? $item['description'] ?? '',
                ];
            }
        }
        
        return [
            'style' => $style,
            'items' => $normalized,
        ];
    }
    
    /**
     * Save tour program to WordPress.
     *
     * @param int $postId
     * @param string $style
     * @param array $items
     * @return void
     */
    public function saveTourProgram(int $postId, string $style, array $items): void
    {
        $post = WpPost::tours()->findOrFail($postId);
        
        // Clean items: remove empty rows
        $cleanedItems = [];
        foreach ($items as $item) {
            if (!empty($item['title']) || !empty($item['desc'])) {
                $cleanedItems[] = [
                    'title' => $item['title'] ?? '',
                    'desc' => $item['desc'] ?? '',
                ];
            }
        }
        
        // Serialize for WordPress
        $serialized = $this->encodeWpSerialized($cleanedItems);
        
        // Save metas
        $post->setMeta('tours_program_style', $style);
        $post->setMeta('tours_program', $serialized);
    }

    /**
     * Libellé « zone » (région ou pays) pour la destination principale : évite d’afficher la ville seule.
     * Chaîne parent → enfant : pays → région → ville ; on préfère région si 3 niveaux, sinon pays.
     */
    public function getPrimaryDestinationAreaLabel(WpPost $post): ?string
    {
        $ids = $this->parseMultiLocation($post->getMeta('multi_location'));
        $primaryId = $ids[0] ?? null;
        if (! $primaryId) {
            return null;
        }

        return $this->getLocationAreaLabelById((int) $primaryId);
    }

    public function getLocationAreaLabelById(int $locationId): ?string
    {
        $chain = [];
        $currentId = $locationId;
        $guard = 0;
        while ($currentId > 0 && $guard++ < 25) {
            $row = \DB::connection('wp')->table('posts')
                ->where('post_type', 'location')
                ->where('ID', $currentId)
                ->first(['ID', 'post_title', 'post_parent']);
            if (! $row) {
                break;
            }
            $chain[] = [
                'id' => (int) $row->ID,
                'title' => (string) $row->post_title,
                'parent' => (int) $row->post_parent,
            ];
            $currentId = (int) $row->post_parent;
        }
        if (count($chain) >= 3) {
            return $chain[1]['title'];
        }
        if (count($chain) === 2) {
            return $chain[1]['title'];
        }
        if (count($chain) === 1) {
            return $chain[0]['title'];
        }

        return null;
    }
}
