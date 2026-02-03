<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelDayItem extends Model
{
    use HasFactory;

    public const TYPES = [
        'flight' => 'Vol',
        'hotel_stay' => 'Hébergement',
        'transfer' => 'Transfert',
        'activity' => 'Activité',
        'meal' => 'Repas',
        'addon' => 'Option supplémentaire',
    ];

    protected $fillable = [
        'voyage_id',
        'day_number',
        'start_day',
        'end_day',
        'nights',
        'type',
        'title',
        'details',
        'included',
        'price_delta_per_person',
        'options_json',
        'meta_json',
        'sort_order',
    ];

    protected $casts = [
        'day_number' => 'integer',
        'start_day' => 'integer',
        'end_day' => 'integer',
        'nights' => 'integer',
        'included' => 'boolean',
        'price_delta_per_person' => 'integer',
        'sort_order' => 'integer',
        'options_json' => 'array',
        'meta_json' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function (TravelDayItem $item) {
            if (is_null($item->start_day)) {
                $item->start_day = $item->day_number;
            }
        });
    }

    public function voyage()
    {
        return $this->belongsTo(Voyage::class);
    }

    public function programDay()
    {
        return $this->belongsTo(TravelProgramDay::class, 'day_number', 'day_number')
            ->where('voyage_id', $this->voyage_id);
    }

    /**
     * Get the human-readable type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Check if this item spans multiple days.
     */
    public function isMultiDay(): bool
    {
        return !is_null($this->end_day) && $this->end_day > $this->start_day;
    }

    /**
     * Get the duration in days.
     */
    public function getDurationDaysAttribute(): int
    {
        if ($this->isMultiDay()) {
            return $this->end_day - $this->start_day + 1;
        }
        return 1;
    }

    /**
     * Format price delta for display.
     */
    public function getFormattedPriceDeltaAttribute(): string
    {
        if ($this->price_delta_per_person === 0) {
            return 'Inclus';
        }
        
        $amount = $this->price_delta_per_person / 100;
        $currency = $this->voyage->currency_symbol ?? 'DH';
        
        return ($this->price_delta_per_person > 0 ? '+' : '') . number_format($amount, 2) . ' ' . $currency;
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter included items.
     */
    public function scopeIncluded($query)
    {
        return $query->where('included', true);
    }

    /**
     * Scope to filter optional items.
     */
    public function scopeOptional($query)
    {
        return $query->where('included', false);
    }
}
