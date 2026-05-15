<?php

namespace App\Services;

use App\Models\AgentCommissionEntry;
use App\Models\AgentCommissionLog;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Wp\WpPost;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AgentCommissionService
{
    public function createFromReservation(Reservation $reservation, string $source = AgentCommissionEntry::SOURCE_RESERVATION_CREATED): ?AgentCommissionEntry
    {
        return DB::transaction(function () use ($reservation, $source) {
            $reservation = $this->loadReservationContext($reservation);
            $agent = $this->resolveAgent($reservation);

            if (! $agent) {
                return null;
            }

            $this->reverseObsoleteEntries($reservation, $agent->id);

            $existing = AgentCommissionEntry::query()
                ->where('reservation_id', $reservation->id)
                ->where('agent_id', $agent->id)
                ->first();

            if ($existing) {
                return $this->refreshFromReservationStatus($reservation, $source);
            }

            $snapshot = $this->buildSnapshot($reservation, $agent);
            $entry = AgentCommissionEntry::query()->create($snapshot + [
                'source' => $source,
                'commission_status' => $this->resolveStatusForReservation($reservation),
            ]);

            $this->syncStatusTimestamps($entry, null, $entry->commission_status);
            $entry->save();

            $this->logChange(
                $entry,
                action: 'commission_created',
                newStatus: $entry->commission_status,
                newAmount: (float) $entry->commission_total,
                description: 'Commission agent creee a partir de la reservation.',
                createdBy: $entry->created_by
            );

            return $entry->fresh(['agent', 'voyage', 'reservation', 'branch']);
        });
    }

    public function refreshFromReservationStatus(Reservation $reservation, string $source = AgentCommissionEntry::SOURCE_RESERVATION_CREATED): ?AgentCommissionEntry
    {
        return DB::transaction(function () use ($reservation, $source) {
            $reservation = $this->loadReservationContext($reservation);
            $agent = $this->resolveAgent($reservation);

            if (! $agent) {
                return null;
            }

            $this->reverseObsoleteEntries($reservation, $agent->id);

            $entry = AgentCommissionEntry::query()
                ->where('reservation_id', $reservation->id)
                ->where('agent_id', $agent->id)
                ->first();

            if (! $entry) {
                return $this->createFromReservation($reservation, $source);
            }

            $oldStatus = (string) $entry->commission_status;
            $oldAmount = (float) $entry->commission_total;
            $newStatus = $this->resolveStatusForReservation($reservation, $entry);

            $entry->fill([
                'voyage_id' => $reservation->voyage_id ?: $reservation->tour_id,
                'branch_id' => $reservation->branch_id,
                'travel_date_id' => $reservation->travel_date_id,
                'client_name' => $this->clientName($reservation),
                'reservation_total' => $reservation->effective_total_amount,
                'reservation_status' => $reservation->status,
                'payment_status' => $reservation->payment_status,
                'source' => $source,
                'updated_by' => $reservation->updated_by ?: $reservation->created_by_user_id ?: $reservation->created_by,
            ]);
            $this->syncStatusTimestamps($entry, $oldStatus, $newStatus);
            $entry->commission_status = $newStatus;
            $entry->save();

            if ($oldStatus !== $newStatus) {
                $this->logChange(
                    $entry,
                    action: 'status_refreshed',
                    oldStatus: $oldStatus,
                    newStatus: $newStatus,
                    oldAmount: $oldAmount,
                    newAmount: (float) $entry->commission_total,
                    description: 'Statut de commission synchronise depuis la reservation.',
                    createdBy: $entry->updated_by
                );
            }

            return $entry->fresh(['agent', 'voyage', 'reservation', 'branch']);
        });
    }

    public function markAsConfirmed(Reservation $reservation, ?User $adminUser = null): ?AgentCommissionEntry
    {
        return $this->transitionFromReservation(
            $reservation,
            AgentCommissionEntry::STATUS_CONFIRMED,
            'commission_confirmed',
            'Commission confirmee.',
            AgentCommissionEntry::SOURCE_RESERVATION_CONFIRMED,
            $adminUser
        );
    }

    public function markAsPayable(Reservation $reservation, ?User $adminUser = null): ?AgentCommissionEntry
    {
        return $this->transitionFromReservation(
            $reservation,
            AgentCommissionEntry::STATUS_PAYABLE,
            'commission_payable',
            'Commission marquee comme payable.',
            AgentCommissionEntry::SOURCE_PAYMENT_RECEIVED,
            $adminUser
        );
    }

    public function markAsPaid(AgentCommissionEntry $entry, User $adminUser): AgentCommissionEntry
    {
        return DB::transaction(function () use ($entry, $adminUser) {
            $entry = $entry->fresh();
            $oldStatus = (string) $entry->commission_status;
            $oldAmount = (float) $entry->commission_total;

            if ($oldStatus === AgentCommissionEntry::STATUS_PAID) {
                return $entry;
            }

            $this->syncStatusTimestamps($entry, $oldStatus, AgentCommissionEntry::STATUS_PAID);
            $entry->fill([
                'commission_status' => AgentCommissionEntry::STATUS_PAID,
                'source' => AgentCommissionEntry::SOURCE_MANUAL_ADJUSTMENT,
                'updated_by' => $adminUser->id,
            ]);
            $entry->save();

            $this->logChange(
                $entry,
                action: 'commission_paid',
                oldStatus: $oldStatus,
                newStatus: AgentCommissionEntry::STATUS_PAID,
                oldAmount: $oldAmount,
                newAmount: (float) $entry->commission_total,
                description: 'Commission marquee comme payee.',
                createdBy: $adminUser->id
            );

            return $entry->fresh(['logs.creator']);
        });
    }

    public function cancelForReservation(Reservation $reservation, ?User $adminUser = null): ?AgentCommissionEntry
    {
        return $this->cancelOrReverseReservation($reservation, $adminUser, false);
    }

    public function reverseForReservation(Reservation $reservation, ?User $adminUser = null): ?AgentCommissionEntry
    {
        return $this->cancelOrReverseReservation($reservation, $adminUser, true);
    }

    public function applyManualAdjustment(AgentCommissionEntry $entry, array $payload, User $adminUser): AgentCommissionEntry
    {
        return DB::transaction(function () use ($entry, $payload, $adminUser) {
            $entry = $entry->fresh();
            $oldAmount = (float) $entry->commission_total;

            $adult = array_key_exists('commission_adult', $payload)
                ? round(max(0, (float) $payload['commission_adult']), 2)
                : (float) $entry->commission_adult;
            $child = array_key_exists('commission_child', $payload)
                ? round(max(0, (float) $payload['commission_child']), 2)
                : (float) $entry->commission_child;
            $baby = array_key_exists('commission_baby', $payload)
                ? round(max(0, (float) $payload['commission_baby']), 2)
                : (float) $entry->commission_baby;
            $total = array_key_exists('commission_total', $payload)
                ? round(max(0, (float) $payload['commission_total']), 2)
                : round($adult + $child + $baby, 2);

            $metadata = $entry->metadata ?? [];
            $metadata['manual_adjustment'] = [
                'previous_total' => $oldAmount,
                'adjusted_at' => now()->toIso8601String(),
            ];

            $entry->fill([
                'commission_adult' => $adult,
                'commission_child' => $child,
                'commission_baby' => $baby,
                'commission_total' => $total,
                'source' => AgentCommissionEntry::SOURCE_MANUAL_ADJUSTMENT,
                'notes' => $payload['notes'] ?? $entry->notes,
                'metadata' => $metadata,
                'updated_by' => $adminUser->id,
            ]);
            $entry->save();

            $this->logChange(
                $entry,
                action: 'manual_adjustment',
                oldStatus: $entry->commission_status,
                newStatus: $entry->commission_status,
                oldAmount: $oldAmount,
                newAmount: $total,
                description: 'Ajustement manuel de la commission.',
                createdBy: $adminUser->id
            );

            return $entry->fresh(['logs.creator']);
        });
    }

    public function logChange(
        AgentCommissionEntry $entry,
        string $action,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?float $oldAmount = null,
        ?float $newAmount = null,
        ?string $description = null,
        ?int $createdBy = null,
    ): AgentCommissionLog {
        return AgentCommissionLog::query()->create([
            'commission_entry_id' => $entry->id,
            'agent_id' => $entry->agent_id,
            'reservation_id' => $entry->reservation_id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'old_amount' => $oldAmount,
            'new_amount' => $newAmount,
            'action' => $action,
            'description' => $description,
            'created_by' => $createdBy,
            'created_at' => now(),
        ]);
    }

    public function resolveStatusForReservation(Reservation $reservation, ?AgentCommissionEntry $entry = null): string
    {
        $reservationStatus = (string) ($reservation->status ?? '');
        $paymentStatus = (string) ($reservation->payment_status ?? '');
        $current = (string) ($entry?->commission_status ?? AgentCommissionEntry::STATUS_ESTIMATED);

        if (in_array($reservationStatus, [
            Reservation::STATUS_CANCELLED,
            Reservation::STATUS_EXPIRED,
            Reservation::STATUS_REFUNDED,
        ], true)) {
            return $current === AgentCommissionEntry::STATUS_PAID
                ? AgentCommissionEntry::STATUS_REVERSED
                : AgentCommissionEntry::STATUS_CANCELLED;
        }

        if ($current === AgentCommissionEntry::STATUS_REVERSED) {
            return $current;
        }

        $target = AgentCommissionEntry::STATUS_ESTIMATED;

        if ($paymentStatus === Reservation::PAYMENT_STATUS_PAID) {
            $target = AgentCommissionEntry::STATUS_PAYABLE;
        } elseif (in_array($reservationStatus, [
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_PAID,
            Reservation::STATUS_PARTIALLY_PAID,
        ], true)) {
            $target = AgentCommissionEntry::STATUS_CONFIRMED;
        }

        if ($current === AgentCommissionEntry::STATUS_PAID) {
            return $current;
        }

        return $this->statusPriority($target) >= $this->statusPriority($current)
            ? $target
            : $current;
    }

    private function transitionFromReservation(
        Reservation $reservation,
        string $targetStatus,
        string $action,
        string $description,
        string $source,
        ?User $adminUser = null,
    ): ?AgentCommissionEntry {
        return DB::transaction(function () use ($reservation, $targetStatus, $action, $description, $source, $adminUser) {
            $entry = $this->refreshFromReservationStatus($reservation, $source);

            if (! $entry) {
                return null;
            }

            $oldStatus = (string) $entry->commission_status;
            $oldAmount = (float) $entry->commission_total;

            if ($this->statusPriority($targetStatus) <= $this->statusPriority($oldStatus)) {
                return $entry;
            }

            $this->syncStatusTimestamps($entry, $oldStatus, $targetStatus);
            $entry->fill([
                'commission_status' => $targetStatus,
                'source' => $source,
                'updated_by' => $adminUser?->id ?: $reservation->updated_by ?: $reservation->created_by_user_id ?: $reservation->created_by,
            ]);
            $entry->save();

            $this->logChange(
                $entry,
                action: $action,
                oldStatus: $oldStatus,
                newStatus: $targetStatus,
                oldAmount: $oldAmount,
                newAmount: (float) $entry->commission_total,
                description: $description,
                createdBy: $entry->updated_by
            );

            return $entry->fresh(['logs.creator']);
        });
    }

    private function cancelOrReverseReservation(Reservation $reservation, ?User $adminUser, bool $forceReverse): ?AgentCommissionEntry
    {
        return DB::transaction(function () use ($reservation, $adminUser, $forceReverse) {
            $reservation = $this->loadReservationContext($reservation);
            $entries = AgentCommissionEntry::query()
                ->where('reservation_id', $reservation->id)
                ->get();

            if ($entries->isEmpty()) {
                return null;
            }

            $result = null;

            foreach ($entries as $entry) {
                $oldStatus = (string) $entry->commission_status;
                $oldAmount = (float) $entry->commission_total;
                $newStatus = $forceReverse || $oldStatus === AgentCommissionEntry::STATUS_PAID
                    ? AgentCommissionEntry::STATUS_REVERSED
                    : AgentCommissionEntry::STATUS_CANCELLED;

                if ($oldStatus === $newStatus) {
                    $result = $entry;
                    continue;
                }

                $this->syncStatusTimestamps($entry, $oldStatus, $newStatus);
                $entry->fill([
                    'commission_status' => $newStatus,
                    'reservation_status' => $reservation->status,
                    'payment_status' => $reservation->payment_status,
                    'source' => AgentCommissionEntry::SOURCE_CANCELLATION,
                    'updated_by' => $adminUser?->id ?: $reservation->updated_by ?: $reservation->created_by_user_id ?: $reservation->created_by,
                ]);
                $entry->save();

                $this->logChange(
                    $entry,
                    action: $newStatus === AgentCommissionEntry::STATUS_REVERSED ? 'commission_reversed' : 'commission_cancelled',
                    oldStatus: $oldStatus,
                    newStatus: $newStatus,
                    oldAmount: $oldAmount,
                    newAmount: (float) $entry->commission_total,
                    description: $newStatus === AgentCommissionEntry::STATUS_REVERSED
                        ? 'Commission reversee suite a une annulation apres paiement.'
                        : 'Commission annulee suite a une annulation de reservation.',
                    createdBy: $entry->updated_by
                );

                $result = $entry;
            }

            return $result?->fresh(['logs.creator']);
        });
    }

    private function buildSnapshot(Reservation $reservation, User $agent): array
    {
        $counts = $this->resolvePassengerCounts($reservation);
        $units = $this->resolveCommissionUnits($reservation);
        $prices = $this->resolvePriceUnits($reservation);

        $adultUnit = $this->computeCommissionUnit($units['adult'], $units['adult_type'], $prices['adult']);
        $childUnit = $this->computeCommissionUnit($units['child'], $units['child_type'], $prices['child']);
        $babyUnit = $units['baby'];

        $adult = round($adultUnit * $counts['adult'], 2);
        $child = round($childUnit * $counts['child'], 2);
        $baby = round($babyUnit * $counts['baby'], 2);
        $total = round($adult + $child + $baby, 2);

        return [
            'agent_id' => $agent->id,
            'reservation_id' => $reservation->id,
            'voyage_id' => $reservation->voyage_id ?: $reservation->tour_id,
            'branch_id' => $reservation->branch_id,
            'travel_date_id' => $reservation->travel_date_id,
            'client_name' => $this->clientName($reservation),
            'reservation_total' => $reservation->effective_total_amount,
            'commission_base_amount' => $total,
            'commission_adult' => $adult,
            'commission_child' => $child,
            'commission_baby' => $baby,
            'commission_total' => $total,
            'reservation_status' => $reservation->status,
            'payment_status' => $reservation->payment_status,
            'calculated_at' => $reservation->created_at ?: now(),
            'created_by' => $reservation->created_by_user_id ?: $reservation->created_by,
            'updated_by' => $reservation->updated_by ?: $reservation->created_by_user_id ?: $reservation->created_by,
            'metadata' => [
                'snapshot' => [
                    'adult_count' => $counts['adult'],
                    'child_count' => $counts['child'],
                    'baby_count' => $counts['baby'],
                    'adult_unit_raw' => $units['adult'],
                    'adult_unit_type' => $units['adult_type'],
                    'adult_unit_effective' => $adultUnit,
                    'child_unit_raw' => $units['child'],
                    'child_unit_type' => $units['child_type'],
                    'child_unit_effective' => $childUnit,
                    'baby_unit' => $babyUnit,
                ],
                'reservation' => [
                    'total_base' => $reservation->effective_total_base,
                    'extras_total' => $reservation->effective_extras_total,
                    'remaining_amount' => $reservation->effective_remaining_amount,
                ],
                'source' => [
                    'wp_post_id' => $reservation->tour?->wp_post_id ?? null,
                    'agent_resolution' => $reservation->agent_id ? 'agent_id' : 'operational_actor',
                ],
            ],
        ];
    }

    private function loadReservationContext(Reservation $reservation): Reservation
    {
        return $reservation->loadMissing([
            'tour:id,wp_post_id,name',
            'voyage:id,wp_post_id,name',
            'departure:id,start_date',
            'travelDate:id,date',
            'agent:id,name,branch_id,manager_id',
            'createdBy:id,name,branch_id,manager_id',
            'creator:id,name,branch_id,manager_id',
            'passengers:id,reservation_id,type',
        ]);
    }

    private function resolveAgent(Reservation $reservation): ?User
    {
        $agent = $reservation->agent ?: $reservation->resolveOperationalActorUser();

        return $agent instanceof User ? $agent : null;
    }

    private function resolvePassengerCounts(Reservation $reservation): array
    {
        $adult = (int) ($reservation->adults_count ?? 0);
        $child = (int) ($reservation->children_count ?? 0);
        $baby = (int) ($reservation->infants_count ?? 0);

        if ($adult + $child + $baby > 0) {
            return compact('adult', 'child', 'baby');
        }

        $adult = (int) $reservation->passengers->where('type', 'adult')->count();
        $child = (int) $reservation->passengers->where('type', 'child')->count();
        $baby = (int) $reservation->passengers->where('type', 'infant')->count();

        if ($adult + $child + $baby === 0) {
            $adult = max(1, (int) ($reservation->passengers_count ?? 1));
        }

        return compact('adult', 'child', 'baby');
    }

    private function resolveCommissionUnits(Reservation $reservation): array
    {
        $wpPostId = (int) ($reservation->tour?->wp_post_id ?? $reservation->voyage?->wp_post_id ?? 0);
        $default = ['adult' => 0.0, 'child' => 0.0, 'baby' => 0.0, 'adult_type' => 'fixed', 'child_type' => 'fixed'];

        if ($wpPostId <= 0) {
            return $default;
        }

        try {
            $wpPost = WpPost::query()->find($wpPostId);
        } catch (\Throwable) {
            return $default;
        }

        if (! $wpPost) {
            return $default;
        }

        $adult = $this->metaToFloat($wpPost->getMeta('commission_adulte'));
        $child = $this->metaToFloat($wpPost->getMeta('commission_enfant'));
        $baby = $this->metaToFloat(
            $wpPost->getMeta('commission_baby', $wpPost->getMeta('commission_bebe', $wpPost->getMeta('commission_infant', 0)))
        );

        $adultType = $this->normalizeCommissionType($wpPost->getMeta('commission_adulte_type'));
        $childType = $this->normalizeCommissionType($wpPost->getMeta('commission_enfant_type'));

        return [
            'adult' => $adult,
            'child' => $child,
            'baby' => $baby,
            'adult_type' => $adultType,
            'child_type' => $childType,
        ];
    }

    private function resolvePriceUnits(Reservation $reservation): array
    {
        $wpPostId = (int) ($reservation->tour?->wp_post_id ?? $reservation->voyage?->wp_post_id ?? 0);
        $default = ['adult' => 0.0, 'child' => 0.0];

        if ($wpPostId <= 0) {
            return $default;
        }

        try {
            $wpPost = WpPost::query()->find($wpPostId);
        } catch (\Throwable) {
            return $default;
        }

        if (! $wpPost) {
            return $default;
        }

        $adult = $this->metaToFloat($wpPost->getMeta('adult_price'));
        $child = $this->metaToFloat($wpPost->getMeta('child_price'));

        if ($adult <= 0) {
            $voyage = $reservation->voyage ?: $reservation->tour;
            if ($voyage && $voyage->price_from > 0) {
                $adult = (float) $voyage->price_from;
            }
        }

        return [
            'adult' => $adult,
            'child' => $child,
        ];
    }

    private function computeCommissionUnit(float $value, string $type, float $basePrice): float
    {
        if ($value <= 0) {
            return 0.0;
        }

        if ($type === 'percentage' && $basePrice > 0) {
            return round($basePrice * ($value / 100), 2);
        }

        return $value;
    }

    private function normalizeCommissionType(mixed $raw): string
    {
        $type = strtolower(trim((string) ($raw ?? '')));
        if (in_array($type, ['percent', 'percentage', 'pourcentage', '%'], true)) {
            return 'percentage';
        }

        return 'fixed';
    }

    private function reverseObsoleteEntries(Reservation $reservation, int $activeAgentId): void
    {
        $obsolete = AgentCommissionEntry::query()
            ->where('reservation_id', $reservation->id)
            ->where('agent_id', '!=', $activeAgentId)
            ->whereNotIn('commission_status', [
                AgentCommissionEntry::STATUS_CANCELLED,
                AgentCommissionEntry::STATUS_REVERSED,
            ])
            ->get();

        foreach ($obsolete as $entry) {
            $oldStatus = (string) $entry->commission_status;
            $newStatus = $oldStatus === AgentCommissionEntry::STATUS_PAID
                ? AgentCommissionEntry::STATUS_REVERSED
                : AgentCommissionEntry::STATUS_CANCELLED;

            $this->syncStatusTimestamps($entry, $oldStatus, $newStatus);
            $entry->fill([
                'commission_status' => $newStatus,
                'source' => AgentCommissionEntry::SOURCE_RESERVATION_REASSIGNED,
                'updated_by' => $reservation->updated_by ?: $reservation->created_by_user_id ?: $reservation->created_by,
            ]);
            $entry->save();

            $this->logChange(
                $entry,
                action: 'commission_reassigned',
                oldStatus: $oldStatus,
                newStatus: $newStatus,
                oldAmount: (float) $entry->commission_total,
                newAmount: (float) $entry->commission_total,
                description: 'Ancienne commission neutralisee suite a une reaffectation de reservation.',
                createdBy: $entry->updated_by
            );
        }
    }

    private function syncStatusTimestamps(AgentCommissionEntry $entry, ?string $oldStatus, string $newStatus): void
    {
        if ($newStatus === AgentCommissionEntry::STATUS_CONFIRMED && ! $entry->confirmed_at) {
            $entry->confirmed_at = now();
        }

        if ($newStatus === AgentCommissionEntry::STATUS_PAYABLE) {
            $entry->confirmed_at = $entry->confirmed_at ?: now();
            $entry->payable_at = $entry->payable_at ?: now();
        }

        if ($newStatus === AgentCommissionEntry::STATUS_PAID) {
            $entry->confirmed_at = $entry->confirmed_at ?: now();
            $entry->payable_at = $entry->payable_at ?: now();
            $entry->paid_at = $entry->paid_at ?: now();
        }

        if ($oldStatus === AgentCommissionEntry::STATUS_CANCELLED && $newStatus !== AgentCommissionEntry::STATUS_CANCELLED) {
            $entry->updated_at = now();
        }
    }

    private function clientName(Reservation $reservation): string
    {
        return trim((string) ($reservation->client_first_name ?? '').' '.(string) ($reservation->client_last_name ?? ''));
    }

    private function metaToFloat(mixed $value): float
    {
        return is_numeric($value) ? round((float) $value, 2) : 0.0;
    }

    private function statusPriority(string $status): int
    {
        return match ($status) {
            AgentCommissionEntry::STATUS_CONFIRMED => 20,
            AgentCommissionEntry::STATUS_PAYABLE => 30,
            AgentCommissionEntry::STATUS_PAID => 40,
            AgentCommissionEntry::STATUS_CANCELLED => 50,
            AgentCommissionEntry::STATUS_REVERSED => 60,
            default => 10,
        };
    }
}
