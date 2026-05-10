<?php

namespace App\Services;

use App\Models\User;

class ReservationVisibilityService
{
    public function canViewSensitive(User $user): bool
    {
        return $user->can('reservations.view_sensitive');
    }

    public function canViewFinancial(User $user): bool
    {
        return $user->can('reservations.view_financial');
    }

    public function canViewClientContact(User $user): bool
    {
        return $user->can('reservations.view_client_contact');
    }

    public function canViewInternalNotes(User $user): bool
    {
        return $user->can('reservations.view_internal_notes');
    }

    public function canViewCommissions(User $user): bool
    {
        return $user->can('reservations.view_commissions');
    }

    public function flagsFor(User $user): array
    {
        return [
            'view_sensitive' => $this->canViewSensitive($user),
            'view_financial' => $this->canViewFinancial($user),
            'view_client_contact' => $this->canViewClientContact($user),
            'view_internal_notes' => $this->canViewInternalNotes($user),
            'view_commissions' => $this->canViewCommissions($user),
        ];
    }
}
