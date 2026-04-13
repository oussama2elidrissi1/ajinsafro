<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;

class Departure extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_OPEN = 'open';

    public const STATUS_LIMITED = 'limited';

    public const STATUS_FULL = 'full';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELED = 'canceled';

    /** @deprecated Préférer la graphie cancelled */
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_OPEN,
        self::STATUS_LIMITED,
        self::STATUS_FULL,
        self::STATUS_CLOSED,
        self::STATUS_CANCELED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'voyage_id',
        'wp_travel_date_id',
        'start_date',
        'end_date',
        'status',
        'total_capacity',
        'reserved_capacity',
        'available_capacity',
        'base_price',
        'sale_price',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'voyage_id' => 'integer',
        'wp_travel_date_id' => 'integer',
        'total_capacity' => 'integer',
        'reserved_capacity' => 'integer',
        'available_capacity' => 'integer',
        'base_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    public function voyage()
    {
        return $this->belongsTo(Voyage::class);
    }

    public function departureHotels(): HasMany
    {
        return $this->hasMany(DepartureHotel::class)->orderBy('sort_order')->orderBy('id');
    }

    public function rooms(): HasManyThrough
    {
        return $this->hasManyThrough(
            DepartureHotelRoom::class,
            DepartureHotel::class,
            'departure_id',
            'departure_hotel_id'
        )->orderBy('departure_hotel_rooms.id');
    }

    public function roomAllocations(): HasMany
    {
        return $this->hasMany(DepartureRoomAllocation::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Recompute available_capacity from departure_hotel_rooms.available_places (excluding full/closed).
     * Does not automatically persist unless $save=true.
     */
    public function recomputeAvailableCapacity(bool $save = true): int
    {
        $total = (int) DB::connection($this->getConnectionName() ?: config('database.default'))
            ->table('departure_hotel_rooms as dhr')
            ->join('departure_hotels as dh', 'dh.id', '=', 'dhr.departure_hotel_id')
            ->where('dh.departure_id', $this->id)
            ->where('dh.is_active', true)
            ->whereNotIn('dhr.status', [DepartureHotelRoom::STATUS_FULL, DepartureHotelRoom::STATUS_CLOSED, DepartureHotelRoom::STATUS_INACTIVE])
            ->sum('dhr.available_places');

        $this->available_capacity = max(0, $total);
        if ($save) {
            $this->save();
        }

        return (int) $this->available_capacity;
    }

    public function groupDealParticipants()
    {
        return $this->hasMany(GroupDealParticipant::class);
    }

    public function confirmedParticipantsCount(): int
    {
        return $this->groupDealParticipants()->where('status', GroupDealParticipant::STATUS_CONFIRMED)->count();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_OPEN => 'Ouvert',
            self::STATUS_LIMITED => 'Limité',
            self::STATUS_FULL => 'Complet',
            self::STATUS_CLOSED => 'Fermé',
            self::STATUS_CANCELED, self::STATUS_CANCELLED => 'Annulé',
            default => $this->status,
        };
    }
}
