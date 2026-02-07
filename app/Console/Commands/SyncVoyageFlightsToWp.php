<?php

namespace App\Console\Commands;

use App\Models\Voyage;
use App\Services\VoyageFlightService;
use Illuminate\Console\Command;

class SyncVoyageFlightsToWp extends Command
{
    protected $signature = 'voyage:sync-flights-to-wp 
                            {--voyage= : Sync only this voyage ID}
                            {--dry-run : Show what would be synced without writing}';

    protected $description = 'Sync voyage_flights (Laravel) to aj_tour_flights (WP) so the front always reflects CRUD';

    public function __construct(
        protected VoyageFlightService $flightService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $voyageId = $this->option('voyage');
        $dryRun = $this->option('dry-run');

        if ($voyageId) {
            $voyage = Voyage::find($voyageId);
            if (!$voyage) {
                $this->error("Voyage #{$voyageId} not found.");
                return 1;
            }
            if (!$voyage->wp_post_id) {
                $this->warn("Voyage #{$voyageId} has no wp_post_id, skipping.");
                return 0;
            }
            $voyages = collect([$voyage]);
        } else {
            $voyages = Voyage::whereNotNull('wp_post_id')->get();
        }

        if ($voyages->isEmpty()) {
            $this->info('No voyages with wp_post_id found.');
            return 0;
        }

        $this->info(($dryRun ? '[DRY-RUN] ' : '') . 'Syncing flights to WP for ' . $voyages->count() . ' voyage(s)...');

        $ok = 0;
        $fail = 0;
        foreach ($voyages as $voyage) {
            try {
                if (!$dryRun) {
                    $this->flightService->syncFlightsToWp($voyage->id, (int) $voyage->wp_post_id);
                }
                $this->line("  ✓ Voyage #{$voyage->id} → wp_post_id {$voyage->wp_post_id}");
                $ok++;
            } catch (\Throwable $e) {
                $this->error("  ✗ Voyage #{$voyage->id}: " . $e->getMessage());
                $fail++;
            }
        }

        $this->newLine();
        $this->info("Done: {$ok} synced" . ($fail ? ", {$fail} failed" : '') . ($dryRun ? ' (dry-run)' : '.'));
        return $fail > 0 ? 1 : 0;
    }
}
