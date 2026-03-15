<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerCommission extends Model
{
    public const STATUS_CALCULATED = 'calculated';
    public const STATUS_PENDING = 'pending';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reservation_id',
        'partner_id',
        'rule_id',
        'reservation_total',
        'amount',
        'status',
        'validated_at',
        'paid_at',
        'payout_id',
    ];

    protected $casts = [
        'reservation_total' => 'decimal:2',
        'amount' => 'decimal:2',
        'validated_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(PartnerCommissionRule::class, 'rule_id');
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(PartnerPayout::class, 'payout_id');
    }
}
