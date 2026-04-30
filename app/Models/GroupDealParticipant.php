<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupDealParticipant extends Model
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_CANCELLED = 'cancelled';

    protected $fillable = [
        'group_deal_id',
        'departure_id',
        'client_id',
        'user_id',
        'reservation_id',
        'full_name',
        'phone',
        'email',
        'participants_count',
        'status',
        'selected_price',
        'payment_status',
        'joined_at',
    ];

    protected $casts = [
        'group_deal_id' => 'integer',
        'departure_id' => 'integer',
        'client_id' => 'integer',
        'user_id' => 'integer',
        'reservation_id' => 'integer',
        'participants_count' => 'integer',
        'selected_price' => 'decimal:2',
        'joined_at' => 'datetime',
    ];

    public function groupDeal(): BelongsTo
    {
        return $this->belongsTo(GroupDeal::class);
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(Departure::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_CONFIRMED => 'Confirmé',
            self::STATUS_PAID => 'Payé',
            self::STATUS_CANCELLED => 'Annulé',
            default => ucfirst((string) $this->status),
        };
    }
}
