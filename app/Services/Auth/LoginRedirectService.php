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

        // Client portal (no back-office access).
        if (method_exists($user, 'isClientPortal') && $user->isClientPortal()) {
            return $adminUrl . '/client/dashboard';
        }

        // Partner area (dedicated subdomain)
        if ($user->isPartner()) {
            $partner = $user->partner ?: $user->ownedPartner;
            if ($partner && method_exists($partner, 'canAccessPartnerArea') && $partner->canAccessPartnerArea()) {
                return $partnerUrl . '/dashboard';
            }
            return $partnerUrl . '/en-attente';
        }

        // Reservations-only commercial role must never land on global dashboard.
        if ($user->hasRole([
            BranchScopeService::ROLE_COMMERCIAL_RESERVATIONS_ONLY,
        ])) {
            return $adminUrl . '/admin/reservations/workspace';
        }

        // Offline quoting users should land directly in the same workspace as Othmane.
        if ($user->canQuoteCustomRequests()) {
            return $adminUrl . '/agent/reservations-a-la-carte';
        }

        if ($this->shouldUseAdminInterface($user)) {
            return $adminUrl . '/admin/dashboard/vue-globale';
        }

        // Manager uses the same portal entrypoint as agent/commercial unless it is configured for admin.
        if ($user->hasRole([
            BranchScopeService::ROLE_MANAGER,
            'Manager',
        ])) {
            return $adminUrl . '/agent/dashboard';
        }

        // Explicit mapping for admin roles.
        if ($user->hasRole([
            BranchScopeService::ROLE_SUPER_ADMIN,
            BranchScopeService::ROLE_SIEGE_ADMIN,
            BranchScopeService::ROLE_BRANCH_ADMIN,
            'Super Admin',
            'Admin Siège',
        ]) || $user->isComptable() || $user->is_admin) {
            // Default admin dashboard (professional vue-globale page)
            return $adminUrl . '/admin/dashboard/vue-globale';
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

    private function shouldUseAdminInterface(User $user): bool
    {
        if ((string) ($user->access_mode ?? '') === 'custom') {
            return true;
        }

        if ($user->hasRole(['Admin', 'Super Admin'])) {
            return true;
        }

        return $user->canAccessAdmin();
    }
}
