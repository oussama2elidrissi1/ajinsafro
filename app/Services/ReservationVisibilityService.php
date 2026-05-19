<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ReservationVisibilityService
{
    public function __construct(
        private BranchScopeService $branchScope,
    ) {}

    public function canViewSensitive(User $user): bool
    {
        return $this->branchScope->canSeeAllBranches($user)
            || $user->can('reservations.view_sensitive');
    }

    public function canViewFinancial(User $user): bool
    {
        return $this->branchScope->canSeeAllBranches($user)
            || $user->can('reservations.view_financial');
    }

    public function canViewClientContact(User $user): bool
    {
        return $this->branchScope->canSeeAllBranches($user)
            || $user->can('reservations.view_client_contact');
    }

    public function canViewInternalNotes(User $user): bool
    {
        return $this->branchScope->canSeeAllBranches($user)
            || $user->can('reservations.view_internal_notes');
    }

    public function canViewCommissions(User $user): bool
    {
        return $this->branchScope->canSeeAllBranches($user)
            || $user->can('reservations.view_commissions');
    }

    public function usesLimitedPresentation(User $user): bool
    {
        if ($this->branchScope->isCommercialReservationsOnly($user)) {
            return true;
        }

        if ($this->branchScope->canSeeAllBranches($user)) {
            return false;
        }

        if ($user->isManager() || $user->isBranchAdmin() || $user->isChefCommercial()) {
            return false;
        }

        return $user->isAgent() || $user->isCommercial();
    }

    public function canViewAssignmentContext(User $user): bool
    {
        return ! $this->usesLimitedPresentation($user);
    }

    public function applyScope(Builder $query, User $user): Builder
    {
        if ($this->branchScope->isCommercialReservationsOnly($user)) {
            return $query->where(function (Builder $builder) use ($user): void {
                $builder->where('created_by', $user->id)
                    ->orWhere('created_by_user_id', $user->id);
            });
        }

        if ($this->branchScope->canSeeAllBranches($user)) {
            return $query;
        }

        if ($user->isManager() || $user->isBranchAdmin()) {
            if ($user->branch_id) {
                return $query->where('branch_id', $user->branch_id);
            }

            return $query->assignedTo($user->id);
        }

        if ($user->isChefCommercial()) {
            return $query->where(function (Builder $builder) use ($user): void {
                if ($user->branch_id) {
                    $builder->where('branch_id', $user->branch_id)
                        ->orWhere('sales_manager_id', $user->id);

                    return;
                }

                $builder->where('sales_manager_id', $user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhere('created_by_user_id', $user->id);
            });
        }

        if ($user->isCommercial() || $user->isAgent()) {
            return $query->where(function (Builder $builder) use ($user): void {
                $builder->where('agent_id', $user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhere('created_by_user_id', $user->id);
            });
        }

        if ($user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query->where(function (Builder $builder) use ($user): void {
            $builder->where('agent_id', $user->id)
                ->orWhere('sales_manager_id', $user->id)
                ->orWhere('created_by', $user->id)
                ->orWhere('created_by_user_id', $user->id);
        });
    }

    public function canAccessReservation(User $user, Reservation $reservation, array $context = []): bool
    {
        if ($this->branchScope->canSeeAllBranches($user)) {
            return true;
        }

        $query = Reservation::query()->whereKey($reservation->getKey());
        $this->branchScope->scopeReservations($query, $user, $context);
        $this->applyScope($query, $user);

        return $query->exists();
    }

    public function sanitizeReservationModel(Reservation $reservation, User $user): Reservation
    {
        if (! $this->canViewFinancial($user)) {
            $reservation->setAttribute('base_price', null);
            $reservation->setAttribute('paid_amount', null);
            $reservation->setAttribute('room_supplement_total', null);
            $reservation->setAttribute('payment_type', null);
        }

        if (! $this->canViewClientContact($user)) {
            $reservation->setAttribute('client_email', null);
            $reservation->setAttribute('client_phone', null);

            if ($reservation->relationLoaded('client') && $reservation->client) {
                $reservation->client->email = null;
                $reservation->client->phone = null;
            }
        }

        if (! $this->canViewSensitive($user)) {
            $reservation->setAttribute('client_document_number', null);

            if ($reservation->relationLoaded('client') && $reservation->client) {
                $reservation->client->client_code = null;
            }

            if ($reservation->relationLoaded('passengers')) {
                $reservation->passengers->each(function ($passenger): void {
                    $passenger->birth_date = null;
                    $passenger->document_type = null;
                    $passenger->document_number = null;
                });
            }
        }

        if (! $this->canViewInternalNotes($user)) {
            $reservation->setAttribute('visa_notes', null);
            $reservation->setAttribute('notes', null);
        }

        if ($this->usesLimitedPresentation($user)) {
            $reservation->setAttribute('assignment_note', null);

            if ($reservation->relationLoaded('creator') && $reservation->creator) {
                $reservation->creator->email = null;
            }

            if ($reservation->relationLoaded('createdBy') && $reservation->createdBy) {
                $reservation->createdBy->email = null;
            }
        }

        return $reservation;
    }

    public function flagsFor(User $user): array
    {
        return [
            'view_sensitive' => $this->canViewSensitive($user),
            'view_financial' => $this->canViewFinancial($user),
            'view_client_contact' => $this->canViewClientContact($user),
            'view_internal_notes' => $this->canViewInternalNotes($user),
            'view_commissions' => $this->canViewCommissions($user),
            'limited_presentation' => $this->usesLimitedPresentation($user),
            'view_assignment_context' => $this->canViewAssignmentContext($user),
        ];
    }
}
