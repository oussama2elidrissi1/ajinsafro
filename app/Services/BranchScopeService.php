<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\User;
use App\Services\View\AgentPortalLayout;
use Illuminate\Database\Eloquent\Builder;

class BranchScopeService
{
    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_SIEGE_ADMIN = 'siege_admin';

    public const ROLE_BRANCH_ADMIN = 'branch_admin';

    public const ROLE_CHEF_COMMERCIAL = 'chef_commercial';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_COMMERCIAL = 'commercial';

    public const ROLE_AGENT = 'agent';

    /**
     * Comptes avec accès global (toutes les agences) : super_admin, siege_admin, ou is_admin (legacy).
     */
    public function canSeeAllBranches(User $user): bool
    {
        return $user->hasRole(self::ROLE_SUPER_ADMIN)
            || $user->hasRole(self::ROLE_SIEGE_ADMIN)
            || $user->hasRole('Super Admin')
            || $user->hasRole('Admin Siège')
            || $user->is_admin;
    }

    /**
     * IDs des branches que l'utilisateur peut voir.
     *
     * @return int[]|null null = toutes les branches
     */
    public function visibleBranchIds(User $user): ?array
    {
        if ($this->canSeeAllBranches($user)) {
            return null;
        }
        if ($user->branch_id) {
            return [(int) $user->branch_id];
        }
        // Aligné sur l’attribution des réservations (commercial sans agence propre → agence du manager).
        $managerBranchId = $user->manager?->branch_id;
        if ($managerBranchId) {
            return [(int) $managerBranchId];
        }

        return [];
    }

    /**
     * Branch + chef commercial pour une nouvelle réservation (même logique que la liste admin).
     *
     * @return array{branch_id: int|null, sales_manager_id: int|null}
     */
    public function defaultReservationOwnership(User $user, ?int $clientExternalId = null): array
    {
        $branchId = $user->branch_id ?? $user->manager?->branch_id;
        if (($branchId === null || (int) $branchId <= 0) && $clientExternalId !== null && $clientExternalId > 0) {
            $client = Client::query()->find($clientExternalId);
            if ($client && $client->branch_id) {
                $branchId = (int) $client->branch_id;
            }
        }
        $branchId = $branchId && (int) $branchId > 0 ? (int) $branchId : null;

        $salesManagerId = null;
        if ($branchId !== null) {
            $salesManagerId = Branch::query()->whereKey($branchId)->value('manager_user_id');
            $salesManagerId = $salesManagerId ? (int) $salesManagerId : null;
        }
        if ($salesManagerId === null) {
            $fallback = $user->branch?->manager_user_id;
            $salesManagerId = $fallback ? (int) $fallback : null;
        }

        return [
            'branch_id' => $branchId,
            'sales_manager_id' => $salesManagerId,
        ];
    }

    /**
     * True si l'utilisateur est un compte agence (accès limité à une agence).
     */
    public function isBranchScoped(User $user): bool
    {
        return $user->hasRole(self::ROLE_BRANCH_ADMIN)
            || $user->hasRole(self::ROLE_CHEF_COMMERCIAL)
            || $user->hasRole(self::ROLE_MANAGER)
            || $user->hasRole(self::ROLE_COMMERCIAL)
            || $user->hasRole(self::ROLE_AGENT);
    }

    /**
     * True si compte siège global (Tanger accès toutes agences).
     */
    public function isSiegeAdmin(User $user): bool
    {
        return $user->hasRole(self::ROLE_SIEGE_ADMIN) || $user->hasRole('Admin Siège');
    }

    /**
     * True si compte principal d'une agence (dont agence Tanger).
     */
    public function isBranchAdmin(User $user): bool
    {
        return $user->hasRole(self::ROLE_BRANCH_ADMIN);
    }

    /**
     * Scope les réservations selon le rôle / agence de l'utilisateur.
     */
    public function scopeReservations(Builder $query, User $user): Builder
    {
        $branchIds = $this->visibleBranchIds($user);
        if ($branchIds === null) {
            return $query;
        }
        if (empty($branchIds)) {
            // Compte agence sans branch_id : sinon liste vide alors que des réservations viennent d’être créées
            // (branch_id null + agent_id / created_by). Le portail restreint déjà via
            // {@see constrainReservationQueryForPortalUser}.
            if (AgentPortalLayout::shouldUse($user)) {
                return $query;
            }

            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('branch_id', $branchIds);
    }

    /**
     * Accès fiche / panel / édition : aligné sur la liste (scope agence + propriété portail).
     */
    public function userCanAccessReservation(User $user, Reservation $reservation): bool
    {
        if ($this->canSeeAllBranches($user)) {
            return true;
        }

        $branchIds = $this->visibleBranchIds($user);
        if ($branchIds !== null && $branchIds !== [] && $reservation->branch_id !== null) {
            if (in_array((int) $reservation->branch_id, array_map('intval', $branchIds), true)) {
                return true;
            }
        }

        if (AgentPortalLayout::shouldUse($user)) {
            return $this->reservationMatchesPortalOwnership($user, $reservation);
        }

        return false;
    }

    private function reservationMatchesPortalOwnership(User $user, Reservation $reservation): bool
    {
        $ids = $this->portalOwnershipUserIds($user);
        if ($ids === []) {
            return false;
        }
        $agentId = (int) ($reservation->agent_id ?? 0);
        $salesId = (int) ($reservation->sales_manager_id ?? 0);
        $createdId = (int) ($reservation->created_by ?? 0);
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id === 0) {
                continue;
            }
            if ($agentId === $id || $salesId === $id || $createdId === $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scope les clients selon le rôle / agence de l'utilisateur.
     */
    public function scopeClients(Builder $query, User $user): Builder
    {
        $branchIds = $this->visibleBranchIds($user);
        if ($branchIds === null) {
            return $query;
        }
        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('branch_id', $branchIds);
    }

    /**
     * Scope les utilisateurs selon le rôle / agence (Admin Siège voit tous, Chef Commercial voit son agence).
     */
    public function scopeUsers(Builder $query, User $user): Builder
    {
        if ($this->canSeeAllBranches($user)) {
            return $query;
        }
        if ($user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Branches disponibles pour un select (selon l'utilisateur).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Branch>
     */
    public function branchesForSelect(User $user)
    {
        if ($this->canSeeAllBranches($user)) {
            return Branch::active()->orderBy('type')->orderBy('name')->get();
        }
        if ($user->branch_id) {
            return Branch::where('id', $user->branch_id)->get();
        }

        return collect();
    }

    /**
     * IDs utilisateurs pour le périmètre réservations/clients du portail agent :
     * agent → lui seul ; manager → lui + équipe directe (même agence si renseignée).
     *
     * @return list<int>
     */
    public function portalOwnershipUserIds(User $user): array
    {
        if ($user->isManager()) {
            return User::query()
                ->where('manager_id', $user->id)
                ->when($user->branch_id, fn (Builder $q) => $q->where('branch_id', $user->branch_id))
                ->pluck('id')
                ->push($user->id)
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        return [$user->id];
    }

    /**
     * Membres directs de l'équipe (sans le manager).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function portalDirectReports(User $manager)
    {
        return User::query()
            ->where('manager_id', $manager->id)
            ->when($manager->branch_id, fn (Builder $q) => $q->where('branch_id', $manager->branch_id))
            ->orderBy('name')
            ->get();
    }

    /**
     * Restreint une requête réservations pour le portail agent/commercial/manager
     * (aligné sur {@see AgentPortalLayout::shouldUse} et {@see portalOwnershipUserIds}).
     */
    public function constrainReservationQueryForPortalUser(Builder $query, User $user): void
    {
        if (! \App\Services\View\AgentPortalLayout::shouldUse($user)) {
            return;
        }
        $ids = $this->portalOwnershipUserIds($user);
        if ($ids === []) {
            $query->whereRaw('1 = 0');

            return;
        }
        $query->where(function (Builder $q) use ($ids) {
            $q->whereIn('agent_id', $ids)
                ->orWhereIn('sales_manager_id', $ids)
                ->orWhereIn('created_by', $ids);
        });
    }
}
