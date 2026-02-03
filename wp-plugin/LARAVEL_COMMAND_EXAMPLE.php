<?php

/**
 * Laravel Artisan Command for WordPress Sync
 * 
 * Place this file in: app/Console/Commands/SyncToursToWordPress.php
 * 
 * Usage:
 * php artisan tours:sync-wordpress
 * php artisan tours:sync-wordpress --id=1
 * php artisan tours:sync-wordpress --all
 * php artisan tours:sync-wordpress --modified
 */

namespace App\Console\Commands;

use App\Models\Voyage;
use App\Services\WordPress\TourSyncService;
use Illuminate\Console\Command;

class SyncToursToWordPress extends Command
{
    protected $signature = 'tours:sync-wordpress
                            {--id= : Sync specific tour by ID}
                            {--all : Sync all tours}
                            {--modified : Sync only modified tours}
                            {--force : Force sync even if not modified}';

    protected $description = 'Sync tours from Laravel to WordPress';

    private TourSyncService $syncService;

    public function __construct(TourSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    public function handle()
    {
        $this->info('🚀 Starting WordPress sync...');

        // Get tours to sync
        $tours = $this->getToursToSync();

        if ($tours->isEmpty()) {
            $this->warn('No tours to sync.');
            return 0;
        }

        $this->info("Found {$tours->count()} tour(s) to sync");

        $progressBar = $this->output->createProgressBar($tours->count());
        $progressBar->start();

        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($tours as $tour) {
            try {
                // Check if needs sync (unless forced)
                if (!$this->option('force') && !$this->syncService->needsSync($tour)) {
                    $this->line("\n⏭️  Tour #{$tour->id} '{$tour->name}' - No changes, skipping");
                    $results['skipped']++;
                    $progressBar->advance();
                    continue;
                }

                // Perform sync
                $result = $this->syncService->syncTourToWordPress($tour);
                
                $wpPostId = $result['data']['post_id'] ?? 'N/A';
                $action = $result['data']['action'] ?? 'unknown';
                
                $this->line("\n✅ Tour #{$tour->id} '{$tour->name}' → WP Post #{$wpPostId} ({$action})");
                $results['success']++;

            } catch (\Exception $e) {
                $this->line("\n❌ Tour #{$tour->id} '{$tour->name}' - Error: {$e->getMessage()}");
                $results['failed']++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('📊 Sync Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['✅ Success', $results['success']],
                ['❌ Failed', $results['failed']],
                ['⏭️  Skipped', $results['skipped']],
                ['📦 Total', $tours->count()],
            ]
        );

        return $results['failed'] > 0 ? 1 : 0;
    }

    private function getToursToSync()
    {
        // Sync specific tour
        if ($tourId = $this->option('id')) {
            $tour = Voyage::find($tourId);
            if (!$tour) {
                $this->error("Tour #{$tourId} not found");
                return collect();
            }
            return collect([$tour]);
        }

        // Sync all tours
        if ($this->option('all')) {
            return Voyage::with(['images', 'programDays'])->get();
        }

        // Sync only modified tours (default)
        if ($this->option('modified')) {
            return Voyage::with(['images', 'programDays'])
                ->where(function ($query) {
                    $query->whereNull('wp_synced_at')
                        ->orWhereColumn('updated_at', '>', 'wp_synced_at');
                })
                ->get();
        }

        // Default: sync modified tours
        return Voyage::with(['images', 'programDays'])
            ->where(function ($query) {
                $query->whereNull('wp_synced_at')
                    ->orWhereColumn('updated_at', '>', 'wp_synced_at');
            })
            ->get();
    }
}

/**
 * Register the command in app/Console/Kernel.php:
 * 
 * protected $commands = [
 *     \App\Console\Commands\SyncToursToWordPress::class,
 * ];
 * 
 * Or schedule it:
 * 
 * protected function schedule(Schedule $schedule)
 * {
 *     // Sync modified tours every hour
 *     $schedule->command('tours:sync-wordpress --modified')
 *              ->hourly()
 *              ->withoutOverlapping();
 * 
 *     // Or once per day at 2 AM
 *     $schedule->command('tours:sync-wordpress --all')
 *              ->dailyAt('02:00');
 * }
 */
