<?php

namespace App\Observers;

use App\Models\Voyage;
use App\Services\WpTourSyncService;
use Illuminate\Support\Facades\Log;

/**
 * Voyage Observer
 * Auto-syncs to WordPress on create/update
 */
class VoyageObserver
{
    protected WpTourSyncService $syncService;
    
    /**
     * Flag to prevent infinite loops during sync
     */
    public static bool $syncEnabled = true;

    public function __construct(WpTourSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Handle the Voyage "created" event.
     */
    public function created(Voyage $voyage): void
    {
        if (!self::$syncEnabled || !config('wordpress.auto_sync_enabled', true)) {
            return;
        }

        try {
            // Create in WP
            $this->syncService->createWpTourFromLaravel($voyage->id);
            
            Log::info("Auto-sync: WP tour created", ['voyage_id' => $voyage->id]);
        } catch (\Exception $e) {
            Log::error("Auto-sync failed on create", [
                'voyage_id' => $voyage->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Voyage "updated" event.
     */
    public function updated(Voyage $voyage): void
    {
        if (!self::$syncEnabled || !config('wordpress.auto_sync_enabled', true)) {
            return;
        }

        // Skip if this is a sync update (prevent loop)
        if ($voyage->isDirty('wp_synced_at') && !$voyage->isDirty('name', 'description')) {
            return;
        }

        try {
            if ($voyage->wp_post_id) {
                // Update existing WP tour
                $this->syncService->updateWpTourFromLaravel($voyage->id);
                
                Log::info("Auto-sync: WP tour updated", ['voyage_id' => $voyage->id]);
            } else {
                // Create if not linked yet
                $this->syncService->createWpTourFromLaravel($voyage->id);
                
                Log::info("Auto-sync: WP tour created on update", ['voyage_id' => $voyage->id]);
            }
        } catch (\Exception $e) {
            Log::error("Auto-sync failed on update", [
                'voyage_id' => $voyage->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Voyage "deleted" event.
     * 
     * Note: We don't auto-delete from WP to prevent data loss.
     * You can implement this manually if needed.
     */
    public function deleted(Voyage $voyage): void
    {
        // Optional: Implement WP deletion if needed
        // For safety, we don't auto-delete from WP
        
        Log::info("Voyage deleted (WP not affected)", ['voyage_id' => $voyage->id]);
    }

    /**
     * Temporarily disable sync (useful during batch operations)
     */
    public static function withoutSync(callable $callback)
    {
        $previousState = self::$syncEnabled;
        self::$syncEnabled = false;

        try {
            return $callback();
        } finally {
            self::$syncEnabled = $previousState;
        }
    }
}
