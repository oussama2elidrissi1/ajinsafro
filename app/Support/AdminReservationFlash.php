<?php

namespace App\Support;

use App\Models\Reservation;
use Illuminate\Support\Facades\URL;

/**
 * Données pour le modal de confirmation après création (hub réservations).
 */
final class AdminReservationFlash
{
    /**
     * @return array<string, mixed>
     */
    public static function createdPayload(Reservation $reservation): array
    {
        $reservation->loadMissing(['tour', 'travelDate', 'passengers']);

        $voyageName = $reservation->tour?->name ?? '—';
        $paxCount = max(
            1,
            (int) ($reservation->passengers_count ?? 0),
            $reservation->passengers->count()
        );

        $typeLabel = match ($reservation->prestation_type) {
            'package' => 'Package',
            'vol' => 'Vol',
            'hebergement' => 'Hébergement',
            default => $reservation->prestation_type ? (string) $reservation->prestation_type : '—',
        };

        $statusLabel = match ($reservation->status) {
            Reservation::STATUS_EN_COURS => 'En attente',
            Reservation::STATUS_VALIDEE => 'Confirmée',
            Reservation::STATUS_ANNULEE => 'Annulée',
            default => (string) $reservation->status,
        };

        $dateLabel = $reservation->travelDate?->date
            ? $reservation->travelDate->date->format('d/m/Y')
            : '—';

        $currency = $reservation->tour?->currency_symbol ?? 'MAD';
        $totalLabel = $reservation->base_price !== null
            ? number_format((float) $reservation->base_price, 2, ',', ' ').' '.$currency
            : '—';

        $filteredQuery = array_filter([
            'voyage_id' => $reservation->tour_id > 0 ? $reservation->tour_id : null,
            'travel_date_id' => $reservation->travel_date_id > 0 ? $reservation->travel_date_id : null,
            'status' => $reservation->status,
            'highlight' => $reservation->id,
            'id' => $reservation->id,
            'created' => '1',
        ], fn ($v) => $v !== null && $v !== '');

        return [
            'title' => 'Réservation créée avec succès',
            'id' => $reservation->id,
            'voyage_name' => $voyageName,
            'type_label' => $typeLabel,
            'departure_label' => $dateLabel,
            'pax_count' => $paxCount,
            'total_label' => $totalLabel,
            'status_label' => $statusLabel,
            'debug' => [
                'voyage_id' => (int) $reservation->tour_id,
                'wp_tour_post_id' => $reservation->wp_tour_post_id,
                'travel_date_id' => $reservation->travel_date_id,
                'prestation_type' => $reservation->prestation_type,
                'status' => $reservation->status,
                'branch_id' => $reservation->branch_id,
                'agent_id' => $reservation->agent_id,
                'created_by' => $reservation->created_by,
            ],
            'urls' => [
                'view_in_list' => URL::route('admin.reservations.index', $filteredQuery),
                'view_all' => URL::route('admin.reservations.index'),
                'edit' => URL::route('admin.reservations.edit', $reservation),
            ],
        ];
    }
}
