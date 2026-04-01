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

    public const STATUS_EN_COURS = 'EN_COURS';

    public const STATUS_VALIDEE = 'VALIDEE';

    public const STATUS_ANNULEE = 'ANNULEE';

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
        'updated_by',
        'tour_id',
        'wp_tour_post_id',
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
        'wp_tour_post_id' => 'integer',
        'voyage_flight_id' => 'integer',
        'travel_date_id' => 'integer',
        'client_external_id' => 'integer',
        'partner_id' => 'integer',
        'branch_id' => 'integer',
        'sales_manager_id' => 'integer',
        'agent_id' => 'integer',
        'created_by' => 'integer',
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
        return $this->belongsTo(User::class, 'created_by');
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
}
