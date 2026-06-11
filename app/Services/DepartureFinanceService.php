<?php

namespace App\Services;

use App\Models\ChargeType;
use App\Models\Departure;
use App\Models\DepartureCharge;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DepartureFinanceService
{
    public const ENTRY_PAYMENT_METHODS = [
        'cheque' => 'Cheque',
        'ordre_virement' => 'Ordre de virement',
        'versement_espece' => 'Versement espece',
        'espece' => 'Espece',
        'en_ligne' => 'En ligne',
        'carte' => 'Carte',
        'autre' => 'Autre',
    ];

    public const CHARGE_PAYMENT_METHODS = [
        'espece' => 'Espece',
        'cheque' => 'Cheque',
        'ordre_virement' => 'Ordre de virement',
        'carte' => 'Carte',
        'en_ligne' => 'En ligne',
        'autre' => 'Autre',
    ];

    public const CHARGE_PAYMENT_STATUS_LABELS = [
        'non_paye' => 'Non paye',
        'partiel' => 'Partiel',
        'paye' => 'Paye',
    ];

    public function getEntriesByPaymentMethod(Departure $departure): array
    {
        $entries = $this->emptyEntryBuckets();
        $reservations = $this->confirmedReservationsQuery($departure, true)
            ->with(['payments' => fn ($query) => $query->orderBy('id')])
            ->get();

        foreach ($reservations as $reservation) {
            $people = $this->reservationTravelersCount($reservation);
            $seenMethods = [];

            foreach ($reservation->payments as $payment) {
                $amount = round((float) $payment->amount, 2);
                if ($amount <= 0) {
                    continue;
                }

                $method = $this->normalizeEntryPaymentMethod($payment->payment_method);
                $entries[$method]['amount'] = round($entries[$method]['amount'] + $amount, 2);

                if (! isset($seenMethods[$method])) {
                    $entries[$method]['dossiers']++;
                    $entries[$method]['people'] += $people;
                    $seenMethods[$method] = true;
                }
            }
        }

        return $entries;
    }

    public function getChargesByTypeAndPaymentMethod(Departure $departure): array
    {
        $methods = array_keys(self::CHARGE_PAYMENT_METHODS);
        $rows = [];

        $charges = $departure->charges()
            ->with('type')
            ->orderByRaw('COALESCE(charge_type_id, 999999)')
            ->orderBy('title')
            ->get();

        foreach ($charges as $charge) {
            $typeId = $charge->charge_type_id ?: 0;
            $key = (string) $typeId;

            if (! isset($rows[$key])) {
                $rows[$key] = [
                    'type_id' => $charge->charge_type_id,
                    'type_name' => $charge->type?->name ?: 'Autre',
                    'methods' => array_fill_keys($methods, 0.0),
                    'total' => 0.0,
                ];
            }

            $method = in_array($charge->payment_method, $methods, true) ? $charge->payment_method : 'autre';
            $amount = round((float) $charge->amount, 2);
            $rows[$key]['methods'][$method] = round($rows[$key]['methods'][$method] + $amount, 2);
            $rows[$key]['total'] = round($rows[$key]['total'] + $amount, 2);
        }

        return array_values($rows);
    }

    public function getTotalEntries(Departure $departure): float
    {
        return round((float) collect($this->getEntriesByPaymentMethod($departure))->sum('amount'), 2);
    }

    public function getTotalCharges(Departure $departure): float
    {
        return round((float) $departure->charges()->sum('amount'), 2);
    }

    public function getBalance(Departure $departure): float
    {
        return round($this->getTotalEntries($departure) - $this->getTotalCharges($departure), 2);
    }

    public function isProfitable(Departure $departure): bool
    {
        return $this->getBalance($departure) > 0;
    }

    public function getTravelersCount(Departure $departure): int
    {
        return (int) $this->confirmedReservationsQuery($departure)
            ->get()
            ->sum(fn (Reservation $reservation) => $this->reservationTravelersCount($reservation));
    }

    public function getReservationsCount(Departure $departure): int
    {
        return (int) $this->confirmedReservationsQuery($departure)->count();
    }

    public function summarizeDeparture(Departure $departure): array
    {
        $totalEntries = $this->getTotalEntries($departure);
        $totalCharges = $this->getTotalCharges($departure);
        $balance = round($totalEntries - $totalCharges, 2);

        return [
            'departure' => $departure,
            'travelers_count' => $this->getTravelersCount($departure),
            'reservations_count' => $this->getReservationsCount($departure),
            'total_entries' => $totalEntries,
            'total_charges' => $totalCharges,
            'balance' => $balance,
            'is_profitable' => $balance > 0,
        ];
    }

    public function buildInternalTravelSheetData(Departure $departure): array
    {
        $departure->loadMissing(['voyage', 'charges.type']);

        $entries = $this->getEntriesByPaymentMethod($departure);
        $chargesByType = $this->getChargesByTypeAndPaymentMethod($departure);
        $totalChargesByMethod = $this->totalChargesByMethod($chargesByType);
        $summary = $this->summarizeDeparture($departure);

        return [
            'departure' => $departure,
            'voyage' => $departure->voyage,
            'entryMethods' => self::ENTRY_PAYMENT_METHODS,
            'chargeMethods' => self::CHARGE_PAYMENT_METHODS,
            'entries' => $entries,
            'chargesByType' => $chargesByType,
            'totalChargesByMethod' => $totalChargesByMethod,
            'summary' => $summary,
            'generatedAt' => now(),
        ];
    }

    public function paymentMethodLabels(): array
    {
        return self::CHARGE_PAYMENT_METHODS;
    }

    public function paymentStatusLabels(): array
    {
        return self::CHARGE_PAYMENT_STATUS_LABELS;
    }

    private function confirmedReservationsQuery(Departure $departure, bool $mustHavePayment = false): Builder
    {
        $query = Reservation::query()
            ->where('departure_id', $departure->id)
            ->where(function (Builder $query): void {
                $query->whereIn('status', [
                    Reservation::STATUS_CONFIRMED,
                    Reservation::STATUS_PARTIALLY_PAID,
                    Reservation::STATUS_PAID,
                    Reservation::STATUS_VALIDEE,
                ])->orWhereIn('dossier_status', [
                    Reservation::DOSSIER_CONFIRMED,
                    Reservation::DOSSIER_COMPLETED,
                ]);
            })
            ->whereNotIn('status', [
                Reservation::STATUS_CANCELLED,
                Reservation::STATUS_EXPIRED,
                Reservation::STATUS_REFUNDED,
                Reservation::STATUS_ANNULEE,
            ])
            ->where(function (Builder $query): void {
                $query->whereNull('dossier_status')
                    ->orWhere('dossier_status', '')
                    ->orWhereNotIn('dossier_status', [Reservation::DOSSIER_CANCELLED, 'cancelled']);
            });

        if ($mustHavePayment) {
            $query->whereHas('payments', fn (Builder $query) => $query->where('amount', '>', 0));
        }

        return $query;
    }

    private function emptyEntryBuckets(): array
    {
        return collect(self::ENTRY_PAYMENT_METHODS)
            ->map(fn (string $label, string $key) => [
                'key' => $key,
                'label' => $label,
                'dossiers' => 0,
                'people' => 0,
                'amount' => 0.0,
            ])
            ->all();
    }

    private function reservationTravelersCount(Reservation $reservation): int
    {
        $count = (int) ($reservation->passengers_count ?? 0);
        if ($count > 0) {
            return $count;
        }

        return max(0, (int) ($reservation->adults_count ?? 0) + (int) ($reservation->children_count ?? 0) + (int) ($reservation->infants_count ?? 0));
    }

    private function normalizeEntryPaymentMethod(?string $method): string
    {
        $value = Str::of((string) $method)->ascii()->lower()->replace(['-', ' '], '_')->value();

        return match ($value) {
            'cheque', 'check' => 'cheque',
            'virement', 'transfer', 'bank_transfer', 'ordre_virement', 'ordre_de_virement' => 'ordre_virement',
            'cashplus', 'cash_plus', 'versement_espece', 'versement_cash', 'versement' => 'versement_espece',
            'cash', 'espece', 'especes' => 'espece',
            'online', 'en_ligne', 'paiement_en_ligne' => 'en_ligne',
            'card', 'carte', 'carte_bancaire' => 'carte',
            default => 'autre',
        };
    }

    private function totalChargesByMethod(array $chargesByType): array
    {
        $totals = array_fill_keys(array_keys(self::CHARGE_PAYMENT_METHODS), 0.0);

        foreach ($chargesByType as $row) {
            foreach (($row['methods'] ?? []) as $method => $amount) {
                $totals[$method] = round(($totals[$method] ?? 0) + (float) $amount, 2);
            }
        }

        return $totals;
    }
}
