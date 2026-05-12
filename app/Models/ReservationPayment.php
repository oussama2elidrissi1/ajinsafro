<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationPayment extends Model
{
    protected $table = 'reservation_payments';

    protected $fillable = [
        'reservation_id',
        'payment_date',
        'payment_method',
        'amount',
        'reference',
        'proof_file',
        'receipt_pdf_path',
        'note',
        'created_by',
    ];

    protected $casts = [
        'reservation_id' => 'integer',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'created_by' => 'integer',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
