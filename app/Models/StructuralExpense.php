<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Charge de structure de l'agence : loyer, salaires, energie, logiciels...
 *
 * Ces charges ne sont JAMAIS rattachees a un depart et n'entrent donc pas dans la marge
 * d'un projet de voyage. Elles sont soustraites uniquement au niveau du resultat de gestion.
 */
class StructuralExpense extends Model
{
    use SoftDeletes;

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

    public const CATEGORY_LABELS = [
        'loyer' => 'Loyer',
        'eau' => 'Eau',
        'electricite' => 'Electricite',
        'internet' => 'Internet',
        'telephone' => 'Telephone',
        'salaires' => 'Salaires',
        'cnss' => 'CNSS',
        'fournitures' => 'Fournitures',
        'vehicules' => 'Vehicules',
        'carburant' => 'Carburant',
        'logiciels' => 'Logiciels',
        'publicite' => 'Publicite generale',
        'comptable' => 'Comptable',
        'frais_bancaires' => 'Frais bancaires',
        'impots_taxes' => 'Impots et taxes',
        'entretien' => 'Entretien',
        'divers' => 'Divers',
    ];

    public const RECURRENCE_LABELS = [
        'mensuelle' => 'Mensuelle',
        'trimestrielle' => 'Trimestrielle',
        'annuelle' => 'Annuelle',
    ];

    /** Nombre de mois separant deux occurrences. */
    public const RECURRENCE_MONTHS = [
        'mensuelle' => 1,
        'trimestrielle' => 3,
        'annuelle' => 12,
    ];

    protected $fillable = [
        'branch_id',
        'supplier_id',
        'category',
        'label',
        'description',
        'amount',
        'paid_amount',
        'currency',
        'status',
        'expense_date',
        'due_date',
        'paid_at',
        'payment_method',
        'is_recurring',
        'recurrence',
        'recurrence_until',
        'recurrence_parent_id',
        'recurrence_period',
        'notes',
        'created_by',
        'updated_by',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'supplier_id' => 'integer',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'expense_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'date',
        'is_recurring' => 'boolean',
        'recurrence_until' => 'date',
        'recurrence_parent_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'validated_by' => 'integer',
        'validated_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(FinanceSupplier::class, 'supplier_id');
    }

    public function recurrenceParent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'recurrence_parent_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(self::class, 'recurrence_parent_id');
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

    /** Exclut les charges annulees : elles ne pesent dans aucun total. */
    public function scopeAccountable(Builder $query): Builder
    {
        // Colonne qualifiee : ce scope est utilise dans des requetes jointes (branches).
        return $query->where($query->qualifyColumn('status'), '!=', self::STATUS_CANCELLED);
    }

    public function scopeForPeriod(Builder $query, ?string $from, ?string $to): Builder
    {
        $column = $query->qualifyColumn('expense_date');

        return $query
            ->when($from, fn (Builder $q, string $value) => $q->whereDate($column, '>=', $value))
            ->when($to, fn (Builder $q, string $value) => $q->whereDate($column, '<=', $value));
    }

    public function getRemainingAmountAttribute(): float
    {
        return round(max(0, (float) $this->amount - (float) $this->paid_amount), 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? $this->category;
    }
}
