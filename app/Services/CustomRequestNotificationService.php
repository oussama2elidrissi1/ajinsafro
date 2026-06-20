<?php

namespace App\Services;

use App\Models\ClientNotification;
use App\Models\CustomRequest;
use App\Models\CustomRequestQuote;
use App\Models\User;

class CustomRequestNotificationService
{
    public function notifyNewRequest(CustomRequest $customRequest): void
    {
        $users = User::query()
            ->where(function ($query): void {
                $query->where('is_admin', true)
                    ->orWhereHas('permissions', fn ($q) => $q->whereIn('name', ['custom_requests.quote', 'custom_requests.view_all']))
                    ->orWhereHas('roles.permissions', fn ($q) => $q->whereIn('name', ['custom_requests.quote', 'custom_requests.view_all']));
            })
            ->get();

        $users
            ->unique('id')
            ->filter(fn (User $user): bool => $user->canQuoteCustomRequests() || $user->can('custom_requests.view_all'))
            ->each(function (User $user) use ($customRequest): void {
                $this->notifyUser($user, 'custom_request_new', 'Nouvelle demande à la carte', 'Nouvelle demande à la carte à traiter : '.$customRequest->request_number, $this->customRequestLinkFor($user, $customRequest));
            });
    }

    public function notifyAssigned(CustomRequest $customRequest): void
    {
        if ($customRequest->assignedAgent) {
            $this->notifyUser($customRequest->assignedAgent, 'custom_request_assigned', 'Demande assignée', 'Une demande à la carte vous a été assignée', $this->customRequestLinkFor($customRequest->assignedAgent, $customRequest));
        }
    }

    public function notifyQuoteSent(CustomRequest $customRequest): void
    {
        if ($customRequest->creator) {
            $this->notifyUser($customRequest->creator, 'custom_request_quote_sent', 'Devis envoyé', 'Le devis de la demande '.$customRequest->request_number.' est prêt', $this->customRequestLinkFor($customRequest->creator, $customRequest));
        }
    }

    public function notifyPriceSheetSent(CustomRequest $customRequest): void
    {
        if ($customRequest->creator) {
            $this->notifyUser(
                $customRequest->creator,
                'custom_request_price_sheet_sent',
                'Fiche prix envoyée',
                'La fiche prix interne de la demande '.$customRequest->request_number.' est disponible.',
                $this->customRequestLinkFor($customRequest->creator, $customRequest)
            );
        }
    }

    public function notifyModificationRequested(CustomRequest $customRequest, CustomRequestQuote $quote): void
    {
        if ($customRequest->assignedAgent) {
            $this->notifyUser($customRequest->assignedAgent, 'custom_request_modification_requested', 'Modification demandée', 'Modification demandée sur le devis '.$quote->quote_number, $this->customRequestQuoteLinkFor($customRequest->assignedAgent, $customRequest));
        }
    }

    public function notifyConfirmed(CustomRequest $customRequest): void
    {
        if ($customRequest->assignedAgent) {
            $this->notifyUser(
                $customRequest->assignedAgent,
                'custom_request_confirmed',
                'Demande confirmée',
                'La demande '.$customRequest->request_number.' a été confirmée par l’agent commercial.',
                $this->customRequestLinkFor($customRequest->assignedAgent, $customRequest)
            );
        }
    }

    public function notifyCancelled(CustomRequest $customRequest): void
    {
        if ($customRequest->assignedAgent) {
            $this->notifyUser(
                $customRequest->assignedAgent,
                'custom_request_cancelled',
                'Demande annulée',
                'La demande '.$customRequest->request_number.' a été annulée par l’agent commercial.',
                $this->customRequestLinkFor($customRequest->assignedAgent, $customRequest)
            );
        }
    }

    public function notifyMissingInfo(CustomRequest $customRequest): void
    {
        if ($customRequest->creator) {
            $this->notifyUser($customRequest->creator, 'custom_request_missing_info', 'Informations manquantes', 'L’agent offline demande des informations complémentaires', $this->customRequestLinkFor($customRequest->creator, $customRequest));
        }
    }

    private function notifyUser(User $user, string $type, string $title, string $message, ?string $link = null): void
    {
        ClientNotification::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => false,
        ]);
    }

    private function customRequestLinkFor(User $user, CustomRequest $customRequest): string
    {
        if ($this->usesAgentPortal($user)) {
            return route('agent.custom-reservations.show', $customRequest);
        }

        return route('admin.custom-requests.show', $customRequest);
    }

    private function customRequestQuoteLinkFor(User $user, CustomRequest $customRequest): string
    {
        if ($this->usesAgentPortal($user)) {
            return route('agent.custom-reservations.quote', $customRequest);
        }

        return route('admin.custom-requests.quote', $customRequest);
    }

    private function usesAgentPortal(User $user): bool
    {
        return ! $user->is_admin && ($user->hasRole('Agent') || $user->isAgentOffline());
    }
}
