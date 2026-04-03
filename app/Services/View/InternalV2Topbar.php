<?php

namespace App\Services\View;

use App\Models\User;
use App\Services\BranchScopeService;

final class InternalV2Topbar
{
    public static function shouldHide(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->isPartner()) {
            return true;
        }

        return $user->hasRole([
            BranchScopeService::ROLE_AGENT,
            BranchScopeService::ROLE_COMMERCIAL,
            BranchScopeService::ROLE_CHEF_COMMERCIAL,
            BranchScopeService::ROLE_MANAGER,
            'Agent',
            'Commercial',
            'Chef Commercial',
            'Manager',
            'Partenaire',
        ]);
    }
}
