<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BranchScopeService
{
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_SIEGE_ADMIN = 'siege_admin';
    public const ROLE_BRANCH_ADMIN = 'branch_admin';
    public const ROLE_CHEF_COMMERCIAL = 'chef_commercial';
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
        return [];
    }

    /**
     * True si l'utilisateur est un compte agence (accès limité à une agence).
     */
    public function isBranchScoped(User $user): bool
    {
        return $user->hasRole(self::ROLE_BRANCH_ADMIN)
            || $user->hasRole(self::ROLE_CHEF_COMMERCIAL)
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
            return $query->whereRaw('1 = 0');
        }
        return $query->whereIn('branch_id', $branchIds);
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
}
