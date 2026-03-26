<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\BranchScopeService;

class LoginRedirectService
{
    private function normalizeBaseUrl(string $url): string
    {
        $url = rtrim(trim($url), '/');

        // Prevent legacy config like https://booking.ajinsafro.net/partner from leaking into redirects.
        $url = preg_replace('~/partner$~i', '', $url) ?? $url;
        $url = rtrim($url, '/');

        return $url;
    }

    /**
     * Central mapping roles/permissions -> destination URL after login
     * and when a logged-in user hits /login.
     */
    public function destinationFor(User $user): string
    {
        $adminUrl = $this->normalizeBaseUrl((string) config('app.admin_url', config('app.url')));
        $partnerUrl = $this->normalizeBaseUrl((string) config('app.partner_url', 'https://partenaire.ajinsafro.net'));
        if ($partnerUrl === '') {
            $partnerDomain = (string) config('app.partner_domain', 'partenaire.ajinsafro.net');
            $partnerUrl = 'https://' . $partnerDomain;
        }
        if ($adminUrl === '') {
            $adminUrl = rtrim((string) config('app.url', 'https://booking.ajinsafro.net'), '/');
        }

        // Partner area (dedicated subdomain)
        if ($user->isPartner()) {
            $partner = $user->partner;
            if ($partner && method_exists($partner, 'canAccessPartnerArea') && $partner->canAccessPartnerArea()) {
                return $partnerUrl . '/dashboard';
            }
            return $partnerUrl . '/en-attente';
        }

        // Explicit mapping for admin roles.
        if ($user->hasRole([
            BranchScopeService::ROLE_SUPER_ADMIN,
            BranchScopeService::ROLE_SIEGE_ADMIN,
            BranchScopeService::ROLE_BRANCH_ADMIN,
            'Super Admin',
            'Admin Siège',
        ]) || $user->isComptable() || $user->is_admin) {
            return $adminUrl . '/admin/dashboard';
        }

        // Commercial roles
        if ($user->hasRole([
            BranchScopeService::ROLE_CHEF_COMMERCIAL,
            BranchScopeService::ROLE_MANAGER,
            BranchScopeService::ROLE_COMMERCIAL,
            'Chef Commercial',
            'Manager',
        ])) {
            return $adminUrl . '/agent/dashboard';
        }

        // Agent role
        if ($user->hasRole([
            BranchScopeService::ROLE_AGENT,
            'Agent',
        ])) {
            return $adminUrl . '/agent/dashboard';
        }

        // Fallback for users without back-office roles (e.g. WP-only synced accounts).
        return rtrim((string) config('app.public_url', 'https://ajinsafro.net'), '/');
    }
}

