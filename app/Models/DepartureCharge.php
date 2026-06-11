<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DepartureCharge extends Model
{
    use SoftDeletes;

    public const PAYMENT_METHODS = ['espece', 'cheque', 'ordre_virement', 'carte', 'en_ligne', 'autre'];

    public const PAYMENT_STATUSES = ['non_paye', 'partiel', 'paye'];

    protected $fillable = [
        'departure_id',
        'voyage_id',
        'charge_type_id',
        'title',
        'description',
        'supplier_name',
        'amount',
        'currency',
        'payment_method',
        'payment_status',
        'paid_at',
        'attachment',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'departure_id' => 'integer',
        'voyage_id' => 'integer',
        'charge_type_id' => 'integer',
        'amount' => 'decimal:2',
        'paid_at' => 'date',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function departure(): BelongsTo
    {
        return $this->belongsTo(Departure::class);
    }

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ChargeType::class, 'charge_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
