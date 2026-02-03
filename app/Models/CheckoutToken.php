<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CheckoutToken extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'token',
        'session_id',
        'voyage_id',
        'currency',
        'price_locked_until',
        'created_at',
    ];

    protected $casts = [
        'price_locked_until' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function (CheckoutToken $checkoutToken) {
            if (empty($checkoutToken->token)) {
                $checkoutToken->token = self::generateToken();
            }
            
            if (is_null($checkoutToken->created_at)) {
                $checkoutToken->created_at = now();
            }
            
            // Default price lock: 15 minutes
            if (is_null($checkoutToken->price_locked_until)) {
                $checkoutToken->price_locked_until = now()->addMinutes(15);
            }
        });
    }

    public function session()
    {
        return $this->belongsTo(PackageSession::class, 'session_id');
    }

    public function voyage()
    {
        return $this->belongsTo(Voyage::class);
    }

    /**
     * Generate a unique checkout token.
     */
    public static function generateToken(): string
    {
        do {
            $token = 'chk_' . Str::random(32);
        } while (self::where('token', $token)->exists());
        
        return $token;
    }

    /**
     * Check if the price lock has expired.
     */
    public function isPriceLockExpired(): bool
    {
        return $this->price_locked_until->isPast();
    }

    /**
     * Get remaining time for price lock in seconds.
     */
    public function getRemainingLockTimeAttribute(): int
    {
        if ($this->isPriceLockExpired()) {
            return 0;
        }
        
        return $this->price_locked_until->diffInSeconds(now());
    }

    /**
     * Extend the price lock.
     */
    public function extendLock(int $minutes = 15): void
    {
        $this->price_locked_until = now()->addMinutes($minutes);
        $this->save();
    }

    /**
     * Scope to filter valid (non-expired) tokens.
     */
    public function scopeValid($query)
    {
        return $query->where('price_locked_until', '>', now());
    }

    /**
     * Scope to filter expired tokens.
     */
    public function scopeExpired($query)
    {
        return $query->where('price_locked_until', '<=', now());
    }
}
