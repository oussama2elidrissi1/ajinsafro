<?php

namespace App\Services\View;

use App\Models\User;
use App\Services\BranchScopeService;

/**
 * Determines whether the authenticated user should see the agent/commercial
 * portal shell (partner_v2 look) while browsing admin routes.
 *
 * Mirrors priority in {@see \App\Services\Auth\LoginRedirectService::destinationFor}.
 */
final class AgentPortalLayout
{
    public static function shouldUse(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        // Manager must always use agent/commercial portal UI.
        if ($user->hasRole([
            BranchScopeService::ROLE_MANAGER,
            'Manager',
        ])) {
            return true;
        }

        if ($user->isPartner()) {
            return false;
        }

        if ($user->hasRole([
            BranchScopeService::ROLE_SUPER_ADMIN,
            BranchScopeService::ROLE_SIEGE_ADMIN,
            BranchScopeService::ROLE_BRANCH_ADMIN,
            'Super Admin',
            'Admin Siège',
        ]) || $user->isComptable() || $user->is_admin) {
            return false;
        }

        return $user->hasRole([
            BranchScopeService::ROLE_CHEF_COMMERCIAL,
            BranchScopeService::ROLE_MANAGER,
            BranchScopeService::ROLE_COMMERCIAL,
            'Chef Commercial',
            'Manager',
            BranchScopeService::ROLE_AGENT,
            'Agent',
        ]);
    }
}
