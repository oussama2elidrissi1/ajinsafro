<?php

namespace App\Support;

use App\Models\User;
use App\Services\BranchScopeService;

/**
 * Referentiel du module « Finance & Controle ».
 *
 * Ce module est reserve au SEUL role d'administrateur principal : `super_admin`.
 * Aucun autre role n'y a acces, y compris `siege_admin` et les roles legacy `Admin` /
 * `Super Admin`. Le flag historique `users.is_admin` et l'appartenance aux comptes dev
 * ne donnent AUCUN acces : seule la detention du role compte.
 *
 * Les permissions listees ici ne doivent JAMAIS etre distribuees a un autre role : elles
 * sont explicitement exclues de tous les lots calcules par AjinsafroRolesSeeder, et la
 * migration 2026_09_09_100200 purge toute attribution parasite (role ou utilisateur).
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
     * Seul role autorise a detenir les permissions du module.
     *
     * Volontairement reduit a `super_admin` : les graphies legacy `Super Admin` et `Admin`
     * en sont exclues car le role `Admin` est attribue automatiquement a tout compte
     * `is_admin` par AdminPermissionsSeeder, ce qui rouvrirait le module par la bande.
     *
     * @return list<string>
     */
    public static function adminRoleNames(): array
    {
        return [
            BranchScopeService::ROLE_SUPER_ADMIN,
        ];
    }

    /**
     * Seule source de verite de l'acces au module (menu, middleware, controleurs, FormRequests).
     *
     * Aucun contournement possible : ni `is_admin`, ni `access_mode = custom`, ni compte dev,
     * ni permission attribuee par erreur. Seule la detention du role `super_admin` ouvre l'acces.
     */
    public static function userIsFinanceAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        // Defense en profondeur : un compte portail client ou partenaire reste exclu
        // meme si le role lui avait ete attribue par erreur.
        if ($user->isClientPortal() || $user->isPartner()) {
            return false;
        }

        return $user->hasRole(self::adminRoleNames());
    }
}
