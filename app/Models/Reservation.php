<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public const VISA_STATUS_NOT_REQUIRED = 'not_required';

    public const VISA_STATUS_PENDING = 'pending';

    public const VISA_STATUS_APPROVED = 'approved';

    public const VISA_STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'partner_id',
        'branch_id',
        'sales_manager_id',
        'agent_id',
        'created_by',
        'created_by_user_id',
        'updated_by',
        'tour_id',
        'voyage_id',
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
        'passengers_count',
        'notes',
        'paid_amount',
        'visa_ok',
        'visa_notes',
        'visa_status',
        'visa_document_path',
    ];

    protected $casts = [
        'tour_id' => 'integer',
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
        'created_by' => 'integer',
        'created_by_user_id' => 'integer',
        'updated_by' => 'integer',
        'passengers_count' => 'integer',
        'paid_amount' => 'decimal:2',
        'visa_ok' => 'boolean',
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
        $base = $this->base_price !== null ? (float) $this->base_price : null;
        $supp = $this->room_supplement_total !== null ? (float) $this->room_supplement_total : null;
        if ($base === null && $supp === null) {
            return null;
        }

        return ($base ?? 0) + ($supp ?? 0);
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
