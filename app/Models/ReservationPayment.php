<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ReservationPayment extends Model
{
    protected $table = 'reservation_payments';

    protected $fillable = [
        'reservation_dossier_id',
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
        'reservation_dossier_id' => 'integer',
        'reservation_id' => 'integer',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'created_by' => 'integer',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(ReservationDossier::class, 'reservation_dossier_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Justificatifs du module Finance & Controle rattaches a cet encaissement.
     *
     * Complementaire de `proof_file` deja porte par la table : le centre de justificatifs
     * considere l'un ou l'autre comme couvrant, sans imposer de resaisie.
     */
    public function financialDocuments(): MorphMany
    {
        return $this->morphMany(FinancialDocument::class, 'documentable');
    }
}
