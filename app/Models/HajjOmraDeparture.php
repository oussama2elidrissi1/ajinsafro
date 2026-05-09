<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HajjOmraDeparture extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_FULL = 'full';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_EXPIRED,
        self::STATUS_FULL,
    ];

    protected $fillable = [
        'package_id',
        'departure_date',
        'return_date',
        'status',
        'available_places',
        'reserved_places',
        'price_from',
        'internal_notes',
        'sort_order',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
        'available_places' => 'integer',
        'reserved_places' => 'integer',
        'price_from' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'remaining_places',
        'status_label',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(HajjOmraPackage::class, 'package_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public static function statusOptions(): array
    {
        return HajjOmraPackage::statusOptions();
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
