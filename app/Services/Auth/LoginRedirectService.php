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
        // Partner area
        if ($user->isPartner()) {
            $partner = $user->partner;
            if ($partner && method_exists($partner, 'canAccessPartnerArea') && $partner->canAccessPartnerArea()) {
                return route('partner.dashboard');
            }
            return route('partner.pending');
        }

        // Admin (HQ + branches + commercial roles + comptable)
        if ($user->canAccessAdmin() || $user->isComptable()) {
            return route('admin.dashboard');
        }

        // Default fallback: public website homepage
        return 'https://ajinsafro.net/';
    }
}

