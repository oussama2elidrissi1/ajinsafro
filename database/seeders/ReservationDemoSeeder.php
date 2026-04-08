<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Voyage;
use App\Services\BranchScopeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Réservations de démo multi-agences pour tester listes / workspace / rôles.
 *
 * Idempotent : client_email = demo.seed.*@ajinsafro.test
 */
class ReservationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $voyage = Voyage::query()->orderBy('id')->first();
        if (! $voyage) {
            $this->command->warn('ReservationDemoSeeder : aucun voyage — exécutez DubaiTravelSeeder ou créez un voyage.');

            return;
        }

        $tourId = (int) $voyage->id;

        $branches = Branch::query()->whereIn('code', ['TNG', 'FES', 'CAS', 'MAR', 'BRU'])->get()->keyBy('code');
        if ($branches->isEmpty()) {
            $this->command->warn('ReservationDemoSeeder : aucune agence — exécutez BranchesSeeder.');

            return;
        }

        $defs = [
            'FES' => [
                ['suffix' => 'fes-chef', 'status' => Reservation::STATUS_PENDING, 'created_by_chef' => true, 'note' => 'Démo Fès — dossier agence (sans agent_id)'],
                ['suffix' => 'fes-commercial', 'status' => Reservation::STATUS_PENDING, 'role' => 'commercial'],
                ['suffix' => 'fes-agent', 'status' => Reservation::STATUS_CONFIRMED, 'role' => 'agent'],
            ],
            'TNG' => [
                ['suffix' => 'tng-branch', 'status' => Reservation::STATUS_PENDING, 'role' => 'branch_admin'],
            ],
            'CAS' => [
                ['suffix' => 'cas-commercial', 'status' => Reservation::STATUS_PENDING, 'role' => 'commercial'],
            ],
            'MAR' => [
                ['suffix' => 'mar-agent', 'status' => Reservation::STATUS_CANCELLED, 'role' => 'agent'],
            ],
            'BRU' => [
                ['suffix' => 'bru-chef', 'status' => Reservation::STATUS_PENDING, 'role' => 'chef'],
            ],
        ];

        foreach ($defs as $code => $rows) {
            $branch = $branches->get($code);
            if (! $branch) {
                continue;
            }

            $chef = User::where('email', 'chef.'.$code.'@ajinsafro.com')->first();
            $commercial = User::where('email', 'commercial.'.$code.'@ajinsafro.com')->first();
            $agent = User::where('email', 'agent.'.$code.'@ajinsafro.com')->first();
            $branchAdmin = User::query()
                ->where('branch_id', $branch->id)
                ->whereHas('roles', fn ($q) => $q->where('name', BranchScopeService::ROLE_BRANCH_ADMIN))
                ->first();

            foreach ($rows as $row) {
                $email = 'demo.seed.'.$row['suffix'].'@ajinsafro.test';
                if (Reservation::query()->where('client_email', $email)->exists()) {
                    continue;
                }

                $salesManagerId = $chef?->id;
                $agentId = null;
                $createdBy = $chef?->id ?? $commercial?->id ?? $agent?->id ?? 1;

                if (! empty($row['created_by_chef']) && $chef) {
                    $createdBy = $chef->id;
                    $agentId = null;
                } elseif (($row['role'] ?? '') === 'commercial' && $commercial) {
                    $createdBy = $commercial->id;
                    $agentId = $commercial->id;
                } elseif (($row['role'] ?? '') === 'agent' && $agent) {
                    $createdBy = $agent->id;
                    $agentId = $agent->id;
                } elseif (($row['role'] ?? '') === 'branch_admin' && $branchAdmin) {
                    $createdBy = $branchAdmin->id;
                    $agentId = $branchAdmin->id;
                } elseif (($row['role'] ?? '') === 'chef' && $chef) {
                    $createdBy = $chef->id;
                    $agentId = null;
                }

                Reservation::query()->create([
                    'tour_id' => $tourId,
                    'branch_id' => $branch->id,
                    'sales_manager_id' => $salesManagerId,
                    'agent_id' => $agentId,
                    'created_by' => $createdBy,
                    'created_by_user_id' => $createdBy,
                    'client_mode' => 'new',
                    'client_first_name' => 'Démo',
                    'client_last_name' => strtoupper($code),
                    'client_email' => $email,
                    'client_phone' => '+212600000000',
                    'payment_type' => Reservation::PAYMENT_ESPECE,
                    'status' => $row['status'],
                    'passengers_count' => 2,
                    'notes' => $row['note'] ?? 'ReservationDemoSeeder',
                ]);
            }
        }

        DB::table('reservations')
            ->whereNull('created_by_user_id')
            ->whereNotNull('created_by')
            ->update(['created_by_user_id' => DB::raw('created_by')]);

        $this->command->info('ReservationDemoSeeder : OK (emails demo.seed.*@ajinsafro.test).');
    }
}
