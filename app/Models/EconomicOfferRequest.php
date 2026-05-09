<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EconomicOfferRequest extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_CONTACTED,
        self::STATUS_CONFIRMED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'offer_id',
        'departure_id',
        'offer_title',
        'selected_departure_date',
        'full_name',
        'phone',
        'email',
        'adults',
        'children',
        'message',
        'status',
        'internal_notes',
        'responsible_agent',
        'source',
        'contacted_at',
        'confirmed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'selected_departure_date' => 'date',
        'adults' => 'integer',
        'children' => 'integer',
        'contacted_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $appends = [
        'status_label',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(EconomicOffer::class, 'offer_id');
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(EconomicOfferDeparture::class, 'departure_id');
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_NEW => 'Nouvelle',
            self::STATUS_CONTACTED => 'Contacte',
            self::STATUS_CONFIRMED => 'Confirmee',
            self::STATUS_CANCELLED => 'Annulee',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusOptions()[$this->status] ?? ucfirst((string) $this->status);
    }
}
