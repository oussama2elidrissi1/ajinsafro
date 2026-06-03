<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Partner extends Model
{
    protected $connection = 'mysql';

    public const STATUS_PENDING = 'pending';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';

    public const TYPE_AGENCE = 'agence';
    public const TYPE_COMMERCIAL_INDEPENDANT = 'commercial_independent';
    public const TYPE_POINT_VENTE = 'point_vente';
    public const TYPE_APPORTEUR_AFFAIRES = 'apporteur_affaires';
    public const TYPE_AGENCE_ETRANGER = 'agence_etranger';

    protected $fillable = [
        'user_id',
        'name',
        'raison_sociale',
        'nom_commercial',
        'nom_responsable',
        'responsable_name',
        'email',
        'telephone',
        'phone',
        'adresse',
        'address',
        'ville',
        'city',
        'code_postal',
        'pays',
        'partner_type',
        'ice',
        'if',
        'rc',
        'document_path',
        'logo_path',
        'rib_iban',
        'rib_bic',
        'payment_mode',
        'contract_path',
        'status',
        'wallet_balance',
        'created_by',
        'validated_at',
        'validated_by',
        'rejected_at',
        'rejected_reason',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
        'rejected_at' => 'datetime',
        'wallet_balance' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function validatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'partner_id');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(User::class, 'partner_id');
    }

    public function partnerAgents()
    {
        return $this->agents()->whereHas('roles', fn ($query) => $query->where('name', 'partner_agent'));
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(PartnerWalletTransaction::class, 'partner_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'partner_id');
    }

    public function commissionRules(): HasMany
    {
        return $this->hasMany(PartnerCommissionRule::class, 'partner_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(PartnerCommission::class, 'partner_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(PartnerPayout::class, 'partner_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PartnerDocument::class, 'partner_id');
    }

    /** Voyages que le partenaire a le droit de vendre. Vide = tous. */
    public function voyageAccess(): BelongsToMany
    {
        return $this->belongsToMany(Voyage::class, 'partner_voyage_access', 'partner_id', 'voyage_id')->withTimestamps();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function canAccessPartnerArea(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->nom_commercial ?: ($this->name ?: $this->raison_sociale);
    }

    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path) {
            return Storage::disk('public')->url($this->logo_path);
        }

        return asset('build/images/logo-dark.png');
    }

    public function getResponsibleNameAttribute(): ?string
    {
        return $this->responsable_name ?: $this->nom_responsable;
    }

    public function getPhoneNumberAttribute(): ?string
    {
        return $this->phone ?: $this->telephone;
    }

    public function getAddressLineAttribute(): ?string
    {
        return $this->address ?: $this->adresse;
    }

    public function getCityNameAttribute(): ?string
    {
        return $this->city ?: $this->ville;
    }

    public static function partnerTypeLabels(): array
    {
        return [
            self::TYPE_AGENCE => 'Agence de voyage',
            self::TYPE_COMMERCIAL_INDEPENDANT => 'Commercial indépendant',
            self::TYPE_POINT_VENTE => 'Point de vente affilié',
            self::TYPE_APPORTEUR_AFFAIRES => 'Apporteur d\'affaires',
            self::TYPE_AGENCE_ETRANGER => 'Agence à l\'étranger',
        ];
    }

    public function getPartnerTypeLabelAttribute(): ?string
    {
        return $this->partner_type ? (self::partnerTypeLabels()[$this->partner_type] ?? $this->partner_type) : null;
    }
}
