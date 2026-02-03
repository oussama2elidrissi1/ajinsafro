<?php

namespace App\Console\Commands;

use App\Models\Voyage;
use App\Services\Wp\WpTourImporter;
use Illuminate\Console\Command;

class WpImportTours extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wp:import-tours 
                            {--all : Import all published st_tours from WordPress}
                            {--wp_id= : Import a specific WordPress post by ID}
                            {--limit=0 : Limit the number of tours to import (0 = no limit)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import WordPress TravelerWP tours (st_tours) into Laravel voyages table';

    protected WpTourImporter $importer;

    public function __construct(WpTourImporter $importer)
    {
        parent::__construct();
        $this->importer = $importer;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('   WordPress Tour Importer - TravelerWP → Laravel');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        $startTime = microtime(true);
        $wpId = $this->option('wp_id');
        $importAll = $this->option('all');
        $limit = (int) $this->option('limit');

        if (!$importAll && !$wpId) {
            $this->error('❌ You must specify either --all or --wp_id=<ID>');
            $this->newLine();
            $this->info('Examples:');
            $this->line('  php artisan wp:import-tours --all');
            $this->line('  php artisan wp:import-tours --all --limit=10');
            $this->line('  php artisan wp:import-tours --wp_id=123');
            return self::FAILURE;
        }

        if ($wpId) {
            // Import single tour
            return $this->importSingleTour((int) $wpId);
        }

        // Import all tours
        return $this->importAllTours($limit, $startTime);
    }

    /**
     * Import a single tour by WordPress post ID.
     */
    protected function importSingleTour(int $wpPostId): int
    {
        $this->info("🔄 Importing WordPress tour: {$wpPostId}...");
        $this->newLine();

        $result = $this->importer->importOne($wpPostId);

        if ($result['status'] === 'error') {
            $this->error("❌ Error: {$result['message']}");
            return self::FAILURE;
        }

        $statusEmoji = [
            'created' => '✅',
            'updated' => '🔄',
            'skipped' => '⏭️',
        ];

        $emoji = $statusEmoji[$result['status']] ?? '❓';
        $this->info("{$emoji} {$result['message']}");

        if ($result['voyage_id']) {
            $voyage = Voyage::find($result['voyage_id']);
            if ($voyage) {
                $this->newLine();
                $this->info('📋 Voyage Details:');
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['ID', $voyage->id],
                        ['WP Post ID', $voyage->wp_post_id],
                        ['Name', $voyage->name],
                        ['Slug', $voyage->slug],
                        ['Destination', $voyage->destination ?? 'N/A'],
                        ['Duration', $voyage->duration_text ?? 'N/A'],
                        ['Price From', $voyage->price_from ? number_format($voyage->price_from / 100, 2) . ' ' . $voyage->currency : 'N/A'],
                        ['Status', $voyage->status],
                        ['Synced At', $voyage->wp_synced_at ? $voyage->wp_synced_at->format('Y-m-d H:i:s') : 'Never'],
                    ]
                );
            }
        }

        return self::SUCCESS;
    }

    /**
     * Import all tours from WordPress.
     */
    protected function importAllTours(int $limit, float $startTime): int
    {
        $limitText = $limit > 0 ? " (limit: {$limit})" : " (no limit)";
        $this->info("🔄 Importing all published st_tours from WordPress{$limitText}...");
        $this->newLine();

        $progressBar = $this->output->createProgressBar();
        $progressBar->setFormat(' %current% tours processed | %elapsed:6s% elapsed');
        $progressBar->start();

        $summary = $this->importer->importAll($limit);

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('   Import Summary');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['✅ Created', $summary['created']],
                ['🔄 Updated', $summary['updated']],
                ['⏭️  Skipped (no changes)', $summary['skipped']],
                ['❌ Errors', count($summary['errors'])],
            ]
        );

        // Display errors if any
        if (!empty($summary['errors'])) {
            $this->newLine();
            $this->error('Errors encountered:');
            foreach ($summary['errors'] as $error) {
                $wpId = $error['wp_post_id'] ?? 'N/A';
                $this->line("  • WP Post ID {$wpId}: {$error['message']}");
            }
        }

        // Display total count and samples
        $this->newLine();
        $this->displayVerification();

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->newLine();
        $this->info("⏱️  Completed in {$elapsed} seconds");
        $this->info('═══════════════════════════════════════════════════════');

        return self::SUCCESS;
    }

    /**
     * Display verification: total count and sample voyages.
     */
    protected function displayVerification(): void
    {
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('   Verification');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        $totalCount = Voyage::count();
        $this->info("📊 Total voyages in database: {$totalCount}");
        $this->newLine();

        // Show first 5 voyages
        $sampleVoyages = Voyage::orderBy('id')
            ->limit(5)
            ->get(['id', 'wp_post_id', 'name', 'slug', 'destination', 'price_from', 'status']);

        if ($sampleVoyages->isNotEmpty()) {
            $this->info('📋 Sample voyages (first 5):');
            $this->table(
                ['ID', 'WP Post ID', 'Name', 'Slug', 'Destination', 'Price (MAD)', 'Status'],
                $sampleVoyages->map(function ($voyage) {
                    return [
                        $voyage->id,
                        $voyage->wp_post_id,
                        Str::limit($voyage->name, 30),
                        $voyage->slug,
                        Str::limit($voyage->destination ?? 'N/A', 20),
                        $voyage->price_from ? number_format($voyage->price_from / 100, 2) : 'N/A',
                        $voyage->status,
                    ];
                })->toArray()
            );
        } else {
            $this->warn('No voyages found in the database.');
        }

        $this->newLine();
        
        // Additional stats
        $wpSyncedCount = Voyage::whereNotNull('wp_post_id')->count();
        $activeCount = Voyage::where('status', 'actif')->count();
        
        $this->info("📈 Statistics:");
        $this->line("  • Synced from WordPress: {$wpSyncedCount}");
        $this->line("  • Active tours: {$activeCount}");
        $this->line("  • Draft tours: " . ($totalCount - $activeCount));
    }
}
