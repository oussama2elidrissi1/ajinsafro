<?php

namespace App\Services\Auth;

use App\Models\User;

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
        $publicUrl = $this->normalizeBaseUrl((string) config('app.public_url', 'https://ajinsafro.net'));

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

