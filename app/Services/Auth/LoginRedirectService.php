<?php

namespace App\Services\Auth;

use App\Models\User;

class LoginRedirectService
{
    /**
     * Central mapping roles/permissions -> destination URL after login
     * and when a logged-in user hits /login.
     */
    public function destinationFor(User $user): string
    {
        $adminUrl = rtrim((string) config('app.admin_url', config('app.url')), '/');
        $partnerUrl = rtrim((string) config('app.partner_url', 'https://partenaire.ajinsafro.net'), '/');
        $publicUrl = rtrim((string) config('app.public_url', 'https://ajinsafro.net'), '/');

        // Partner area (dedicated subdomain)
        if ($user->isPartner()) {
            $partner = $user->partner;
            if ($partner && method_exists($partner, 'canAccessPartnerArea') && $partner->canAccessPartnerArea()) {
                return $partnerUrl . '/dashboard';
            }
            return $partnerUrl . '/en-attente';
        }

        // Admin (HQ + branches + commercial roles + comptable) stays on booking/back-office domain
        if ($user->canAccessAdmin() || $user->isComptable()) {
            return $adminUrl . '/admin/dashboard';
        }

        // Default fallback: public website homepage
        return $publicUrl . '/';
    }
}

