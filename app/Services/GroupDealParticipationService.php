<?php

namespace App\Services;

use App\Models\Departure;
use App\Models\Voyage;
use Carbon\Carbon;

class GroupDealParticipationService
{
    /**
     * Sélectionne le départ le plus pertinent pour affichage Group Deal.
     *
     * Règle métier :
     * 1. Prochain départ futur (start_date >= TODAY) avec status "open" ou "full"
     * 2. Si aucun futur, le dernier départ passé même statut
     * 3. Si aucun trouvé, NULL
     *
     * @param Voyage $voyage
     * @return Departure|null
     */
    public function selectMostRelevantDeparture(Voyage $voyage): ?Departure
    {
        $today = Carbon::now()->startOfDay();

        // Chercher d'abord les départs futurs vendables
        $departure = $voyage->departures()
            ->whereIn('status', ['open', 'full'])
            ->where('start_date', '>=', $today)
            ->where('group_deal_enabled', true)
            ->orderBy('start_date', 'asc')
            ->first();

        // Si aucun futur, prendre le dernier passé vendable
        if (!$departure) {
            $departure = $voyage->departures()
                ->whereIn('status', ['open', 'full'])
                ->where('start_date', '<', $today)
                ->where('group_deal_enabled', true)
                ->orderBy('start_date', 'desc')
                ->first();
        }

        return $departure;
    }

    /**
     * Calcule les métriques de participation pour un départ Group Deal.
     *
     * Utilise les vraies données :
     * - total_capacity : departure.total_capacity
     * - confirmed_count : COUNT(reservations avec passengers confirmés)
     * - is_guaranteed : champ réel departure.is_guaranteed (peut être force-set manuellement)
     *
     * @param Departure $departure
     * @return array
     */
    public function calculateParticipationMetrics(Departure $departure): array
    {
        // Charger les réservations confirmées avec les passagers (eager load pour éviter N+1)
        $confirmedReservations = $departure->reservations()
            ->whereIn('status', ['confirmed', 'partially_paid', 'paid'])
            ->with('passengers')
            ->get();

        // Compter les participants via reservation_passengers (SOURCE DE VÉRITÉ)
        $confirmedCount = $confirmedReservations->sum(fn ($r) => $r->passengers->count());

        $totalCapacity = max(1, (int) ($departure->total_capacity ?? 1)); // Protéger division par 0
        $remainingCapacity = max(0, $totalCapacity - $confirmedCount);
        $minimumToGuarantee = (int) ($departure->guaranteed_threshold ?? 0);
        $missingToGuarantee = max(0, $minimumToGuarantee - $confirmedCount);

        $progressPercent = min(100, (int) round(($confirmedCount / $totalCapacity) * 100));

        // Prendre en compte le flag réel is_guaranteed (peut être force-set manuellement)
        // Mais aussi vérifier si le seuil est atteint
        $isGuaranteed = (bool) ($departure->is_guaranteed ?? false) 
            || ($confirmedCount >= $minimumToGuarantee && $minimumToGuarantee > 0);

        $isFull = $confirmedCount >= $totalCapacity;
        $isAlmostFull = !$isFull && $remainingCapacity <= 3;

        $statusLabel = $this->buildStatusLabel(
            $confirmedCount,
            $totalCapacity,
            $remainingCapacity,
            $minimumToGuarantee,
            $missingToGuarantee,
            $isGuaranteed,
            $isFull,
            $isAlmostFull
        );

        return [
            'total_capacity'        => $totalCapacity,
            'confirmed_count'       => $confirmedCount,
            'remaining_capacity'    => $remainingCapacity,
            'minimum_to_guarantee'  => $minimumToGuarantee,
            'missing_to_guarantee'  => $missingToGuarantee,
            'progress_percent'      => $progressPercent,
            'is_guaranteed'         => $isGuaranteed,
            'is_full'               => $isFull,
            'is_almost_full'        => $isAlmostFull,
            'remaining_places'      => $remainingCapacity,
            'status_label'          => $statusLabel,
        ];
    }

    /**
     * Génère le libellé de statut dynamique pour affichage.
     *
     * Règles d'affichage :
     * - Complet → "Complet"
     * - Garanti → "Départ garanti"
     * - Presque complet (≤3 places) → "Plus que X places"
     * - En attente → "+N pour garantir" ou "En attente"
     *
     * @return string
     */
    private function buildStatusLabel(
        int $confirmedCount,
        int $totalCapacity,
        int $remainingCapacity,
        int $minimumToGuarantee,
        int $missingToGuarantee,
        bool $isGuaranteed,
        bool $isFull,
        bool $isAlmostFull
    ): string
    {
        if ($isFull) {
            return 'Complet';
        }

        if ($isGuaranteed && $minimumToGuarantee > 0) {
            return 'Départ garanti';
        }

        if ($isAlmostFull && $remainingCapacity > 0) {
            return $remainingCapacity === 1
                ? 'Plus qu\'1 place'
                : "Plus que {$remainingCapacity} places";
        }

        if ($minimumToGuarantee > 0 && $missingToGuarantee > 0) {
            return $missingToGuarantee === 1
                ? '+1 personne pour garantir'
                : "+{$missingToGuarantee} personnes pour garantir";
        }

        return 'En attente';
    }

    /**
     * Enrichit un voyage avec les métriques du meilleur départ.
     *
     * @param Voyage $voyage
     * @return array|null
     */
    public function getMetricsForVoyage(Voyage $voyage): ?array
    {
        $departure = $this->selectMostRelevantDeparture($voyage);

        if (!$departure) {
            return null;
        }

        return $this->calculateParticipationMetrics($departure);
    }
}
