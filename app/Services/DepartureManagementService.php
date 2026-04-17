<?php

namespace App\Services;

use App\Models\Departure;
use Illuminate\Support\Collection;

class DepartureManagementService
{
    public function stockConsumingStatuses(): array
    {
        $consuming = (array) config('booking_lifecycle.stock_consuming_statuses', []);

        if ((bool) config('booking_lifecycle.option_holds_stock', false)) {
            $consuming = array_merge($consuming, (array) config('booking_lifecycle.stock_hold_statuses', []));
        }

        return collect($consuming)
            ->filter(fn ($status) => is_string($status) && $status !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, Departure>|array<int, Departure> $departures
     * @return Collection<int, array<string, mixed>>
     */
    public function buildDepartureMetrics(Collection|array $departures): Collection
    {
        $rows = $departures instanceof Collection ? $departures : collect($departures);

        return $rows->map(function (Departure $departure) {
            $hotels = collect($departure->departureHotels ?? []);
            $rooms = $hotels->flatMap(fn ($hotel) => collect($hotel->rooms ?? []));

            $total = max(0, (int) ($departure->total_capacity ?? 0));
            $reserved = max(0, (int) ($departure->reserved_passengers_sum ?? $departure->reserved_capacity ?? 0));
            if ($reserved > $total) {
                $reserved = $total;
            }

            $available = max(0, $total - $reserved);
            $roomTotal = (int) $rooms->sum('total_places');
            $roomReserved = (int) $rooms->sum('reserved_places');
            $roomAvailable = (int) $rooms->sum('available_places');

            return [
                'id' => (int) $departure->id,
                'start_date_iso' => optional($departure->start_date)->format('Y-m-d'),
                'start_date_label' => optional($departure->start_date)->format('d/m/Y'),
                'end_date_iso' => optional($departure->end_date)->format('Y-m-d'),
                'end_date_label' => $departure->end_date ? optional($departure->end_date)->format('d/m/Y') : null,
                'status' => (string) ($departure->status ?? Departure::STATUS_DRAFT),
                'status_label' => (string) ($departure->status_label ?? ($departure->status ?? '')),
                'total_capacity' => $total,
                'reserved_capacity' => $reserved,
                'available_capacity' => $available,
                'rooms_count' => (int) $rooms->count(),
                'room_total_places' => $roomTotal,
                'room_reserved_places' => $roomReserved,
                'room_available_places' => $roomAvailable,
                'room_mismatch' => $roomTotal > 0 && $roomTotal !== $total,
            ];
        })->values();
    }

    /**
     * @param Collection<int, array<string, mixed>>|array<int, array<string, mixed>> $metrics
     * @return array<string, int>
     */
    public function summarizeMetrics(Collection|array $metrics): array
    {
        $rows = $metrics instanceof Collection ? $metrics : collect($metrics);

        return [
            'total_departures' => (int) $rows->count(),
            'active_departures' => (int) $rows->whereIn('status', [Departure::STATUS_OPEN, Departure::STATUS_LIMITED])->count(),
            'full_departures' => (int) $rows->where('status', Departure::STATUS_FULL)->count(),
            'closed_departures' => (int) $rows->whereIn('status', [Departure::STATUS_CLOSED, Departure::STATUS_CANCELED, Departure::STATUS_CANCELLED])->count(),
            'total_capacity' => (int) $rows->sum('total_capacity'),
            'reserved_capacity' => (int) $rows->sum('reserved_capacity'),
            'available_capacity' => (int) $rows->sum('available_capacity'),
        ];
    }
}
