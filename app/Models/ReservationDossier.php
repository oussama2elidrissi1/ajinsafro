<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReservationDossier extends Model
{
    use SoftDeletes;

    protected $table = 'reservation_dossiers';

    protected $fillable = [
        'dossier_number',
        'client_id',
        'main_reservation_id',
        'total_base',
        'room_supplement_total',
        'extras_total',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'dossier_status',
        'created_by',
        'assigned_to',
        'confirmed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'client_id' => 'integer',
        'main_reservation_id' => 'integer',
        'total_base' => 'decimal:2',
        'room_supplement_total' => 'decimal:2',
        'extras_total' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'created_by' => 'integer',
        'assigned_to' => 'integer',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function mainReservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'main_reservation_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'reservation_dossier_id')->orderByDesc('created_at')->orderByDesc('id');
    }

    public function latestReservation(): HasOne
    {
        return $this->hasOne(Reservation::class, 'reservation_dossier_id')->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ReservationPayment::class, 'reservation_dossier_id')->orderByDesc('payment_date')->orderByDesc('id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ReservationDocument::class, 'reservation_dossier_id')->orderByDesc('created_at')->orderByDesc('id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ReservationHistory::class, 'reservation_dossier_id')->orderByDesc('created_at')->orderByDesc('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
