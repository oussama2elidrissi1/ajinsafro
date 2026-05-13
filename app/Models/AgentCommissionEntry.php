<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentCommissionEntry extends Model
{
    public const STATUS_ESTIMATED = 'estimated';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PAYABLE = 'payable';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REVERSED = 'reversed';

    public const SOURCE_RESERVATION_CREATED = 'reservation_created';
    public const SOURCE_RESERVATION_CONFIRMED = 'reservation_confirmed';
    public const SOURCE_PAYMENT_RECEIVED = 'payment_received';
    public const SOURCE_CANCELLATION = 'cancellation';
    public const SOURCE_MANUAL_ADJUSTMENT = 'manual_adjustment';
    public const SOURCE_RESERVATION_REASSIGNED = 'reservation_reassigned';
    public const SOURCE_BACKFILL = 'backfill';

    protected $fillable = [
        'agent_id',
        'reservation_id',
        'voyage_id',
        'branch_id',
        'travel_date_id',
        'client_name',
        'reservation_total',
        'commission_base_amount',
        'commission_adult',
        'commission_child',
        'commission_baby',
        'commission_total',
        'reservation_status',
        'payment_status',
        'commission_status',
        'source',
        'calculated_at',
        'confirmed_at',
        'payable_at',
        'paid_at',
        'created_by',
        'updated_by',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'travel_date_id' => 'integer',
        'reservation_total' => 'decimal:2',
        'commission_base_amount' => 'decimal:2',
        'commission_adult' => 'decimal:2',
        'commission_child' => 'decimal:2',
        'commission_baby' => 'decimal:2',
        'commission_total' => 'decimal:2',
        'calculated_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'payable_at' => 'datetime',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class, 'voyage_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function travelDate(): BelongsTo
    {
        return $this->belongsTo(TravelDate::class, 'travel_date_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AgentCommissionLog::class, 'commission_entry_id')->orderByDesc('created_at')->orderByDesc('id');
    }

    public function scopeForMonth(Builder $query, ?string $month): Builder
    {
        if (! is_string($month) || ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $query;
        }

        return $query->whereBetween('calculated_at', [
            $month.'-01 00:00:00',
            date('Y-m-t 23:59:59', strtotime($month.'-01')),
        ]);
    }

    public function scopeForStatus(Builder $query, ?string $status): Builder
    {
        if (! is_string($status) || trim($status) === '') {
            return $query;
        }

        return $query->where('commission_status', trim($status));
    }

    public function statusLabelFr(): string
    {
        return match ((string) $this->commission_status) {
            self::STATUS_CONFIRMED => 'Confirmee',
            self::STATUS_PAYABLE => 'Payable',
            self::STATUS_PAID => 'Payee',
            self::STATUS_CANCELLED => 'Annulee',
            self::STATUS_REVERSED => 'Reversee',
            default => 'Estimee',
        };
    }

    public function sourceLabelFr(): string
    {
        return match ((string) $this->source) {
            self::SOURCE_RESERVATION_CONFIRMED => 'Confirmation reservation',
            self::SOURCE_PAYMENT_RECEIVED => 'Paiement recu',
            self::SOURCE_CANCELLATION => 'Annulation',
            self::SOURCE_MANUAL_ADJUSTMENT => 'Ajustement manuel',
            self::SOURCE_RESERVATION_REASSIGNED => 'Reaffectation',
            self::SOURCE_BACKFILL => 'Backfill',
            default => 'Creation reservation',
        };
    }

    public function departureDateLabel(): ?string
    {
        $departureDate = $this->reservation?->departure?->start_date ?? $this->travelDate?->date;

        return $departureDate ? $departureDate->format('d/m/Y') : null;
    }
}
