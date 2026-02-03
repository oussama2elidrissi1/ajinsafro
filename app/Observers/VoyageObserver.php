<?php

namespace App\Observers;

use App\Models\Voyage;
use App\Services\Sync\SyncContext;
use App\Services\Sync\WpSyncService;
use Illuminate\Support\Facades\Log;

class VoyageObserver
{
    protected WpSyncService $wpSyncService;
    protected SyncContext $syncContext;

    public function __construct(WpSyncService $wpSyncService, SyncContext $syncContext)
    {
        $this->wpSyncService = $wpSyncService;
        $this->syncContext = $syncContext;
    }

    /**
     * Handle the Voyage "created" event.
     */
    public function created(Voyage $voyage): void
    {
        $this->syncToWordPress($voyage, 'created');
    }

    /**
     * Handle the Voyage "updated" event.
     */
    public function updated(Voyage $voyage): void
    {
        $this->syncToWordPress($voyage, 'updated');
    }

    /**
     * Handle the Voyage "deleted" event.
     */
    public function deleted(Voyage $voyage): void
    {
        // Skip if sync is from WordPress
        if ($this->syncContext->isFromWp()) {
            Log::info('[VoyageObserver] Skipping deletion sync - source is WordPress', [
                'voyage_id' => $voyage->id,
            ]);
            return;
        }

        // Delete from WordPress
        $result = $this->wpSyncService->deleteVoyage($voyage);

        if (!$result['success']) {
            Log::warning('[VoyageObserver] Failed to delete from WordPress', [
                'voyage_id' => $voyage->id,
                'message' => $result['message'],
            ]);
        }
    }

    /**
     * Sync voyage to WordPress if needed.
     *
     * @param Voyage $voyage
     * @param string $event
     */
    protected function syncToWordPress(Voyage $voyage, string $event): void
    {
        // Skip if sync is coming from WordPress (to prevent loops)
        if ($this->syncContext->isFromWp()) {
            Log::info('[VoyageObserver] Skipping sync - source is WordPress', [
                'voyage_id' => $voyage->id,
                'event' => $event,
            ]);
            return;
        }

        // Skip if no changes detected (based on sync hash)
        if ($event === 'updated' && $this->wpSyncService->shouldSkipSync($voyage)) {
            return;
        }

        // Push to WordPress
        $result = $this->wpSyncService->upsertVoyage($voyage);

        if (!$result['success']) {
            Log::warning('[VoyageObserver] Failed to sync to WordPress', [
                'voyage_id' => $voyage->id,
                'event' => $event,
                'message' => $result['message'],
            ]);
        }
    }
}
