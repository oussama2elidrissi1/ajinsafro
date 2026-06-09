<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CustomRequest extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_NEW = 'new';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_MISSING_INFO = 'missing_info';
    public const STATUS_MODIFICATION_REQUESTED = 'modification_requested';
    public const STATUS_QUOTE_PREPARED = 'quote_prepared';
    public const STATUS_QUOTE_SENT = 'quote_sent';
    public const STATUS_WAITING_CUSTOMER = 'waiting_customer';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUSED = 'refused';

    protected $fillable = [
        'request_number', 'created_by', 'assigned_to', 'client_id', 'customer_full_name', 'customer_phone',
        'customer_email', 'customer_city', 'customer_country', 'customer_identity', 'customer_type',
        'customer_notes', 'desired_destination', 'departure_city', 'desired_departure_date',
        'desired_return_date', 'desired_duration', 'travel_type', 'travelers_count', 'adults_count',
        'children_count', 'babies_count', 'approximate_budget', 'currency', 'desired_level',
        'desired_hotel', 'hotel_category', 'meal_plan', 'rooms_count', 'room_type',
        'separate_room_needed', 'accommodation_notes', 'flight_included', 'preferred_airline',
        'departure_airport', 'arrival_airport', 'baggage_included', 'airport_transfer_included',
        'local_transport', 'transport_notes', 'requested_services_details', 'estimated_price',
        'requested_deposit', 'paid_amount', 'remaining_amount', 'payment_method', 'payment_status',
        'status', 'priority', 'response_deadline', 'internal_notes', 'quote_sent_at',
        'confirmed_at', 'cancelled_at',
    ];

    protected $casts = [
        'desired_departure_date' => 'date',
        'desired_return_date' => 'date',
        'response_deadline' => 'date',
        'quote_sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'separate_room_needed' => 'boolean',
        'approximate_budget' => 'decimal:2',
        'estimated_price' => 'decimal:2',
        'requested_deposit' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $request): void {
            if (! $request->request_number) {
                $request->request_number = self::generateRequestNumber();
            }
            $request->remaining_amount = $request->calculateRemainingAmount(false);
        });
    }

    public static function generateRequestNumber(): string
    {
        $year = now()->format('Y');
        $prefix = 'DAC-'.$year.'-';
        $numbers = self::withTrashed()
            ->where('request_number', 'like', $prefix.'%')
            ->pluck('request_number')
            ->map(function ($number) use ($prefix): int {
                if (! is_string($number) || ! preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', $number, $matches)) {
                    return 0;
                }

                return (int) $matches[1];
            });

        $next = ((int) $numbers->max()) + 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function calculateRemainingAmount(bool $persist = true): float
    {
        $base = (float) ($this->latestQuote?->total_sale ?? $this->estimated_price ?? 0);
        $remaining = max(0, $base - (float) ($this->paid_amount ?? 0));

        if ($persist && $this->exists) {
            $this->forceFill(['remaining_amount' => $remaining])->save();
        }

        return $remaining;
    }

    public function changeStatus(string $status, ?int $userId, ?string $note = null): void
    {
        $oldStatus = $this->status;
        $this->forceFill([
            'status' => $status,
            'confirmed_at' => $status === self::STATUS_CONFIRMED ? now() : $this->confirmed_at,
            'cancelled_at' => $status === self::STATUS_CANCELLED ? now() : $this->cancelled_at,
            'quote_sent_at' => $status === self::STATUS_QUOTE_SENT ? now() : $this->quote_sent_at,
        ])->save();

        $this->statusLogs()->create([
            'user_id' => $userId,
            'old_status' => $oldStatus,
            'new_status' => $status,
            'note' => $note,
        ]);
    }

    public function canBeEditedBy(User $user): bool
    {
        if ($user->can('custom_requests.view_all') || $user->can('custom_requests.assign')) {
            return true;
        }

        return (int) $this->created_by === (int) $user->id
            && $user->can('custom_requests.edit')
            && in_array($this->status, [self::STATUS_DRAFT, self::STATUS_MODIFICATION_REQUESTED], true);
    }

    public function canBeQuotedBy(User $user): bool
    {
        return $user->can('custom_requests.quote')
            && (
                $user->can('custom_requests.view_all')
                || $this->assigned_to === null
                || (int) $this->assigned_to === (int) $user->id
            );
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('custom_requests.view_all') || $user->can('custom_requests.assign')) {
            return $query;
        }

        if ($user->can('custom_requests.quote')) {
            return $query->where(function (Builder $builder) use ($user): void {
                $builder->whereIn('status', [self::STATUS_NEW])
                    ->orWhere('assigned_to', $user->id);
            });
        }

        return $query->where('created_by', $user->id);
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_NEW => 'Nouvelle demande',
            self::STATUS_ASSIGNED => 'Assignée',
            self::STATUS_PROCESSING => 'En traitement',
            self::STATUS_MISSING_INFO => 'Informations manquantes',
            self::STATUS_MODIFICATION_REQUESTED => 'Modification demandée',
            self::STATUS_QUOTE_PREPARED => 'Devis préparé',
            self::STATUS_QUOTE_SENT => 'Devis envoyé',
            self::STATUS_WAITING_CUSTOMER => 'En attente client',
            self::STATUS_CONFIRMED => 'Confirmée',
            self::STATUS_CANCELLED => 'Annulée',
            self::STATUS_REFUSED => 'Refusée',
        ];
    }

    public static function priorityOptions(): array
    {
        return ['normal' => 'Normale', 'urgent' => 'Urgente', 'very_urgent' => 'Très urgente'];
    }

    public static function paymentStatusOptions(): array
    {
        return ['unpaid' => 'Non payé', 'deposit_paid' => 'Acompte payé', 'partially_paid' => 'Payé partiellement', 'fully_paid' => 'Payé totalement'];
    }

    public static function travelTypeOptions(): array
    {
        return [
            'organized_trip' => 'Voyage organisé', 'omra' => 'Omra', 'hajj' => 'Hajj',
            'hotel_stay' => 'Séjour hôtel', 'flight_ticket' => 'Billet avion', 'circuit' => 'Circuit',
            'visa' => 'Visa', 'transport' => 'Transport', 'other' => 'Autre',
        ];
    }

    public static function serviceOptions(): array
    {
        return [
            'visa' => 'Visa', 'travel_insurance' => 'Assurance voyage', 'tourist_guide' => 'Guide touristique',
            'excursions' => 'Excursions', 'activities' => 'Activités', 'transfers' => 'Transferts',
            'flight_ticket' => 'Billet avion', 'hotel' => 'Hôtel', 'car_rental' => 'Location voiture',
            'catering' => 'Restauration', 'group_assistance' => 'Assistance groupe', 'other' => 'Autre',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? Str::headline((string) $this->status);
    }

    public function priorityLabel(): string
    {
        return self::priorityOptions()[$this->priority] ?? Str::headline((string) $this->priority);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function services(): HasMany
    {
        return $this->hasMany(CustomRequestService::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomRequestDocument::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(CustomRequestQuote::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CustomRequestComment::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(CustomRequestStatusLog::class);
    }

    public function latestQuote(): HasOne
    {
        return $this->hasOne(CustomRequestQuote::class)->latestOfMany();
    }
}
