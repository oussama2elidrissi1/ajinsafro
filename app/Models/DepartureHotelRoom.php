<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartureHotelRoom extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_LIMITED = 'limited';
    public const STATUS_FULL = 'full';
    public const STATUS_CLOSED = 'closed';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_LIMITED,
        self::STATUS_FULL,
        self::STATUS_CLOSED,
        self::STATUS_INACTIVE,
    ];

    protected $fillable = [
        'departure_hotel_id',
        'room_id',
        'room_type',
        'capacity_total',
        'total_rooms',
        'reserved_rooms',
        'total_places',
        'reserved_places',
        'available_rooms',
        'available_places',
        'supplement',
        'status',
    ];

    protected $casts = [
        'departure_hotel_id' => 'integer',
        'room_id' => 'integer',
        'capacity_total' => 'integer',
        'total_rooms' => 'integer',
        'reserved_rooms' => 'integer',
        'total_places' => 'integer',
        'reserved_places' => 'integer',
        'available_rooms' => 'integer',
        'available_places' => 'integer',
        'supplement' => 'decimal:2',
    ];

    public function departureHotel(): BelongsTo
    {
        return $this->belongsTo(DepartureHotel::class);
    }

    /**
     * Capacity per physical room (same DB column as legacy name capacity_total).
     */
    public function getCapacityPerRoomAttribute(): int
    {
        return max(0, (int) $this->capacity_total);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_AVAILABLE => 'Disponible',
            self::STATUS_LIMITED => 'Limité',
            self::STATUS_FULL => 'Complet',
            self::STATUS_CLOSED => 'Fermé',
            self::STATUS_INACTIVE => 'Inactif',
            default => $status,
        };
    }

    /**
     * Places = chambres × capacité/chambre (admin peut ensuite ajuster available_places à la main).
     */
    public function syncAvailablePlacesFromRoomsAndCapacity(): void
    {
        $rooms = max(0, (int) $this->available_rooms);
        $cap = max(1, (int) $this->capacity_total);

        $this->available_places = $rooms * $cap;
    }
}

