<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Referentiel fournisseur du module Finance & Controle.
 *
 * Volontairement leger : il sert uniquement de point de rattachement des charges
 * (voyage et structure) et des justificatifs. Il ne remplace ni `partners` (revendeurs)
 * ni les catalogues metier (hotels, compagnies aeriennes) qui restent la reference produit.
 */
class FinanceSupplier extends Model
{
    use SoftDeletes;

    public const TYPE_LABELS = [
        'hotel' => 'Hotel',
        'compagnie_aerienne' => 'Compagnie aerienne',
        'transporteur' => 'Transporteur',
        'guide' => 'Guide',
        'activite' => 'Activite',
        'restaurant' => 'Restaurant',
        'assurance' => 'Assurance',
        'visa' => 'Visa',
        'prestataire_structure' => 'Prestataire structure',
        'autre' => 'Autre',
    ];

    protected $fillable = [
        'name',
        'type',
        'contact_name',
        'email',
        'phone',
        'city',
        'country',
        'address',
        'tax_id',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function charges(): HasMany
    {
        return $this->hasMany(DepartureCharge::class, 'supplier_id');
    }

    public function structuralExpenses(): HasMany
    {
        return $this->hasMany(StructuralExpense::class, 'supplier_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(FinancialDocument::class, 'supplier_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? self::TYPE_LABELS['autre'];
    }
}
