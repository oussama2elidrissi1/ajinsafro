<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    public const TYPE_HEAD_OFFICE = 'head_office';
    public const TYPE_BRANCH = 'branch';
    public const AGENCY_TYPE_INTERNAL = 'internal';
    public const AGENCY_TYPE_PARTNER = 'partner';
    public const AGENCY_TYPE_FRANCHISE = 'franchise';
    public const AGENCY_TYPE_EXTERNAL = 'external';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'name',
        'code',
        'type',
        'agency_type',
        'city',
        'country',
        'address',
        'phone',
        'email',
        'logo_path',
        'default_commission_rate',
        'currency',
        'business_hours',
        'internal_notes',
        'documents',
        'manager_user_id',
        'status',
        'is_active',
        'archived_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_commission_rate' => 'decimal:2',
        'documents' => 'array',
        'archived_at' => 'datetime',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'branch_id');
    }

    public function agencyEmployees(): HasMany
    {
        return $this->hasMany(AgencyEmployee::class, 'branch_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'branch_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'branch_id');
    }

    public function chatChannels(): HasMany
    {
        return $this->hasMany(ChatChannel::class, 'branch_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('status', self::STATUS_ACTIVE)
            ->whereNull('archived_at');
    }

    public function scopeHeadOffice(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_HEAD_OFFICE);
    }

    public function scopeBranches(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_BRANCH);
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name . ($this->city ? ' (' . $this->city . ')' : '');
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }

    public static function agencyTypeLabels(): array
    {
        return [
            self::AGENCY_TYPE_INTERNAL => 'Agence interne',
            self::AGENCY_TYPE_PARTNER => 'Partenaire',
            self::AGENCY_TYPE_FRANCHISE => 'Franchise',
            self::AGENCY_TYPE_EXTERNAL => 'Externe',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_SUSPENDED => 'Suspendue',
        ];
    }
}
