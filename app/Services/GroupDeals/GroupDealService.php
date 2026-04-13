<?php

namespace App\Services\GroupDeals;

use App\Models\Client;
use App\Models\Departure;
use App\Models\GroupDealParticipant;
use App\Models\Voyage;
use Illuminate\Support\Facades\DB;

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
            'voyages_group_deal' => Voyage::where('is_group_deal', true)->count(),
            'departures_open'    => Departure::where('group_deal_enabled', true)
                ->whereIn('status', ['open', 'limited'])
                ->count(),
            'departures_guaranteed' => Departure::where('group_deal_enabled', true)
                ->where('is_guaranteed', true)
                ->count(),
            'participants_total' => GroupDealParticipant::where('status', GroupDealParticipant::STATUS_CONFIRMED)->count(),
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
        $tiers      = $voyage?->pricingTiers()->orderBy('min_participants')->get() ?? collect();
        $activeTier = $voyage?->activePricingTier($confirmed);
        $nextTier   = $tiers->first(fn ($t) => $t->min_participants > $confirmed);

        return [
            'confirmed_count' => $confirmed,
            'threshold'       => $threshold,
            'progression_pct' => $progression,
            'active_tier'     => $activeTier,
            'next_tier'       => $nextTier,
            'current_price'   => $departure->active_tier_price ?? $departure->base_price ?? $departure->sale_price,
        ];
    }
}
