<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DepartureCharge extends Model
{
    use SoftDeletes;

    public const PAYMENT_METHODS = ['espece', 'cheque', 'ordre_virement', 'carte', 'en_ligne', 'autre'];

    public const PAYMENT_STATUSES = ['non_paye', 'partiel', 'paye'];

    public const STATUS_PLANNED = 'prevue';

    public const STATUS_COMMITTED = 'engagee';

    public const STATUS_PARTIALLY_PAID = 'partiellement_payee';

    public const STATUS_PAID = 'payee';

    public const STATUS_CANCELLED = 'annulee';

    public const STATUS_LABELS = [
        self::STATUS_PLANNED => 'Prevue',
        self::STATUS_COMMITTED => 'Engagee',
        self::STATUS_PARTIALLY_PAID => 'Partiellement payee',
        self::STATUS_PAID => 'Payee',
        self::STATUS_CANCELLED => 'Annulee',
    ];

    /**
     * Correspondance vers l'enum historique `payment_status`, conservee pour que les pages
     * et exports « Finances departs » anterieurs au module continuent de fonctionner.
     */
    public const STATUS_TO_PAYMENT_STATUS = [
        self::STATUS_PLANNED => 'non_paye',
        self::STATUS_COMMITTED => 'non_paye',
        self::STATUS_PARTIALLY_PAID => 'partiel',
        self::STATUS_PAID => 'paye',
        self::STATUS_CANCELLED => 'non_paye',
    ];

    protected $fillable = [
        'departure_id',
        'voyage_id',
        'branch_id',
        'charge_type_id',
        'supplier_id',
        'title',
        'description',
        'supplier_name',
        'amount',
        'planned_amount',
        'paid_amount',
        'currency',
        'payment_method',
        'payment_status',
        'status',
        'charge_date',
        'due_date',
        'paid_at',
        'invoice_reference',
        'attachment',
        'notes',
        'created_by',
        'updated_by',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'departure_id' => 'integer',
        'voyage_id' => 'integer',
        'branch_id' => 'integer',
        'charge_type_id' => 'integer',
        'supplier_id' => 'integer',
        'amount' => 'decimal:2',
        'planned_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'charge_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'date',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'validated_by' => 'integer',
        'validated_at' => 'datetime',
    ];

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

    public function type(): BelongsTo
    {
        return $this->belongsTo(ChargeType::class, 'charge_type_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(FinanceSupplier::class, 'supplier_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(FinancialDocument::class, 'documentable');
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

    /** Exclut les charges annulees : elles ne pesent dans aucun total ni dans la marge. */
    public function scopeAccountable(Builder $query): Builder
    {
        // Colonne qualifiee : ce scope est utilise dans des requetes jointes (branches, charge_types).
        $column = $query->qualifyColumn('status');

        return $query->where(function (Builder $inner) use ($column) {
            $inner->whereNull($column)->orWhere($column, '!=', self::STATUS_CANCELLED);
        });
    }

    /** Montant prevu : retombe sur le montant reel pour les charges saisies avant le module. */
    public function getEffectivePlannedAmountAttribute(): float
    {
        $planned = $this->attributes['planned_amount'] ?? null;

        if ($planned !== null && $planned !== '') {
            return round((float) $planned, 2);
        }

        return round((float) ($this->attributes['amount'] ?? 0), 2);
    }

    public function getRemainingAmountAttribute(): float
    {
        return round(max(0, (float) $this->amount - (float) $this->paid_amount), 2);
    }

    public function getStatusLabelAttribute(): string
    {
        $status = (string) ($this->status ?: self::STATUS_COMMITTED);

        return self::STATUS_LABELS[$status] ?? $status;
    }

    /**
     * Aligne l'enum historique sur le statut du module afin que les deux representations
     * ne divergent jamais, quel que soit le point d'entree (ancienne page ou nouveau module).
     */
    public function syncLegacyPaymentStatus(): void
    {
        $status = (string) ($this->status ?: self::STATUS_COMMITTED);
        $this->payment_status = self::STATUS_TO_PAYMENT_STATUS[$status] ?? 'non_paye';
    }
}
