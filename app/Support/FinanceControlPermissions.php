<?php

namespace App\Support;

use App\Models\User;
use App\Services\BranchScopeService;

/**
 * Referentiel du module « Finance & Controle ».
 *
 * Ce module est reserve a l'administration globale de la plateforme (siege / super admin),
 * exactement la meme portee que celle deja appliquee par
 * {@see \App\Http\Middleware\EnsureRoutePermission::GLOBAL_ADMIN_ROUTE_PREFIXES}.
 *
 * Les permissions listees ici ne doivent JAMAIS etre distribuees aux roles operationnels
 * (branch_admin, chef_commercial, manager, commercial, agent, partenaires) : elles sont
 * explicitement exclues des lots calcules par AjinsafroRolesSeeder.
 */
class FinanceControlPermissions
{
    /**
     * Gate (et non permission Spatie) servant de garde unique cote menu ET cote serveur.
     * Volontairement absente de config/admin_menu.php `permission` pour qu'aucune
     * synchronisation de permissions ne puisse la creer ni l'attribuer en base.
     */
    public const ACCESS_GATE = 'finance-control.access';

    public const VIEW = 'finance.view';

    public const PROJECTS_VIEW = 'finance.projects.view';

    public const EXPENSES_MANAGE = 'finance.expenses.manage';

    public const STRUCTURAL_EXPENSES_MANAGE = 'finance.structural_expenses.manage';

    public const DOCUMENTS_MANAGE = 'finance.documents.manage';

    public const TREASURY_VIEW = 'finance.treasury.view';

    public const REPORTING_VIEW = 'finance.reporting.view';

    /**
     * Permissions creees par ce module.
     *
     * `finance.view` en est volontairement absente : elle preexiste au module et est
     * deja attribuee a des roles operationnels. Elle ne conditionne donc aucun acces ici.
     *
     * @return list<string>
     */
    public static function moduleOwned(): array
    {
        return [
            self::PROJECTS_VIEW,
            self::EXPENSES_MANAGE,
            self::STRUCTURAL_EXPENSES_MANAGE,
            self::DOCUMENTS_MANAGE,
            self::TREASURY_VIEW,
            self::REPORTING_VIEW,
        ];
    }

    /**
     * Roles autorises a detenir les permissions du module (graphies actuelles + legacy).
     *
     * @return list<string>
     */
    public static function adminRoleNames(): array
    {
        return [
            BranchScopeService::ROLE_SUPER_ADMIN,
            BranchScopeService::ROLE_SIEGE_ADMIN,
            'Super Admin',
            'Admin Siege',
            'Admin Siège',
            'Admin',
        ];
    }

    /**
     * Seule source de verite de l'acces au module.
     */
    public static function userIsFinanceAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isClientPortal() || $user->isPartner()) {
            return false;
        }

        if (method_exists($user, 'isDevAdmin') && $user->isDevAdmin()) {
            return true;
        }

        if ((bool) ($user->is_admin ?? false)) {
            return true;
        }

        return $user->hasRole(self::adminRoleNames());
    }
}
