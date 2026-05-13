<?php

namespace App\Console\Commands;

use App\Models\AgentCommissionEntry;
use App\Models\Reservation;
use App\Models\User;
use App\Services\AgentCommissionService;
use Illuminate\Console\Command;

class BackfillAgentCommissions extends Command
{
    protected $signature = 'commissions:backfill {--chunk=200 : Nombre de reservations par lot}';

    protected $description = 'Cree les commissions agents manquantes pour les reservations existantes.';

    public function __construct(
        private readonly AgentCommissionService $agentCommissionService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $chunk = max(50, (int) $this->option('chunk'));
        $summary = [
            'analyzed' => 0,
            'created' => 0,
            'existing' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        Reservation::query()
            ->with([
                'tour:id,wp_post_id,name',
                'voyage:id,wp_post_id,name',
                'agent:id,name',
                'createdBy:id,name',
                'creator:id,name',
                'passengers:id,reservation_id,type',
            ])
            ->orderBy('id')
            ->chunkById($chunk, function ($reservations) use (&$summary): void {
                foreach ($reservations as $reservation) {
                    $summary['analyzed']++;

                    $agent = $reservation->agent ?: $reservation->resolveOperationalActorUser();
                    if (! $agent instanceof User) {
                        $summary['skipped']++;
                        $this->warn('Reservation #'.$reservation->id.' ignoree: aucun agent resolu.');
                        continue;
                    }

                    $alreadyExists = AgentCommissionEntry::query()
                        ->where('reservation_id', $reservation->id)
                        ->where('agent_id', $agent->id)
                        ->exists();

                    try {
                        $entry = $this->agentCommissionService->createFromReservation($reservation, AgentCommissionEntry::SOURCE_BACKFILL);
                        if (! $entry) {
                            $summary['skipped']++;
                            continue;
                        }

                        $this->agentCommissionService->refreshFromReservationStatus($reservation, AgentCommissionEntry::SOURCE_BACKFILL);

                        if ($alreadyExists) {
                            $summary['existing']++;
                        } else {
                            $summary['created']++;
                        }
                    } catch (\Throwable $e) {
                        $summary['errors']++;
                        $this->error('Reservation #'.$reservation->id.' en erreur: '.$e->getMessage());
                    }
                }
            });

        $this->newLine();
        $this->info('Backfill commissions termine.');
        $this->table(
            ['Reservations analysees', 'Commissions creees', 'Commissions existantes', 'Sans agent', 'Erreurs'],
            [[
                $summary['analyzed'],
                $summary['created'],
                $summary['existing'],
                $summary['skipped'],
                $summary['errors'],
            ]]
        );

        return self::SUCCESS;
    }
}
