<?php

namespace App\Services;

use App\Models\User;

/**
 * Colonnes et densité du hub /admin/reservations selon le rôle connecté.
 */
final class ReservationHubTableProfile
{
    public const MODE_NETWORK = 'network';

    public const MODE_AGENCY = 'agency';

    public const MODE_OPERATIONS = 'operations';

    /**
     * Siège / réseau : toutes les colonnes de supervision.
     * Agence (admin agence, chef commercial) : tableau intermédiaire.
     * Opérations (commercial, agent, manager) : tableau compact.
     */
    public function mode(User $user): string
    {
        if ($user->isSuperAdmin() || $user->isSiegeAdmin()) {
            return self::MODE_NETWORK;
        }
        if ($user->isBranchAdmin() || $user->isChefCommercial()) {
            return self::MODE_AGENCY;
        }

        return self::MODE_OPERATIONS;
    }

    /**
     * @param  bool  $voyageFiltered  Filtre voyage et/ou travel_date_id : liste multi-agences → colonne « Agence » (modes agency / operations).
     */
    public function tableColumnCount(string $mode, bool $voyageFiltered = false): int
    {
        $extra = $voyageFiltered && ($mode === self::MODE_AGENCY || $mode === self::MODE_OPERATIONS) ? 1 : 0;

        return match ($mode) {
            self::MODE_NETWORK => 13,
            self::MODE_AGENCY => 9 + $extra,
            default => 8 + $extra,
        };
    }
}
