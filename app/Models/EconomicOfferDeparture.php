<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EconomicOfferDeparture extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FULL = 'full';
    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_FULL,
        self::STATUS_EXPIRED,
    ];

    protected $fillable = [
        'offer_id',
        'departure_date',
        'return_date',
        'price_from',
        'total_places',
        'available_places',
        'reserved_places',
        'status',
        'internal_notes',
        'sort_order',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
        'price_from' => 'decimal:2',
        'total_places' => 'integer',
        'available_places' => 'integer',
        'reserved_places' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'remaining_places',
        'status_label',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(EconomicOffer::class, 'offer_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public static function statusOptions(): array
    {
        return EconomicOffer::statusOptions();
    }

    public function getRemainingPlacesAttribute(): int
    {
        return max(0, (int) $this->available_places - (int) $this->reserved_places);
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusOptions()[$this->status] ?? ucfirst((string) $this->status);
    }
}
