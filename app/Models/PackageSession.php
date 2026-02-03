<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PackageSession extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'voyage_id',
        'pax_adults',
        'pax_children',
        'pax_infants',
        'currency',
        'state_json',
        'price_snapshot_json',
        'expires_at',
    ];

    protected $casts = [
        'pax_adults' => 'integer',
        'pax_children' => 'integer',
        'pax_infants' => 'integer',
        'state_json' => 'array',
        'price_snapshot_json' => 'array',
        'expires_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function (PackageSession $session) {
            if (empty($session->id)) {
                $session->id = (string) Str::uuid();
            }
            
            // Default expiration: 24 hours
            if (is_null($session->expires_at)) {
                $session->expires_at = now()->addHours(24);
            }
        });
    }

    public function voyage()
    {
        return $this->belongsTo(Voyage::class);
    }

    public function checkoutTokens()
    {
        return $this->hasMany(CheckoutToken::class, 'session_id');
    }

    /**
     * Get total number of travelers.
     */
    public function getTotalPaxAttribute(): int
    {
        return $this->pax_adults + $this->pax_children + $this->pax_infants;
    }

    /**
     * Check if the session has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Extend the session expiration.
     */
    public function extend(int $hours = 24): void
    {
        $this->expires_at = now()->addHours($hours);
        $this->save();
    }

    /**
     * Get the state modifications.
     */
    public function getStateAttribute(): array
    {
        return $this->state_json ?? [
            'removed_items' => [],
            'added_items' => [],
            'modified_items' => [],
        ];
    }

    /**
     * Update the state with new modifications.
     */
    public function updateState(string $action, array $data): void
    {
        $state = $this->state;
        
        switch ($action) {
            case 'add':
                $state['added_items'][] = $data;
                break;
            case 'remove':
                $state['removed_items'][] = $data['item_id'];
                break;
            case 'modify':
                $state['modified_items'][$data['item_id']] = $data['new_option'];
                break;
        }
        
        $this->state_json = $state;
        $this->save();
    }

    /**
     * Clear all modifications.
     */
    public function resetState(): void
    {
        $this->state_json = [
            'removed_items' => [],
            'added_items' => [],
            'modified_items' => [],
        ];
        $this->save();
    }
}
