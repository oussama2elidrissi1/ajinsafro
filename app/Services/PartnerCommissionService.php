<?php

namespace App\Services;

use App\Models\PartnerCommission;
use App\Models\PartnerCommissionRule;
use App\Models\Reservation;
use Carbon\Carbon;

class PartnerCommissionService
{
    /**
     * Calcule et enregistre (ou met à jour) la commission pour une réservation partenaire.
     * Appelé après create/update de réservation quand partner_id est renseigné.
     */
    public function calculateAndSaveForReservation(Reservation $reservation): ?PartnerCommission
    {
        if (!$reservation->partner_id || !$reservation->tour_id) {
            return null;
        }

        $total = (float) ($reservation->total_price ?? 0);
        if ($total <= 0 && $reservation->base_price !== null) {
            $total = (float) $reservation->base_price + (float) ($reservation->room_supplement_total ?? 0);
        }
        if ($total <= 0 && $reservation->tour) {
            $total = (float) ($reservation->tour->price_from ?? 0);
        }

        $rule = $this->findApplicableRule($reservation->partner_id, $reservation->tour_id);
        $amount = $rule ? $this->calculateAmount($rule, $total) : 0;

        $commission = PartnerCommission::query()->where('reservation_id', $reservation->id)->first();
        $status = $commission ? $commission->status : PartnerCommission::STATUS_CALCULATED;
        if ($reservation->status === Reservation::STATUS_ANNULEE) {
            $status = PartnerCommission::STATUS_CANCELLED;
        } elseif ($reservation->status === Reservation::STATUS_VALIDEE) {
            if ($status === PartnerCommission::STATUS_CALCULATED || $status === PartnerCommission::STATUS_PENDING) {
                $status = PartnerCommission::STATUS_VALIDATED;
            }
        }

        if ($commission) {
            $commission->update([
                'rule_id' => $rule?->id,
                'reservation_total' => $total,
                'amount' => $amount,
                'status' => $status,
                'validated_at' => $status === PartnerCommission::STATUS_VALIDATED || $status === PartnerCommission::STATUS_PAID
                    ? ($commission->validated_at ?? now()) : null,
            ]);
            return $commission;
        }

        return PartnerCommission::create([
            'reservation_id' => $reservation->id,
            'partner_id' => $reservation->partner_id,
            'rule_id' => $rule?->id,
            'reservation_total' => $total,
            'amount' => $amount,
            'status' => $status,
            'validated_at' => $status === PartnerCommission::STATUS_VALIDATED ? now() : null,
        ]);
    }

    /**
     * Passe la commission en "validée" quand la réservation est confirmée / payée.
     */
    public function validateCommissionForReservation(Reservation $reservation): void
    {
        $commission = PartnerCommission::query()
            ->where('reservation_id', $reservation->id)
            ->whereIn('status', [PartnerCommission::STATUS_CALCULATED, PartnerCommission::STATUS_PENDING])
            ->first();
        if ($commission) {
            $commission->update([
                'status' => PartnerCommission::STATUS_VALIDATED,
                'validated_at' => $commission->validated_at ?? now(),
            ]);
        }
    }

    /**
     * Annule la commission (réservation annulée).
     */
    public function cancelCommissionForReservation(Reservation $reservation): void
    {
        PartnerCommission::query()
            ->where('reservation_id', $reservation->id)
            ->whereNotIn('status', [PartnerCommission::STATUS_PAID])
            ->update([
                'status' => PartnerCommission::STATUS_CANCELLED,
            ]);
    }

    private function findApplicableRule(int $partnerId, int $voyageId): ?PartnerCommissionRule
    {
        $today = Carbon::today();
        $query = PartnerCommissionRule::query()
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $today);
            });

        $rule = (clone $query)
            ->where('partner_id', $partnerId)
            ->where('voyage_id', $voyageId)
            ->first();
        if ($rule) {
            return $rule;
        }
        $rule = (clone $query)
            ->where('partner_id', $partnerId)
            ->whereNull('voyage_id')
            ->first();
        if ($rule) {
            return $rule;
        }
        return (clone $query)
            ->whereNull('partner_id')
            ->where(function ($q) use ($voyageId) {
                $q->where('voyage_id', $voyageId)->orWhereNull('voyage_id');
            })
            ->orderByRaw('voyage_id IS NOT NULL DESC')
            ->first();
    }

    private function calculateAmount(PartnerCommissionRule $rule, float $reservationTotal): float
    {
        if ($rule->type === PartnerCommissionRule::TYPE_PERCENT) {
            return round($reservationTotal * ((float) $rule->value / 100), 2);
        }
        return (float) $rule->value;
    }
}
