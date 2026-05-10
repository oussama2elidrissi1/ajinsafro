<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyEmployee extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'branch_id',
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'avatar_path',
        'position',
        'department',
        'employee_type',
        'contract_type',
        'hire_date',
        'exit_date',
        'fixed_salary',
        'salary_currency',
        'hr_status',
        'national_id',
        'address',
        'emergency_contact',
        'internal_hr_notes',
        'status',
        'can_login',
        'notes',
    ];

    protected $casts = [
        'can_login' => 'boolean',
        'hire_date' => 'date',
        'exit_date' => 'date',
        'fixed_salary' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        if (! $branchId) {
            return $query;
        }

        return $query->where('branch_id', $branchId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeWithLogin(Builder $query): Builder
    {
        return $query->where('can_login', true)->whereNotNull('user_id');
    }

    public function scopeByPosition(Builder $query, string $position): Builder
    {
        return $position !== '' ? $query->where('position', $position) : $query;
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar_path) {
            return asset('storage/' . $this->avatar_path);
        }

        return $this->user?->avatar_url;
    }

    public static function positionOptions(): array
    {
        return [
            'Manager point de vente',
            'Chef commercial',
            'Agent commercial',
            'Agent réservation',
            'Agent visa',
            'Agent finance',
            'Support client',
            'Guide',
            'Chauffeur',
            'Coordinateur terrain',
            'Administration centrale',
            'Developpement / IT',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => 'Actif',
            self::STATUS_INACTIVE => 'Inactif',
            self::STATUS_SUSPENDED => 'Suspendu',
        ];
    }
}
