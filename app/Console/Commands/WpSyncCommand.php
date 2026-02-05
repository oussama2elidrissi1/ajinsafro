<?php

namespace App\Console\Commands;

use App\Models\Voyage;
use App\Services\WpTourSyncService;
use App\Repositories\WpRepository;
use App\Observers\VoyageObserver;
use Illuminate\Console\Command;

class WpSyncCommand extends Command
{
    protected $signature = 'wp:sync 
                            {action : Action to perform: push, pull, pull-all, status}
                            {--id= : Voyage ID (for push) or WP Post ID (for pull)}
                            {--force : Force sync even if no changes detected}';

    protected $description = 'Synchronize tours between Laravel and WordPress';

    protected WpTourSyncService $syncService;
    protected WpRepository $wpRepo;

    public function __construct(WpTourSyncService $syncService, WpRepository $wpRepo)
    {
        parent::__construct();
        $this->syncService = $syncService;
        $this->wpRepo = $wpRepo;
    }

    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'push':
                return $this->handlePush();
            
            case 'pull':
                return $this->handlePull();
            
            case 'pull-all':
                return $this->handlePullAll();
            
            case 'status':
                return $this->handleStatus();
            
            default:
                $this->error("Unknown action: {$action}");
                $this->info("Available actions: push, pull, pull-all, status");
                return 1;
        }
    }

    /**
     * Push Laravel → WP
     */
    protected function handlePush()
    {
        $voyageId = $this->option('id');

        if (!$voyageId) {
            $this->error('--id required for push');
            return 1;
        }

        $voyage = Voyage::find($voyageId);

        if (!$voyage) {
            $this->error("Voyage #{$voyageId} not found");
            return 1;
        }

        $this->info("Pushing voyage #{$voyageId} to WordPress...");

        try {
            if ($voyage->wp_post_id) {
                $result = $this->syncService->updateWpTourFromLaravel($voyageId, $this->option('force'));
                $this->info("✓ WP tour #{$result['wp_post_id']} updated");
            } else {
                $result = $this->syncService->createWpTourFromLaravel($voyageId);
                $this->info("✓ WP tour #{$result['wp_post_id']} created");
            }

            $this->table(['Field', 'Value'], [
                ['Voyage ID', $result['voyage_id']],
                ['WP Post ID', $result['wp_post_id']],
                ['Action', $result['action'] ?? 'created'],
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error("✗ Failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Pull WP → Laravel
     */
    protected function handlePull()
    {
        $wpPostId = $this->option('id');

        if (!$wpPostId) {
            $this->error('--id required for pull (WP post ID)');
            return 1;
        }

        $this->info("Pulling WP post #{$wpPostId} to Laravel...");

        try {
            // Disable observer to prevent loop
            $result = VoyageObserver::withoutSync(function() use ($wpPostId) {
                return $this->syncService->upsertLaravelVoyageFromWp($wpPostId);
            });

            $this->info("✓ Voyage #{$result['voyage_id']} {$result['action']}");

            $this->table(['Field', 'Value'], [
                ['Voyage ID', $result['voyage_id']],
                ['WP Post ID', $result['wp_post_id']],
                ['Action', $result['action']],
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error("✗ Failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Pull all WP tours to Laravel
     */
    protected function handlePullAll()
    {
        $this->info("Pulling ALL st_tours from WordPress...");

        try {
            $wpTours = $this->wpRepo->findTours(['post_status' => 'publish'], 1000);
            
            $this->info("Found " . count($wpTours) . " published tours in WP");

            $created = 0;
            $updated = 0;
            $failed = 0;

            $bar = $this->output->createProgressBar(count($wpTours));
            $bar->start();

            foreach ($wpTours as $wpTour) {
                try {
                    $result = VoyageObserver::withoutSync(function() use ($wpTour) {
                        return $this->syncService->upsertLaravelVoyageFromWp($wpTour['ID']);
                    });

                    if ($result['action'] === 'created') {
                        $created++;
                    } else {
                        $updated++;
                    }

                } catch (\Exception $e) {
                    $failed++;
                    $this->newLine();
                    $this->warn("Failed WP post #{$wpTour['ID']}: " . $e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->table(['Status', 'Count'], [
                ['Created', $created],
                ['Updated', $updated],
                ['Failed', $failed],
                ['Total', count($wpTours)],
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error("✗ Failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show sync status
     */
    protected function handleStatus()
    {
        $voyageId = $this->option('id');

        if ($voyageId) {
            return $this->showVoyageStatus($voyageId);
        }

        // Show global stats
        $total = Voyage::count();
        $linked = Voyage::whereNotNull('wp_post_id')->count();
        $synced = Voyage::whereNotNull('wp_synced_at')->count();

        $this->table(['Metric', 'Count'], [
            ['Total Voyages', $total],
            ['Linked to WP', $linked],
            ['Ever Synced', $synced],
            ['Not Linked', $total - $linked],
        ]);

        // Recent sync activity
        $recent = Voyage::whereNotNull('wp_synced_at')
            ->orderBy('wp_synced_at', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'wp_post_id', 'wp_synced_at']);

        if ($recent->isNotEmpty()) {
            $this->newLine();
            $this->info("Recent Sync Activity:");
            $this->table(['Voyage ID', 'Name', 'WP Post ID', 'Last Synced'], 
                $recent->map(fn($v) => [
                    $v->id,
                    \Str::limit($v->name, 30),
                    $v->wp_post_id,
                    $v->wp_synced_at->diffForHumans(),
                ])
            );
        }

        return 0;
    }

    /**
     * Show specific voyage sync status
     */
    protected function showVoyageStatus(int $voyageId)
    {
        $voyage = Voyage::find($voyageId);

        if (!$voyage) {
            $this->error("Voyage #{$voyageId} not found");
            return 1;
        }

        $this->info("Sync Status for Voyage #{$voyageId}: {$voyage->name}");
        $this->newLine();

        $data = [
            ['WP Post ID', $voyage->wp_post_id ?? 'Not linked'],
            ['Last Synced', $voyage->wp_synced_at ? $voyage->wp_synced_at->format('Y-m-d H:i:s') : 'Never'],
            ['Sync Hash', $voyage->wp_sync_hash ?? 'N/A'],
            ['WP Modified Cache', $voyage->wp_last_modified_gmt_cache ? $voyage->wp_last_modified_gmt_cache->format('Y-m-d H:i:s') : 'N/A'],
        ];

        // Check current WP state
        if ($voyage->wp_post_id) {
            try {
                $wpPost = $this->wpRepo->getPost($voyage->wp_post_id);
                if ($wpPost) {
                    $data[] = ['WP Post Status', $wpPost['post_status']];
                    $data[] = ['WP Modified', $wpPost['post_modified_gmt']];
                    
                    $currentHash = $this->syncService->computeWpSnapshotHash($voyage->wp_post_id);
                    $hashMatch = $currentHash === $voyage->wp_sync_hash;
                    
                    $data[] = ['Current Hash', $currentHash];
                    $data[] = ['Hash Match', $hashMatch ? 'YES ✓' : 'NO (out of sync)'];
                }
            } catch (\Exception $e) {
                $data[] = ['WP Status', 'Error: ' . $e->getMessage()];
            }
        }

        $this->table(['Field', 'Value'], $data);

        return 0;
    }
}
