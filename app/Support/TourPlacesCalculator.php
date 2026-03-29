<?php

namespace App\Support;

use App\Models\TourHotelRoom;

/**
 * Calcul unique des "places" (somme nb_chambres × capacité) pour aligner front / back.
 *
 * Capacité par ligne : capacity_total si > 0, sinon capacity_adults + capacity_children.
 * Si capacité finale ≤ 0 ou room_count ≤ 0 : ligne ignorée.
 * room_type vide après trim : ignorée.
 * is_active : en base, exclure seulement 0 / false / '0' explicites ; NULL ou colonne absente = actif.
 *            en requête formulaire, actif seulement si is_active === 1 (entier), ex. hidden 0 + checkbox 1.
 */
final class TourPlacesCalculator
{
    public static function effectiveCapacity(int $capacityTotal, int $adults, int $children): int
    {
        if ($capacityTotal > 0) {
            return $capacityTotal;
        }

        return max(0, $adults + $children);
    }

    public static function isDbRoomExplicitlyInactive(TourHotelRoom $room): bool
    {
        $attrs = $room->getAttributes();
        if (! array_key_exists('is_active', $attrs)) {
            return false;
        }
        $ia = $attrs['is_active'];

        return $ia === false || $ia === 0 || $ia === '0';
    }

    /**
     * @param  iterable<\App\Models\TourHotel>  $tourHotels  Hôtels déjà chargés avec relation rooms
     * @return array{total: int, lines: list<array<string, mixed>>, ignored: list<array<string, mixed>>}
     */
    public static function explainFromDatabase(iterable $tourHotels): array
    {
        $total = 0;
        $lines = [];
        $ignored = [];

        foreach ($tourHotels as $hotel) {
            $hid = (int) $hotel->id;
            foreach ($hotel->rooms as $room) {
                $rid = (int) $room->id;
                $type = trim((string) ($room->room_type ?? ''));
                if ($type === '') {
                    $ignored[] = ['scope' => 'db', 'hotel_id' => $hid, 'room_id' => $rid, 'reason' => 'empty_room_type'];

                    continue;
                }
                if (self::isDbRoomExplicitlyInactive($room)) {
                    $ignored[] = ['scope' => 'db', 'hotel_id' => $hid, 'room_id' => $rid, 'room_type' => $type, 'reason' => 'is_active_off'];

                    continue;
                }
                $count = (int) ($room->room_count ?? 0);
                if ($count <= 0) {
                    $ignored[] = ['scope' => 'db', 'hotel_id' => $hid, 'room_id' => $rid, 'room_type' => $type, 'reason' => 'room_count_zero'];

                    continue;
                }
                $capTotal = (int) ($room->capacity_total ?? 0);
                $ad = (int) ($room->capacity_adults ?? 0);
                $ch = (int) ($room->capacity_children ?? 0);
                $cap = self::effectiveCapacity($capTotal, $ad, $ch);
                if ($cap <= 0) {
                    $ignored[] = ['scope' => 'db', 'hotel_id' => $hid, 'room_id' => $rid, 'room_type' => $type, 'reason' => 'capacity_zero'];

                    continue;
                }
                $product = $count * $cap;
                $total += $product;
                $lines[] = [
                    'scope' => 'db',
                    'hotel_id' => $hid,
                    'room_id' => $rid,
                    'room_type' => $type,
                    'room_count' => $count,
                    'capacity_used' => $cap,
                    'capacity_total' => $capTotal,
                    'capacity_adults' => $ad,
                    'capacity_children' => $ch,
                    'product' => $product,
                ];
            }
        }

        return ['total' => $total, 'lines' => $lines, 'ignored' => $ignored];
    }

    public static function sumFromDatabase(iterable $tourHotels): int
    {
        return self::explainFromDatabase($tourHotels)['total'];
    }

    /**
     * Même règles que le JS / affichage formulaire (tour_hotels[*][rooms][*]).
     *
     * @param  array<int, mixed>  $tourHotelsInput
     * @return array{total: int, lines: list<array<string, mixed>>, ignored: list<array<string, mixed>>}
     */
    public static function explainFromRequestArray(array $tourHotelsInput): array
    {
        $total = 0;
        $lines = [];
        $ignored = [];

        foreach ($tourHotelsInput as $hi => $hotelRow) {
            if (! is_array($hotelRow)) {
                continue;
            }
            $rooms = $hotelRow['rooms'] ?? [];
            if (! is_array($rooms)) {
                continue;
            }
            foreach ($rooms as $ri => $r) {
                if (! is_array($r)) {
                    continue;
                }
                $type = trim((string) ($r['room_type'] ?? ''));
                if ($type === '') {
                    $ignored[] = ['scope' => 'request', 'hotel_index' => $hi, 'room_index' => $ri, 'reason' => 'empty_room_type'];

                    continue;
                }
                $active = (int) ($r['is_active'] ?? 0) === 1;
                if (! $active) {
                    $ignored[] = ['scope' => 'request', 'hotel_index' => $hi, 'room_index' => $ri, 'room_type' => $type, 'reason' => 'is_active_off'];

                    continue;
                }
                $count = (int) ($r['room_count'] ?? 0);
                if ($count <= 0) {
                    $ignored[] = ['scope' => 'request', 'hotel_index' => $hi, 'room_index' => $ri, 'room_type' => $type, 'reason' => 'room_count_zero'];

                    continue;
                }
                $capTotal = (int) ($r['capacity_total'] ?? 0);
                $ad = (int) ($r['capacity_adults'] ?? 0);
                $ch = (int) ($r['capacity_children'] ?? 0);
                $cap = self::effectiveCapacity($capTotal, $ad, $ch);
                if ($cap <= 0) {
                    $ignored[] = ['scope' => 'request', 'hotel_index' => $hi, 'room_index' => $ri, 'room_type' => $type, 'reason' => 'capacity_zero'];

                    continue;
                }
                $product = $count * $cap;
                $total += $product;
                $lines[] = [
                    'scope' => 'request',
                    'hotel_index' => $hi,
                    'room_index' => $ri,
                    'room_type' => $type,
                    'room_count' => $count,
                    'capacity_used' => $cap,
                    'capacity_total' => $capTotal,
                    'capacity_adults' => $ad,
                    'capacity_children' => $ch,
                    'product' => $product,
                ];
            }
        }

        return ['total' => $total, 'lines' => $lines, 'ignored' => $ignored];
    }
}
