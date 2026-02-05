<?php

namespace App\Services;

use App\Models\Voyage;
use App\Models\TravelProgramDay;
use App\Models\TravelDayItem;
use App\Repositories\WpRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Bidirectional WordPress Tour Synchronization Service
 * 
 * CONFLICT RESOLUTION: WordPress WINS
 * - If WP modified after last sync => overwrite Laravel
 * - If both modified => WP wins
 */
class WpTourSyncService
{
    protected WpRepository $wp;
    protected WpToursProgramParser $programParser;
    
    public function __construct(WpRepository $wp, WpToursProgramParser $programParser)
    {
        $this->wp = $wp;
        $this->programParser = $programParser;
    }

    /**
     * Create WP tour from Laravel voyage (Laravel -> WP)
     */
    public function createWpTourFromLaravel(int $voyageId): array
    {
        $voyage = Voyage::with(['programDays.dayItems'])->findOrFail($voyageId);
        
        if ($voyage->wp_post_id) {
            throw new \Exception("Voyage #{$voyageId} is already linked to WP post #{$voyage->wp_post_id}. Use update instead.");
        }

        DB::beginTransaction();
        try {
            // Create WP post
            $wpPostId = $this->wp->createPost([
                'post_type' => 'st_tours',
                'post_title' => $voyage->name,
                'post_name' => $voyage->slug,
                'post_content' => $voyage->description ?? '',
                'post_excerpt' => $voyage->accroche ?? '',
                'post_status' => $this->mapLaravelStatusToWp($voyage->status),
            ]);

            // Link voyage to WP
            $voyage->update(['wp_post_id' => $wpPostId]);

            // Sync all data
            $this->syncLaravelToWpMetas($voyage);
            $this->syncLaravelToWpTaxonomies($voyage);
            $this->syncLaravelToWpImages($voyage);
            $this->syncLaravelToWpProgram($voyage);

            // Store meta to link back to Laravel
            $this->wp->updatePostMeta($wpPostId, '_aj_laravel_voyage_id', $voyage->id);

            // Update sync state
            $this->updateSyncState($voyage, $wpPostId);

            DB::commit();

            Log::info("WP tour created from Laravel", [
                'voyage_id' => $voyageId,
                'wp_post_id' => $wpPostId,
            ]);

            return [
                'success' => true,
                'wp_post_id' => $wpPostId,
                'voyage_id' => $voyageId,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create WP tour from Laravel", [
                'voyage_id' => $voyageId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update WP tour from Laravel voyage (Laravel -> WP)
     */
    public function updateWpTourFromLaravel(int $voyageId, bool $force = false): array
    {
        $voyage = Voyage::with(['programDays.dayItems'])->findOrFail($voyageId);
        
        if (!$voyage->wp_post_id) {
            throw new \Exception("Voyage #{$voyageId} is not linked to any WP post. Use create instead.");
        }

        $wpPostId = $voyage->wp_post_id;

        // Check conflict (WP wins)
        if (!$force && $this->hasWpConflict($voyage, $wpPostId)) {
            Log::warning("WP conflict detected, pulling from WP instead", [
                'voyage_id' => $voyageId,
                'wp_post_id' => $wpPostId,
            ]);
            return $this->upsertLaravelVoyageFromWp($wpPostId);
        }

        DB::beginTransaction();
        try {
            // Update WP post core fields
            $this->wp->updatePost($wpPostId, [
                'post_title' => $voyage->name,
                'post_name' => $voyage->slug,
                'post_content' => $voyage->description ?? '',
                'post_excerpt' => $voyage->accroche ?? '',
                'post_status' => $this->mapLaravelStatusToWp($voyage->status),
            ]);

            // Sync all data
            $this->syncLaravelToWpMetas($voyage);
            $this->syncLaravelToWpTaxonomies($voyage);
            $this->syncLaravelToWpImages($voyage);
            $this->syncLaravelToWpProgram($voyage);

            // Update sync state
            $this->updateSyncState($voyage, $wpPostId);

            DB::commit();

            Log::info("WP tour updated from Laravel", [
                'voyage_id' => $voyageId,
                'wp_post_id' => $wpPostId,
            ]);

            return [
                'success' => true,
                'wp_post_id' => $wpPostId,
                'voyage_id' => $voyageId,
                'action' => 'updated',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update WP tour from Laravel", [
                'voyage_id' => $voyageId,
                'wp_post_id' => $wpPostId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Upsert Laravel voyage from WP post (WP -> Laravel)
     */
    public function upsertLaravelVoyageFromWp(int $wpPostId): array
    {
        $wpPost = $this->wp->getPost($wpPostId);
        
        if (!$wpPost || $wpPost['post_type'] !== 'st_tours') {
            throw new \Exception("WP post #{$wpPostId} not found or not a tour");
        }

        DB::beginTransaction();
        try {
            // Find or create voyage
            $voyage = Voyage::where('wp_post_id', $wpPostId)->first();
            
            if (!$voyage) {
                // Check if there's a Laravel voyage linked via meta
                $laravelVoyageId = $this->wp->getPostMeta($wpPostId, '_aj_laravel_voyage_id');
                if ($laravelVoyageId) {
                    $voyage = Voyage::find($laravelVoyageId);
                }
            }

            $isNew = !$voyage;
            
            if (!$voyage) {
                $voyage = new Voyage();
            }

            // Map WP core fields to Laravel
            $voyage->wp_post_id = $wpPostId;
            $voyage->name = $wpPost['post_title'];
            $voyage->slug = $wpPost['post_name'];
            $voyage->description = $wpPost['post_content'];
            $voyage->accroche = $wpPost['post_excerpt'];
            $voyage->status = $this->mapWpStatusToLaravel($wpPost['post_status']);

            // Sync metas
            $this->syncWpToLaravelMetas($voyage, $wpPostId);
            
            // Sync images
            $this->syncWpToLaravelImages($voyage, $wpPostId);

            $voyage->save();

            // Sync program
            $this->syncWpToLaravelProgram($voyage, $wpPostId);

            // Update sync state
            $this->updateSyncState($voyage, $wpPostId);

            // Update Laravel->WP link meta
            $this->wp->updatePostMeta($wpPostId, '_aj_laravel_voyage_id', $voyage->id);

            DB::commit();

            Log::info("Laravel voyage upserted from WP", [
                'voyage_id' => $voyage->id,
                'wp_post_id' => $wpPostId,
                'action' => $isNew ? 'created' : 'updated',
            ]);

            return [
                'success' => true,
                'voyage_id' => $voyage->id,
                'wp_post_id' => $wpPostId,
                'action' => $isNew ? 'created' : 'updated',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to upsert Laravel voyage from WP", [
                'wp_post_id' => $wpPostId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sync Laravel meta fields to WP postmeta
     */
    protected function syncLaravelToWpMetas(Voyage $voyage): void
    {
        $wpPostId = $voyage->wp_post_id;
        
        $metas = [
            'min_people' => $voyage->min_people ?? '',
            'max_people' => $voyage->max_people ?? '',
            'tour_price_by' => $voyage->tour_price_by ?? '',
            'is_featured' => $voyage->is_featured ? 'on' : 'off',
            'st_google_map' => $voyage->st_google_map ?? '',
            'multi_location' => $voyage->multi_location ?? '',
            'discount_by_people_type' => $voyage->discount_by_people_type ?? '',
            'discount_type' => $voyage->discount_type ?? '',
            'calculator_discount_by_people_type' => $voyage->calculator_discount_by_people_type ?? '',
            'hide_adult_in_booking_form' => $voyage->hide_adult_in_booking_form ? '1' : '0',
            'st_tour_external_booking' => $voyage->st_tour_external_booking ?? '',
            'tours_include' => is_array($voyage->tours_include) ? implode("\n", $voyage->tours_include) : ($voyage->tours_include ?? ''),
            'tours_exclude' => is_array($voyage->tours_exclude) ? implode("\n", $voyage->tours_exclude) : ($voyage->tours_exclude ?? ''),
            'tours_highlight' => is_array($voyage->tours_highlight) ? implode("\n", $voyage->tours_highlight) : ($voyage->tours_highlight ?? ''),
            'tours_program_style' => $voyage->tours_program_style ?? 'list',
        ];

        foreach ($metas as $key => $value) {
            $this->wp->updatePostMeta($wpPostId, $key, $value);
        }

        // Payment gateway metas
        if ($voyage->payment_gateway_metas && is_array($voyage->payment_gateway_metas)) {
            foreach ($voyage->payment_gateway_metas as $key => $value) {
                $this->wp->updatePostMeta($wpPostId, $key, $value);
            }
        }
    }

    /**
     * Sync WP postmeta to Laravel
     */
    protected function syncWpToLaravelMetas(Voyage $voyage, int $wpPostId): void
    {
        $allMetas = $this->wp->getAllPostMeta($wpPostId);

        $voyage->min_people = (int)($allMetas['min_people'] ?? 0);
        $voyage->max_people = (int)($allMetas['max_people'] ?? 0);
        $voyage->tour_price_by = $allMetas['tour_price_by'] ?? null;
        $voyage->is_featured = ($allMetas['is_featured'] ?? 'off') === 'on';
        $voyage->st_google_map = $allMetas['st_google_map'] ?? null;
        $voyage->multi_location = $allMetas['multi_location'] ?? null;
        $voyage->discount_by_people_type = $allMetas['discount_by_people_type'] ?? null;
        $voyage->discount_type = $allMetas['discount_type'] ?? null;
        $voyage->calculator_discount_by_people_type = $allMetas['calculator_discount_by_people_type'] ?? null;
        $voyage->hide_adult_in_booking_form = ($allMetas['hide_adult_in_booking_form'] ?? '0') === '1';
        $voyage->st_tour_external_booking = $allMetas['st_tour_external_booking'] ?? null;

        // Parse text lists to JSON arrays
        $voyage->tours_include = $this->parseTextToArray($allMetas['tours_include'] ?? '');
        $voyage->tours_exclude = $this->parseTextToArray($allMetas['tours_exclude'] ?? '');
        $voyage->tours_highlight = $this->parseTextToArray($allMetas['tours_highlight'] ?? '');
        $voyage->tours_program_style = $allMetas['tours_program_style'] ?? 'list';

        // Extract payment gateway metas
        $paymentMetas = [];
        foreach ($allMetas as $key => $value) {
            if (str_starts_with($key, 'is_meta_payment_gateway_')) {
                $paymentMetas[$key] = $value;
            }
        }
        $voyage->payment_gateway_metas = !empty($paymentMetas) ? $paymentMetas : null;
    }

    /**
     * Sync Laravel taxonomies to WP
     */
    protected function syncLaravelToWpTaxonomies(Voyage $voyage): void
    {
        // TODO: Implement based on your voyage taxonomy storage
        // For now, placeholder
        $wpPostId = $voyage->wp_post_id;
        
        // Example: if you have destination/type fields
        // $this->wp->setPostTerms($wpPostId, 'st_tour_type', [$voyage->tour_type]);
    }

    /**
     * Sync Laravel images to WP
     */
    protected function syncLaravelToWpImages(Voyage $voyage): void
    {
        $wpPostId = $voyage->wp_post_id;

        // Featured image: try to use existing WP attachment or create placeholder
        if ($voyage->featured_image) {
            // TODO: Implement image import logic
            // For now, keep existing _thumbnail_id if any
        }

        // Gallery: use gallery_wp_ids if available
        if ($voyage->gallery_wp_ids) {
            $this->wp->updatePostMeta($wpPostId, 'gallery', $voyage->gallery_wp_ids);
        }
    }

    /**
     * Sync WP images to Laravel
     */
    protected function syncWpToLaravelImages(Voyage $voyage, int $wpPostId): void
    {
        $allMetas = $this->wp->getAllPostMeta($wpPostId);

        // Cache gallery WP IDs
        $voyage->gallery_wp_ids = $allMetas['gallery'] ?? null;

        // Featured image: get thumbnail URL for display
        $thumbnailId = $allMetas['_thumbnail_id'] ?? null;
        if ($thumbnailId) {
            $attachmentUrl = $this->wp->getAttachmentUrl($thumbnailId);
            if ($attachmentUrl) {
                $voyage->featured_image = $attachmentUrl;
            }
        }
    }

    /**
     * Sync Laravel program to WP tours_program meta
     */
    protected function syncLaravelToWpProgram(Voyage $voyage): void
    {
        $wpPostId = $voyage->wp_post_id;
        
        $programDays = $voyage->programDays()->with('dayItems')->get();
        
        if ($programDays->isEmpty()) {
            return;
        }

        // Generate WP tours_program format (serialized array)
        $toursProgramGenerated = $this->programParser->generateWpProgramFromLaravel($programDays);
        
        $this->wp->updatePostMeta($wpPostId, 'tours_program', $toursProgramGenerated);
    }

    /**
     * Sync WP tours_program to Laravel tables
     */
    protected function syncWpToLaravelProgram(Voyage $voyage, int $wpPostId): void
    {
        $toursProgram = $this->wp->getPostMeta($wpPostId, 'tours_program');
        
        if (!$toursProgram) {
            return;
        }

        $parsed = $this->programParser->parseWpProgramToLaravel($toursProgram);
        
        // Delete existing program
        TravelProgramDay::where('voyage_id', $voyage->id)->delete();
        TravelDayItem::where('voyage_id', $voyage->id)->delete();

        // Create new program days
        foreach ($parsed as $dayData) {
            $programDay = TravelProgramDay::create([
                'voyage_id' => $voyage->id,
                'wp_post_id' => $wpPostId,
                'day_number' => $dayData['day_number'],
                'title' => $dayData['title'] ?? '',
                'description' => $dayData['description'] ?? '',
            ]);

            // Create day items if any
            if (!empty($dayData['items'])) {
                foreach ($dayData['items'] as $index => $itemData) {
                    TravelDayItem::create([
                        'voyage_id' => $voyage->id,
                        'travel_program_day_id' => $programDay->id,
                        'day_number' => $dayData['day_number'],
                        'sort_order' => $index,
                        'type' => $itemData['type'] ?? 'activity',
                        'title' => $itemData['title'] ?? '',
                        'description' => $itemData['description'] ?? '',
                        'time' => $itemData['time'] ?? null,
                    ]);
                }
            }
        }
    }

    /**
     * Update sync state after successful sync
     */
    protected function updateSyncState(Voyage $voyage, int $wpPostId): void
    {
        $wpPost = $this->wp->getPost($wpPostId);
        
        $voyage->update([
            'wp_synced_at' => now(),
            'wp_sync_hash' => $this->computeWpSnapshotHash($wpPostId),
            'wp_last_modified_gmt_cache' => $wpPost['post_modified_gmt'] ?? null,
        ]);
    }

    /**
     * Compute snapshot hash of WP post state
     */
    public function computeWpSnapshotHash(int $wpPostId): string
    {
        $wpPost = $this->wp->getPost($wpPostId);
        $allMetas = $this->wp->getAllPostMeta($wpPostId);

        // Ignore RankMath and transient metas
        $filteredMetas = array_filter($allMetas, function($key) {
            return !str_starts_with($key, 'rank_math_') && !str_starts_with($key, '_transient_');
        }, ARRAY_FILTER_USE_KEY);

        ksort($filteredMetas);

        $snapshot = [
            'post' => [
                'post_title' => $wpPost['post_title'] ?? '',
                'post_name' => $wpPost['post_name'] ?? '',
                'post_content' => $wpPost['post_content'] ?? '',
                'post_excerpt' => $wpPost['post_excerpt'] ?? '',
                'post_status' => $wpPost['post_status'] ?? '',
            ],
            'metas' => $filteredMetas,
        ];

        return md5(json_encode($snapshot));
    }

    /**
     * Check if WP has been modified after last sync (conflict detection)
     */
    protected function hasWpConflict(Voyage $voyage, int $wpPostId): bool
    {
        if (!$voyage->wp_last_modified_gmt_cache) {
            return false; // No baseline, no conflict
        }

        $wpPost = $this->wp->getPost($wpPostId);
        $wpModifiedGmt = $wpPost['post_modified_gmt'] ?? null;

        if (!$wpModifiedGmt) {
            return false;
        }

        $wpModifiedCarbon = Carbon::parse($wpModifiedGmt);
        $cacheCarbon = Carbon::parse($voyage->wp_last_modified_gmt_cache);

        // If WP modified after cache => WP was changed externally
        return $wpModifiedCarbon->greaterThan($cacheCarbon);
    }

    /**
     * Detect and resolve conflict (WP WINS)
     */
    public function detectConflictAndResolve(int $wpPostId, int $voyageId): array
    {
        $voyage = Voyage::findOrFail($voyageId);

        if ($this->hasWpConflict($voyage, $wpPostId)) {
            Log::info("Conflict detected: WP wins, pulling from WP", [
                'voyage_id' => $voyageId,
                'wp_post_id' => $wpPostId,
            ]);

            return $this->upsertLaravelVoyageFromWp($wpPostId);
        }

        return [
            'success' => true,
            'conflict' => false,
            'message' => 'No conflict detected',
        ];
    }

    /**
     * Helper: Map Laravel status to WP
     */
    protected function mapLaravelStatusToWp(string $laravelStatus): string
    {
        return match($laravelStatus) {
            'published' => 'publish',
            'draft' => 'draft',
            'archived' => 'trash',
            default => 'draft',
        };
    }

    /**
     * Helper: Map WP status to Laravel
     */
    protected function mapWpStatusToLaravel(string $wpStatus): string
    {
        return match($wpStatus) {
            'publish' => 'published',
            'draft' => 'draft',
            'trash' => 'archived',
            'pending' => 'draft',
            default => 'draft',
        };
    }

    /**
     * Helper: Parse text with newlines to array
     */
    protected function parseTextToArray(string $text): ?array
    {
        if (empty($text)) {
            return null;
        }

        $lines = array_filter(array_map('trim', explode("\n", $text)));
        return !empty($lines) ? array_values($lines) : null;
    }
}
