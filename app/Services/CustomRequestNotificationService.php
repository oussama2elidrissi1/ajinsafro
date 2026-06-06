<?php

namespace App\Services;

use App\Models\ClientNotification;
use App\Models\CustomRequest;
use App\Models\CustomRequestQuote;
use App\Models\User;
use Illuminate\Support\Collection;

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

        $this->notifyUsers($users, 'custom_request_new', 'Nouvelle demande à la carte', 'Nouvelle demande à la carte à traiter : '.$customRequest->request_number, route('admin.custom-requests.show', $customRequest));
    }

    public function notifyAssigned(CustomRequest $customRequest): void
    {
        if ($customRequest->assignedAgent) {
            $this->notifyUser($customRequest->assignedAgent, 'custom_request_assigned', 'Demande assignée', 'Une demande à la carte vous a été assignée', route('admin.custom-requests.show', $customRequest));
        }
    }

    public function notifyQuoteSent(CustomRequest $customRequest): void
    {
        if ($customRequest->creator) {
            $this->notifyUser($customRequest->creator, 'custom_request_quote_sent', 'Devis envoyé', 'Le devis de la demande '.$customRequest->request_number.' est prêt', route('admin.custom-requests.show', $customRequest));
        }
    }

    public function notifyModificationRequested(CustomRequest $customRequest, CustomRequestQuote $quote): void
    {
        if ($customRequest->assignedAgent) {
            $this->notifyUser($customRequest->assignedAgent, 'custom_request_modification_requested', 'Modification demandée', 'Modification demandée sur le devis '.$quote->quote_number, route('admin.custom-requests.quote', $customRequest));
        }
    }

    public function notifyMissingInfo(CustomRequest $customRequest): void
    {
        if ($customRequest->creator) {
            $this->notifyUser($customRequest->creator, 'custom_request_missing_info', 'Informations manquantes', 'L’agent offline demande des informations complémentaires', route('admin.custom-requests.show', $customRequest));
        }
    }

    private function notifyUsers(Collection $users, string $type, string $title, string $message, ?string $link = null): void
    {
        $users->unique('id')->each(fn (User $user) => $this->notifyUser($user, $type, $title, $message, $link));
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
}
