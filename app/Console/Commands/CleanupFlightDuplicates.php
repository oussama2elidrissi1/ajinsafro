<?php

namespace App\Console\Commands;

use App\Models\Voyage;
use App\Services\VoyageFlightService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Cleanup duplicate rows in WP aj_tour_flights and align with Laravel voyage_flights.
 * No migration, no rollback — safe to run on production.
 */
class CleanupFlightDuplicates extends Command
{
    protected $signature = 'voyage:cleanup-flight-duplicates 
                            {--dry-run : List duplicates only, do not delete}
                            {--no-sync : Do not sync from Laravel after cleanup}';

    protected $description = 'Remove duplicate rows in aj_tour_flights (WP) and align with Laravel CRUD';

    public function __construct(
        protected VoyageFlightService $flightService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $syncAfter = !$this->option('no-sync');

        try {
            $wp = DB::connection('wp');
        } catch (\Throwable $e) {
            $this->error('WP database connection failed: ' . $e->getMessage());
            return 1;
        }

        $prefix = $wp->getTablePrefix();
        $table = $prefix . 'aj_tour_flights';

        if (!$this->tableExists($wp, $table)) {
            $this->warn("Table {$table} does not exist. Nothing to cleanup.");
            return 0;
        }

        $duplicates = $wp->select("
            SELECT tour_id, flight_type, COUNT(*) as cnt
            FROM {$table}
            GROUP BY tour_id, flight_type
            HAVING COUNT(*) > 1
        ");

        if (empty($duplicates)) {
            $this->info('No duplicates found in ' . $table);
            if ($syncAfter && !$dryRun) {
                $this->info('Running sync from Laravel to align state...');
                return $this->runSync();
            }
            return 0;
        }

        $totalDup = array_sum(array_column($duplicates, 'cnt'));
        $toRemove = $totalDup - count($duplicates);
        $this->info("Found duplicates: " . count($duplicates) . " (tour_id, flight_type) groups, {$toRemove} row(s) to remove.");

        foreach ($duplicates as $d) {
            $this->line("  tour_id={$d->tour_id}, flight_type={$d->flight_type} → " . ($d->cnt - 1) . " duplicate(s)");
        }

        if ($dryRun) {
            $this->info('[DRY-RUN] No changes made. Run without --dry-run to delete duplicates.');
            return 0;
        }

        $this->info('Removing duplicates (keeping one row per tour_id + flight_type)...');
        $wp->statement("
            DELETE a FROM {$table} a
            LEFT JOIN (
                SELECT MIN(id) AS id, tour_id, flight_type
                FROM {$table}
                GROUP BY tour_id, flight_type
            ) b ON a.id = b.id AND a.tour_id = b.tour_id AND a.flight_type = b.flight_type
            WHERE b.id IS NULL
        ");
        $this->info('Done. Duplicates removed.');

        if ($syncAfter) {
            $this->info('Syncing all voyages from Laravel (source of truth)...');
            return $this->runSync();
        }

        return 0;
    }

    private function tableExists($connection, string $table): bool
    {
        try {
            $connection->selectOne("SELECT 1 FROM {$table} LIMIT 0");
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function runSync(): int
    {
        $voyages = Voyage::whereNotNull('wp_post_id')->get();
        if ($voyages->isEmpty()) {
            $this->info('No voyages with wp_post_id.');
            return 0;
        }
        $ok = 0;
        foreach ($voyages as $voyage) {
            try {
                $this->flightService->syncFlightsToWp($voyage->id, (int) $voyage->wp_post_id);
                $ok++;
            } catch (\Throwable $e) {
                $this->error("  Voyage #{$voyage->id}: " . $e->getMessage());
            }
        }
        $this->info("Synced {$ok} voyage(s).");
        return 0;
    }
}
