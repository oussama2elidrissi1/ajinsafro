<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Reservation extends Model
{
    /**
     * Connexion Laravel principale (et non la connexion WordPress `wp`).
     */
    protected $connection = 'mysql';

    protected $table = 'reservations';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_OPTION = 'option';

    /**
     * Shared-room workflow (half-double).
     */
    public const STATUS_SHARED_ROOM_PENDING = 'shared_room_pending';

    public const STATUS_SHARED_ROOM_PAIRED = 'shared_room_paired';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REFUNDED = 'refunded';

    /** @deprecated Utiliser STATUS_PENDING — alias rétrocompatibilité */
    public const STATUS_EN_COURS = 'pending';

    /** @deprecated Utiliser STATUS_CONFIRMED */
    public const STATUS_VALIDEE = 'confirmed';

    /** @deprecated Utiliser STATUS_CANCELLED */
    public const STATUS_ANNULEE = 'cancelled';

    public const PAYMENT_CASHPLUS = 'CASHPLUS';

    public const PAYMENT_VIREMENT = 'VIREMENT';

    public const PAYMENT_ESPECE = 'ESPECE';

    public const DOSSIER_DRAFT = 'draft';

    public const DOSSIER_PENDING = 'pending';

    public const DOSSIER_CONFIRMED = 'confirmed';

    public const DOSSIER_CANCELLED = 'cancelled';

    public const DOSSIER_COMPLETED = 'completed';

    public const PAYMENT_STATUS_UNPAID = 'unpaid';

    public const PAYMENT_STATUS_NON_PAID = 'non_paid';

    public const PAYMENT_STATUS_DEPOSIT = 'deposit';

    public const PAYMENT_STATUS_PARTIAL = 'partial';

    public const PAYMENT_STATUS_PAID = 'paid';

    public const VISA_STATUS_NOT_REQUIRED = 'not_required';

    public const VISA_STATUS_PENDING = 'pending';

    public const VISA_STATUS_APPROVED = 'approved';

    public const VISA_STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'partner_id',
        'branch_id',
        'sales_manager_id',
        'agent_id',
        'assigned_to',
        'assigned_at',
        'assignment_priority',
        'assignment_note',
        'created_by',
        'created_by_user_id',
        'updated_by',
        'tour_id',
        'reservation_dossier_id',
        'voyage_id',
        'dossier_number',
        'departure_id',
        'wp_tour_post_id',
        'channel',
        'catalog_source_code',
        'voyage_flight_id',
        'prestation_type',
        'travel_date_id',
        'client_mode',
        'client_external_id',
        'client_first_name',
        'client_last_name',
        'client_email',
        'client_phone',
        'client_document_type',
        'client_document_number',
        'payment_type',
        'payment_receipt_path',
        'status',
        'dossier_status',
        'payment_status',
        'passengers_count',
        'notes',
        'total_base',
        'paid_amount',
        'extras_total',
        'total_amount',
        'remaining_amount',
        'visa_ok',
        'visa_notes',
        'visa_status',
        'visa_document_path',
        'confirmed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'tour_id' => 'integer',
        'reservation_dossier_id' => 'integer',
        'voyage_id' => 'integer',
        'departure_id' => 'integer',
        'wp_tour_post_id' => 'integer',
        'channel' => 'string',
        'voyage_flight_id' => 'integer',
        'travel_date_id' => 'integer',
        'client_external_id' => 'integer',
        'partner_id' => 'integer',
        'branch_id' => 'integer',
        'sales_manager_id' => 'integer',
        'agent_id' => 'integer',
        'assigned_to' => 'integer',
        'assigned_at' => 'datetime',
        'assignment_priority' => 'string',
        'created_by' => 'integer',
        'created_by_user_id' => 'integer',
        'updated_by' => 'integer',
        'passengers_count' => 'integer',
        'total_base' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'extras_total' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'visa_ok' => 'boolean',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function reservationRooms(): HasMany
    {
        return $this->hasMany(ReservationRoom::class);
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(ReservationPassenger::class);
    }

    public function extras(): HasMany
    {
        return $this->hasMany(ReservationExtra::class);
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(ReservationDossier::class, 'reservation_dossier_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ReservationPayment::class)->orderByDesc('payment_date')->orderByDesc('id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ReservationDocument::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ReservationHistory::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_external_id');
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Voyage::class, 'tour_id');
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Voyage::class, 'tour_id');
    }

    public function travelDate(): BelongsTo
    {
        return $this->belongsTo(TravelDate::class, 'travel_date_id');
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(Departure::class, 'departure_id');
    }

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class, 'voyage_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function partnerCommission(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PartnerCommission::class, 'reservation_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function salesManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_manager_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        if (! $branchId) {
            return $query;
        }

        return $query->where('branch_id', $branchId);
    }

    public function scopeAssignedTo(Builder $query, ?int $userId): Builder
    {
        if (! $userId) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($userId): void {
            $builder->where('agent_id', $userId)
                ->orWhere('sales_manager_id', $userId)
                ->orWhere('created_by', $userId)
                ->orWhere('created_by_user_id', $userId);
        });
    }

    public function scopeByCommercial(Builder $query, ?int $userId): Builder
    {
        if (! $userId) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($userId): void {
            $builder->where('sales_manager_id', $userId)
                ->orWhere('created_by', $userId)
                ->orWhere('created_by_user_id', $userId);
        });
    }

    public function scopeByAgent(Builder $query, ?int $userId): Builder
    {
        if (! $userId) {
            return $query;
        }

        return $query->where('agent_id', $userId);
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('branch_id')
            ->whereNull('agent_id')
            ->whereNull('sales_manager_id');
    }

    public function scopeForCurrentUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin() || $user->isSiegeAdmin() || $user->is_admin) {
            return $query;
        }

        if ($user->isManager() || $user->isBranchAdmin()) {
            return $user->branch_id
                ? $query->where('branch_id', $user->branch_id)
                : $this->scopeAssignedTo($query, $user->id);
        }

        if ($user->isChefCommercial()) {
            return $query->where(function (Builder $builder) use ($user): void {
                if ($user->branch_id) {
                    $builder->where('branch_id', $user->branch_id)
                        ->orWhere('sales_manager_id', $user->id);

                    return;
                }

                $builder->where('sales_manager_id', $user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhere('created_by_user_id', $user->id);
            });
        }

        if ($user->isCommercial() || $user->isAgent()) {
            return $query->where(function (Builder $builder) use ($user): void {
                $builder->where('agent_id', $user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhere('created_by_user_id', $user->id);
            });
        }

        if ($user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query->where(function (Builder $builder) use ($user): void {
            $builder->where('agent_id', $user->id)
                ->orWhere('sales_manager_id', $user->id)
                ->orWhere('created_by', $user->id)
                ->orWhere('created_by_user_id', $user->id);
        });
    }

    public function chatChannels(): HasMany
    {
        return $this->hasMany(ChatChannel::class, 'reservation_id');
    }

    /**
     * ID du tour WordPress (pour charger les hôtels/chambres via TourHotel).
     */
    public function getWpTourId(): ?int
    {
        $voyage = $this->tour;

        return $voyage && $voyage->wp_post_id ? (int) $voyage->wp_post_id : null;
    }

    /**
     * Prix total calculé (base + suppléments chambres).
     */
    public function getTotalPriceAttribute(): ?float
    {
        $totalAmount = $this->attributes['total_amount'] ?? null;
        if ($totalAmount !== null && $totalAmount !== '') {
            return (float) $totalAmount;
        }

        $base = $this->base_price !== null ? (float) $this->base_price : null;
        $supp = $this->room_supplement_total !== null ? (float) $this->room_supplement_total : null;
        $extras = $this->extras_total !== null ? (float) $this->extras_total : null;
        if ($base === null && $supp === null && $extras === null) {
            return null;
        }

        return ($base ?? 0) + ($supp ?? 0) + ($extras ?? 0);
    }

    public function getBasePriceAttribute(): ?float
    {
        $raw = $this->attributes['base_price'] ?? null;
        if ($raw !== null && $raw !== '') {
            return (float) $raw;
        }

        $paid = $this->attributes['paid_amount'] ?? null;

        return $paid !== null && $paid !== '' ? (float) $paid : null;
    }

    public function getRoomSupplementTotalAttribute(): ?float
    {
        $raw = $this->attributes['room_supplement_total'] ?? null;

        return $raw !== null && $raw !== '' ? (float) $raw : null;
    }

    public function getAgencyLabelAttribute(): ?string
    {
        return $this->branch?->name ?: $this->partner?->name;
    }

    public function getEffectivePaidAmountAttribute(): float
    {
        $paymentsTotal = $this->relationLoaded('payments')
            ? (float) $this->payments->sum('amount')
            : 0.0;

        if ($paymentsTotal > 0) {
            return round($paymentsTotal, 2);
        }

        return round((float) ($this->paid_amount ?? 0), 2);
    }

    public function getEffectiveExtrasTotalAttribute(): float
    {
        if ($this->extras_total !== null) {
            return round((float) $this->extras_total, 2);
        }

        if ($this->relationLoaded('extras')) {
            return round((float) $this->extras->sum(fn ($extra) => $extra->total_price ?? $extra->price ?? 0), 2);
        }

        return 0.0;
    }

    public function getEffectiveTotalBaseAttribute(): float
    {
        if ($this->total_base !== null) {
            return round((float) $this->total_base, 2);
        }

        return round((float) ($this->base_price ?? 0), 2);
    }

    public function getEffectiveTotalAmountAttribute(): float
    {
        if ($this->total_amount !== null) {
            return round((float) $this->total_amount, 2);
        }

        return round($this->effective_total_base + (float) ($this->room_supplement_total ?? 0) + $this->effective_extras_total, 2);
    }

    public function getEffectiveRemainingAmountAttribute(): float
    {
        if ($this->remaining_amount !== null) {
            return round((float) $this->remaining_amount, 2);
        }

        return round(max(0, $this->effective_total_amount - $this->effective_paid_amount), 2);
    }

    public function paymentStatusLabelFr(): string
    {
        $status = (string) ($this->payment_status ?: self::PAYMENT_STATUS_UNPAID);

        return match ($status) {
            self::PAYMENT_STATUS_DEPOSIT => 'Acompte',
            self::PAYMENT_STATUS_PARTIAL => 'Payé partiellement',
            self::PAYMENT_STATUS_PAID => 'Payé',
            default => 'Non payé',
        };
    }

    public function dossierStatusLabelFr(): string
    {
        $status = (string) ($this->dossier_status ?: self::DOSSIER_PENDING);

        return match ($status) {
            self::DOSSIER_DRAFT => 'Brouillon',
            self::DOSSIER_CONFIRMED => 'Confirmé',
            self::DOSSIER_CANCELLED => 'Annulé',
            self::DOSSIER_COMPLETED => 'Terminé',
            default => 'En attente',
        };
    }

    /**
     * Libellé statut pour affichage (alias legacy EN_COURS / VALIDEE / ANNULEE inclus).
     */
    public function statusLabelFr(): string
    {
        return match ($this->status) {
            self::STATUS_EN_COURS => 'En attente',
            self::STATUS_VALIDEE => 'Confirmée',
            self::STATUS_ANNULEE => 'Annulée',
            self::STATUS_PENDING => 'En attente',
            self::STATUS_SHARED_ROOM_PENDING => 'En attente de jumelage demi-double',
            self::STATUS_SHARED_ROOM_PAIRED => 'Demi-double jumelée',
            self::STATUS_CONFIRMED => 'Confirmée',
            self::STATUS_CANCELLED => 'Annulée',
            default => (string) $this->status,
        };
    }

    /**
     * Compte ayant saisi l’enregistrement : priorité created_by_user_id ({@see creator}), puis created_by ({@see createdBy}).
     */
    public function resolveAuditCreatorUser(): ?User
    {
        return $this->creator ?? $this->createdBy;
    }

    /**
     * Interlocuteur métier « qui porte la réservation » : agent affecté, sinon created_by, sinon created_by_user_id.
     */
    public function resolveOperationalActorUser(): ?User
    {
        if ((int) ($this->agent_id ?? 0) > 0 && $this->agent) {
            return $this->agent;
        }
        if ((int) ($this->created_by ?? 0) > 0 && $this->createdBy) {
            return $this->createdBy;
        }
        if ((int) ($this->created_by_user_id ?? 0) > 0 && $this->creator) {
            return $this->creator;
        }

        return null;
    }

    /**
     * Source métier utilisée pour {@see resolveOperationalActorUser()} (affichage admin).
     */
    public function operationalActorDataSourceLabel(): string
    {
        if ((int) ($this->agent_id ?? 0) > 0 && $this->agent) {
            return 'agent_id';
        }
        if ((int) ($this->created_by ?? 0) > 0 && $this->createdBy) {
            return 'created_by';
        }
        if ((int) ($this->created_by_user_id ?? 0) > 0 && $this->creator) {
            return 'created_by_user_id';
        }

        return '';
    }
}
