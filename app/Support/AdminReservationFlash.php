<?php

namespace App\Support;

use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Données flash pour le bandeau de succès après création (hub réservations). Aucun champ technique côté UI.
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

        $statusLabel = match ($reservation->status) {
            Reservation::STATUS_EN_COURS => 'En attente',
            Reservation::STATUS_VALIDEE => 'Confirmée',
            Reservation::STATUS_ANNULEE => 'Annulée',
            Reservation::STATUS_SHARED_ROOM_PENDING => 'Chambre partagée (en attente)',
            Reservation::STATUS_SHARED_ROOM_PAIRED => 'Chambre partagée (appariée)',
            default => (string) $reservation->status,
        };

        $dateLabel = $reservation->travelDate?->date
            ? $reservation->travelDate->date->format('d/m/Y')
            : '—';

        $currency = $reservation->tour?->currency_symbol ?? 'MAD';
        $totalAmount = $reservation->total_price;
        $totalLabel = $totalAmount !== null
            ? number_format((float) $totalAmount, 2, ',', ' ').' '.$currency
            : '—';

        $filteredQuery = array_filter([
            'voyage_id' => $reservation->tour_id > 0 ? $reservation->tour_id : null,
            'travel_date_id' => $reservation->travel_date_id > 0 ? $reservation->travel_date_id : null,
            'status' => $reservation->status,
            'highlight' => $reservation->id,
            'id' => $reservation->id,
            'created' => '1',
        ], fn ($v) => $v !== null && $v !== '');

        Log::debug('admin.reservation_created', [
            'reservation_id' => $reservation->id,
            'tour_id' => $reservation->tour_id,
            'wp_tour_post_id' => $reservation->wp_tour_post_id,
            'travel_date_id' => $reservation->travel_date_id,
            'prestation_type' => $reservation->prestation_type,
            'status' => $reservation->status,
            'branch_id' => $reservation->branch_id,
        ]);

        return [
            'id' => $reservation->id,
            'voyage_name' => $voyageName,
            'departure_label' => $dateLabel,
            'pax_count' => $paxCount,
            'total_label' => $totalLabel,
            'status_label' => $statusLabel,
            'urls' => [
                'view_in_list' => URL::route('admin.reservations.index', $filteredQuery),
                'view_all' => URL::route('admin.reservations.index'),
                'edit' => URL::route('admin.reservations.edit', $reservation),
            ],
        ];
    }
}
