<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Justificatif rattache a un mouvement financier.
 *
 * Le mouvement peut etre un encaissement client (ReservationPayment), une charge de voyage
 * (DepartureCharge) ou une charge de structure (StructuralExpense). Les pieces deja portees
 * par ces tables (`reservation_payments.proof_file`, `departure_charges.attachment`) restent
 * valables : le centre de justificatifs les considere comme couvrantes sans exiger de resaisie.
 */
class FinancialDocument extends Model
{
    use SoftDeletes;

    public const STATUS_MISSING = 'manquant';

    public const STATUS_TO_CHECK = 'a_controler';

    public const STATUS_VALIDATED = 'valide';

    public const STATUS_REJECTED = 'rejete';

    public const STATUS_LABELS = [
        self::STATUS_MISSING => 'Manquant',
        self::STATUS_TO_CHECK => 'A controler',
        self::STATUS_VALIDATED => 'Valide',
        self::STATUS_REJECTED => 'Rejete',
    ];

    public const TYPE_LABELS = [
        'facture_fournisseur' => 'Facture fournisseur',
        'recu_client' => 'Recu client',
        'virement' => 'Virement',
        'preuve_tpe' => 'Preuve TPE',
        'facture_client' => 'Facture client',
        'contrat' => 'Contrat',
        'avoir' => 'Avoir',
        'autre' => 'Autre document',
    ];

    protected $fillable = [
        'document_type',
        'status',
        'reference',
        'document_date',
        'amount',
        'currency',
        'documentable_type',
        'documentable_id',
        'departure_id',
        'voyage_id',
        'branch_id',
        'supplier_id',
        'client_name',
        'file_path',
        'file_name',
        'file_mime',
        'file_size',
        'notes',
        'created_by',
        'updated_by',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'document_date' => 'date',
        'amount' => 'decimal:2',
        'documentable_id' => 'integer',
        'departure_id' => 'integer',
        'voyage_id' => 'integer',
        'branch_id' => 'integer',
        'supplier_id' => 'integer',
        'file_size' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'validated_by' => 'integer',
        'validated_at' => 'datetime',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(Departure::class);
    }

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(FinanceSupplier::class, 'supplier_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function scopeForMovement(Builder $query, Model $movement): Builder
    {
        return $query
            ->where('documentable_type', $movement->getMorphClass())
            ->where('documentable_id', $movement->getKey());
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->document_type] ?? $this->document_type;
    }
}
