<?php

namespace App\Services\GroupDeals;

use App\Models\Client;
use App\Models\ClientNotification;
use App\Models\Departure;
use App\Models\GroupDeal;
use App\Models\GroupDealParticipant;
use App\Models\Voyage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GroupDealService
{
    /**
     * Ajoute un participant à un départ group deal.
     * Retourne le participant créé ou existant.
     *
     * @throws \RuntimeException si le départ n'est pas group deal ou si le client est déjà inscrit.
     */
    public function addParticipant(Departure $departure, Client $client, ?int $reservationId = null): GroupDealParticipant
    {
        if (! $departure->group_deal_enabled) {
            throw new \RuntimeException('Ce départ n\'a pas le Group Deal activé.');
        }

        $existing = GroupDealParticipant::where('departure_id', $departure->id)
            ->where('client_id', $client->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $participant = GroupDealParticipant::create([
            'departure_id'   => $departure->id,
            'client_id'      => $client->id,
            'reservation_id' => $reservationId,
            'status'         => GroupDealParticipant::STATUS_CONFIRMED,
            'joined_at'      => now(),
        ]);

        $this->recalculate($departure->fresh());

        return $participant;
    }

    /**
     * Enregistre une participation sur une offre Group Deal publique.
     */
    public function registerPublicParticipant(GroupDeal $deal, array $payload): GroupDealParticipant
    {
        if (in_array($deal->status, [GroupDeal::STATUS_CLOSED, GroupDeal::STATUS_CANCELLED], true)) {
            throw new \RuntimeException('Cette offre n’accepte plus de nouvelles participations.');
        }

        if ($deal->registration_deadline && now()->isAfter($deal->registration_deadline->endOfDay())) {
            throw new \RuntimeException('La date limite d’inscription est dépassée pour cette offre.');
        }

        if ($deal->remaining_places <= 0) {
            throw new \RuntimeException('Cette offre est complète.');
        }

        $existing = GroupDealParticipant::query()
            ->where('group_deal_id', $deal->id)
            ->where(function ($query) use ($payload) {
                if (! empty($payload['user_id'])) {
                    $query->where('user_id', $payload['user_id']);
                } elseif (! empty($payload['email'])) {
                    $query->where('email', $payload['email']);
                }
            })
            ->whereIn('status', [
                GroupDealParticipant::STATUS_PENDING,
                GroupDealParticipant::STATUS_CONFIRMED,
                GroupDealParticipant::STATUS_PAID,
            ])
            ->first();

        if ($existing) {
            return $existing;
        }

        $participant = GroupDealParticipant::create([
            'group_deal_id' => $deal->id,
            'client_id' => $payload['client_id'] ?? null,
            'user_id' => $payload['user_id'] ?? null,
            'full_name' => $payload['full_name'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'email' => $payload['email'] ?? null,
            'participants_count' => max(1, (int) ($payload['participants_count'] ?? 1)),
            'status' => $payload['status'] ?? GroupDealParticipant::STATUS_PENDING,
            'selected_price' => $deal->current_price,
            'payment_status' => $payload['payment_status'] ?? GroupDealParticipant::PAYMENT_PENDING,
            'joined_at' => now(),
        ]);

        $this->createUserNotification(
            $participant->user_id,
            'group_deal_participation_created',
            'Participation enregistrée',
            sprintf(
                'Votre demande de participation au voyage %s a bien été enregistrée.',
                $deal->title
            ),
            route('front.group-deals.show', $deal->slug)
        );

        $this->createUserNotification(
            $participant->user_id,
            'group_deal_share_prompt',
            'Invitez vos amis',
            'Partagez ce voyage avec vos amis pour atteindre le meilleur prix.',
            $this->shareUrlForDeal($deal, $participant->user_id)
        );

        $this->syncOfferMetrics($deal->fresh());

        return $participant;
    }

    /**
     * Recalcule le prix actif selon les paliers et met à jour is_guaranteed.
     */
    public function recalculate(Departure $departure): void
    {
        if (! $departure->group_deal_enabled) {
            return;
        }

        $count = GroupDealParticipant::where('departure_id', $departure->id)
            ->where('status', GroupDealParticipant::STATUS_CONFIRMED)
            ->count();

        $voyage = $departure->voyage;
        $tier   = $voyage?->activePricingTier($count);

        $updates = [
            'active_tier_price' => $tier?->price_per_person ?? $departure->base_price,
        ];

        if (! $departure->is_guaranteed && $count >= $departure->guaranteed_threshold) {
            $updates['is_guaranteed']  = true;
            $updates['guaranteed_at']  = now();
        }

        $departure->update($updates);
    }

    public function syncOfferMetrics(GroupDeal $deal): GroupDeal
    {
        $previousGuaranteed = $deal->is_guaranteed;
        $previousPrice = (float) ($deal->current_price ?? 0);

        $confirmedStatuses = [
            GroupDealParticipant::STATUS_PENDING,
            GroupDealParticipant::STATUS_CONFIRMED,
            GroupDealParticipant::STATUS_PAID,
        ];

        $currentParticipants = (int) $deal->participants()
            ->whereIn('status', $confirmedStatuses)
            ->sum('participants_count');

        $activeTier = $deal->activePricingTier($currentParticipants);
        if (! $activeTier) {
            $activeTier = $deal->priceTiers()->orderBy('min_people')->first();
        }

        $isGuaranteed = $currentParticipants >= max(1, (int) $deal->min_participants);
        $startingPrice = (float) ($deal->priceTiers()->orderBy('min_people')->value('price_per_person') ?? 0);
        $currentPrice = (float) ($activeTier?->price_per_person ?? 0);
        $discountPercent = $startingPrice > 0 && $currentPrice > 0 && $currentPrice < $startingPrice
            ? (int) round((($startingPrice - $currentPrice) / $startingPrice) * 100)
            : 0;

        $status = $deal->status;
        if (! in_array($status, [GroupDeal::STATUS_DRAFT, GroupDeal::STATUS_CLOSED, GroupDeal::STATUS_CANCELLED], true)) {
            $status = $isGuaranteed ? GroupDeal::STATUS_GUARANTEED : GroupDeal::STATUS_PUBLISHED;
        }

        $deal->forceFill([
            'current_participants' => $currentParticipants,
            'starting_price' => $startingPrice > 0 ? $startingPrice : $deal->starting_price,
            'current_price' => $currentPrice > 0 ? $currentPrice : $deal->current_price,
            'discount_percent' => $discountPercent,
            'status' => $status,
            'guaranteed_at' => $isGuaranteed
                ? ($deal->guaranteed_at ?: now())
                : null,
        ])->save();

        $deal->refresh();

        $newPrice = (float) ($deal->current_price ?? 0);
        if (! $previousGuaranteed && $deal->is_guaranteed) {
            $this->notifyParticipants(
                $deal,
                'group_deal_guaranteed',
                'Voyage garanti',
                'Bonne nouvelle ! Le voyage est maintenant garanti.'
            );
        } elseif ($deal->remaining_to_guarantee > 0 && $deal->remaining_to_guarantee <= 2) {
            $this->notifyParticipants(
                $deal,
                'group_deal_almost_guaranteed',
                'Départ bientôt garanti',
                sprintf(
                    'Il reste seulement %d personne%s pour garantir le départ de votre voyage.',
                    $deal->remaining_to_guarantee,
                    $deal->remaining_to_guarantee > 1 ? 's' : ''
                )
            );
        }

        if ($newPrice > 0 && abs($newPrice - $previousPrice) > 0.001) {
            $this->notifyParticipants(
                $deal,
                'group_deal_price_changed',
                'Nouveau palier de prix atteint',
                sprintf(
                    'Le groupe a atteint %d participant%s. Le prix est maintenant de %s DH par personne.',
                    $deal->current_participants,
                    $deal->current_participants > 1 ? 's' : '',
                    number_format($newPrice, 0, ',', ' ')
                )
            );
        }

        return $deal;
    }

    /**
     * Active ou désactive le Group Deal sur un départ.
     */
    public function toggleGroupDeal(Departure $departure, bool $enabled, int $minParticipants = 1, int $guaranteedThreshold = 10): void
    {
        $departure->update([
            'group_deal_enabled'   => $enabled,
            'min_participants'     => $minParticipants,
            'guaranteed_threshold' => $guaranteedThreshold,
            'is_guaranteed'        => $enabled ? $departure->is_guaranteed : false,
            'active_tier_price'    => $enabled ? $departure->active_tier_price : null,
        ]);
    }

    /**
     * Statistiques globales Group Deal pour le dashboard.
     */
    public function dashboardStats(): array
    {
        return [
            'offers' => GroupDeal::count(),
            'open' => GroupDeal::whereIn('status', [GroupDeal::STATUS_PUBLISHED, GroupDeal::STATUS_GUARANTEED])->count(),
            'guaranteed' => GroupDeal::where('status', GroupDeal::STATUS_GUARANTEED)->count(),
            'participants_total' => GroupDealParticipant::whereIn('status', [
                GroupDealParticipant::STATUS_PENDING,
                GroupDealParticipant::STATUS_CONFIRMED,
                GroupDealParticipant::STATUS_PAID,
            ])->sum('participants_count'),
            'legacy_voyages_group_deal' => Voyage::where('is_group_deal', true)->count(),
            'legacy_departures_open' => Departure::where('group_deal_enabled', true)
                ->whereIn('status', ['open', 'limited'])
                ->count(),
        ];
    }

    /**
     * Statistiques d'un départ : compte confirmés, prix actif, progression.
     */
    public function departureStats(Departure $departure): array
    {
        $confirmed = GroupDealParticipant::where('departure_id', $departure->id)
            ->where('status', GroupDealParticipant::STATUS_CONFIRMED)
            ->count();

        $threshold   = max(1, $departure->guaranteed_threshold);
        $progression = min(100, (int) round(($confirmed / $threshold) * 100));

        $voyage     = $departure->voyage;
        $tiers      = $voyage?->pricingTiers()->orderBy('min_people')->get() ?? collect();
        $activeTier = $voyage?->activePricingTier($confirmed);
        $nextTier   = $tiers->first(fn ($t) => $t->min_people > $confirmed);

        return [
            'confirmed_count' => $confirmed,
            'threshold'       => $threshold,
            'progression_pct' => $progression,
            'active_tier'     => $activeTier,
            'next_tier'       => $nextTier,
            'current_price'   => $departure->active_tier_price ?? $departure->base_price ?? $departure->sale_price,
        ];
    }

    public function offerStats(GroupDeal $deal): array
    {
        $deal = $this->syncOfferMetrics($deal);
        $activeTier = $deal->activePricingTier();
        $nextTier = $deal->nextPricingTier();
        $bestTier = $deal->bestPricingTier();

        return [
            'current_participants' => (int) $deal->current_participants,
            'remaining_to_guarantee' => $deal->remaining_to_guarantee,
            'remaining_places' => $deal->remaining_places,
            'progress_percent' => $deal->progress_percent,
            'is_guaranteed' => $deal->is_guaranteed,
            'current_price' => (float) ($deal->current_price ?? 0),
            'active_tier' => $activeTier,
            'next_tier' => $nextTier,
            'best_tier' => $bestTier,
        ];
    }

    public function shareUrlForDeal(GroupDeal $deal, ?int $userId = null): string
    {
        $params = $userId ? ['ref' => $userId] : [];

        return route('front.group-deals.show', array_merge([$deal->slug], $params));
    }

    protected function notifyParticipants(GroupDeal $deal, string $type, string $title, string $message): void
    {
        $userIds = $deal->participants()
            ->whereNotNull('user_id')
            ->whereIn('status', [
                GroupDealParticipant::STATUS_PENDING,
                GroupDealParticipant::STATUS_CONFIRMED,
                GroupDealParticipant::STATUS_PAID,
            ])
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();

        foreach ($userIds as $userId) {
            $this->createUserNotification(
                (int) $userId,
                $type,
                $title,
                $message,
                route('front.group-deals.show', $deal->slug)
            );
        }
    }

    protected function createUserNotification(?int $userId, string $type, string $title, string $message, ?string $link = null): void
    {
        if (! $userId) {
            return;
        }

        $alreadyExists = ClientNotification::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('title', $title)
            ->where('message', $message)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if ($alreadyExists) {
            return;
        }

        ClientNotification::create([
            'user_id' => $userId,
            'type' => Str::limit($type, 64, ''),
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => false,
        ]);
    }
}
