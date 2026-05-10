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
        'status',
        'can_login',
        'notes',
    ];

    protected $casts = [
        'can_login' => 'boolean',
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
            'Manager agence',
            'Chef commercial',
            'Agent commercial',
            'Agent réservation',
            'Agent visa',
            'Agent finance',
            'Agent support',
            'Guide',
            'Chauffeur',
            'Coordinateur terrain',
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
