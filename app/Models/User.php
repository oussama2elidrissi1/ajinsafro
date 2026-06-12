<?php

namespace App\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'address',
        'branch_id',
        'partner_id',
        'manager_id',
        'created_by',
        'job_title',
        'user_type',
        'is_admin',
        'is_active',
        'access_mode',
        'base_role',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    /**
     * Get the avatar URL (storage or default).
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        return $this->defaultAvatarDataUri();
    }

    private function defaultAvatarDataUri(): string
    {
        $svg = $this->usesFemaleDefaultAvatar()
            ? '<svg xmlns="http://www.w3.org/2000/svg" width="240" height="240" viewBox="0 0 240 240"><rect width="240" height="240" rx="120" fill="#fff0f5"/><path d="M72 107c0-39 21-64 48-64s48 25 48 64c0 33 17 47 28 58-18 10-45 14-76 14s-58-4-76-14c11-11 28-25 28-58z" fill="#d83f87"/><circle cx="120" cy="93" r="36" fill="#f06aa6"/><path d="M51 207c8-41 35-65 69-65s61 24 69 65" fill="#d83f87"/><circle cx="120" cy="120" r="112" fill="none" stroke="#ffffff" stroke-width="10"/></svg>'
            : '<svg xmlns="http://www.w3.org/2000/svg" width="240" height="240" viewBox="0 0 240 240"><rect width="240" height="240" rx="120" fill="#e8f4fb"/><circle cx="120" cy="91" r="42" fill="#0b6fae"/><path d="M50 204c7-43 35-68 70-68s63 25 70 68" fill="#0b6fae"/><circle cx="120" cy="120" r="112" fill="none" stroke="#ffffff" stroke-width="10"/></svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private function usesFemaleDefaultAvatar(): bool
    {
        $gender = strtolower(trim((string) ($this->gender ?? $this->sexe ?? $this->sex ?? '')));

        if (in_array($gender, ['female', 'femme', 'f'], true)) {
            return true;
        }

        if (in_array($gender, ['male', 'homme', 'm'], true)) {
            return false;
        }

        $firstName = strtolower(trim((string) strtok((string) $this->name, ' ')));

        return in_array($firstName, [
            'aicha',
            'asmaa',
            'fatima',
            'hajar',
            'ilham',
            'imane',
            'khadija',
            'meryem',
            'nadia',
            'oumaima',
            'oumima',
            'salma',
            'sara',
            'soukaina',
            'zineb',
        ], true);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Commerciaux / agents rattachés à ce manager.
     */
    public function directReports(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function ownedPartner(): HasOne
    {
        return $this->hasOne(Partner::class);
    }

    public function agentCommissionEntries(): HasMany
    {
        return $this->hasMany(AgentCommissionEntry::class, 'agent_id');
    }

    public function agencyEmployee(): HasOne
    {
        return $this->hasOne(AgencyEmployee::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        if (! $branchId) {
            return $query;
        }

        return $query->where('branch_id', $branchId);
    }

    public function scopeAgencyStaff(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder->whereNotNull('branch_id')
                ->orWhere('user_type', 'agency_employee')
                ->orWhere('access_mode', 'role');
        });
    }

    public function scopeCanLogin(Builder $query): Builder
    {
        return $query->active()->where(function (Builder $builder): void {
            $builder->whereNull('user_type')
                ->orWhere('user_type', '!=', 'client');
        });
    }

    public function chatChannels(): BelongsToMany
    {
        return $this->belongsToMany(ChatChannel::class, 'chat_channel_members', 'user_id', 'channel_id')
            ->withPivot('role_in_channel', 'last_read_at')
            ->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(\App\Services\BranchScopeService::ROLE_SUPER_ADMIN) || $this->hasRole('Super Admin') || $this->is_admin;
    }

    public function isSiegeAdmin(): bool
    {
        return $this->hasRole(\App\Services\BranchScopeService::ROLE_SIEGE_ADMIN) || $this->hasRole('Admin Siège');
    }

    public function isBranchAdmin(): bool
    {
        return $this->hasRole(\App\Services\BranchScopeService::ROLE_BRANCH_ADMIN);
    }

    public function isChefCommercial(): bool
    {
        return $this->hasRole(\App\Services\BranchScopeService::ROLE_CHEF_COMMERCIAL) || $this->hasRole('Chef Commercial');
    }

    public function isManager(): bool
    {
        return $this->hasRole(\App\Services\BranchScopeService::ROLE_MANAGER) || $this->hasRole('Manager');
    }

    public function isCommercial(): bool
    {
        return $this->hasRole(\App\Services\BranchScopeService::ROLE_COMMERCIAL);
    }

    public function isAgent(): bool
    {
        return $this->hasRole(\App\Services\BranchScopeService::ROLE_AGENT) || $this->hasRole('Agent');
    }

    public function isCommercialReservationsOnly(): bool
    {
        return $this->hasRole(\App\Services\BranchScopeService::ROLE_COMMERCIAL_RESERVATIONS_ONLY);
    }

    public function isComptable(): bool
    {
        return $this->hasRole('Comptable');
    }

    public function isPartner(): bool
    {
        if ($this->isPartnerAdmin() || $this->isPartnerAgent()) {
            return true;
        }

        // Primary signal: explicit role
        if ($this->hasRole('Partenaire') || $this->hasRole('Partner')) {
            return true;
        }

        // Secondary signals: user_type/base_role flags (legacy / imported accounts)
        if ((string) ($this->user_type ?? '') === 'partner' || (string) ($this->base_role ?? '') === 'partner') {
            return true;
        }

        // Fallback: presence of a Partner profile linked to this user.
        if ($this->relationLoaded('partner')) {
            return $this->partner !== null || ($this->relationLoaded('ownedPartner') && $this->ownedPartner !== null);
        }

        return ! empty($this->partner_id) || Partner::query()->where('user_id', $this->id)->exists();
    }

    public function isPartnerAdmin(): bool
    {
        return $this->hasRole('partner_admin') || $this->hasRole('Partenaire') || (string) ($this->base_role ?? '') === 'partner_admin';
    }

    public function isPartnerAgent(): bool
    {
        return $this->hasRole('partner_agent') || (string) ($this->base_role ?? '') === 'partner_agent';
    }

    public function canManagePartnerAgency(): bool
    {
        return $this->isPartnerAdmin();
    }

    public function isClientPortal(): bool
    {
        if ((string) ($this->user_type ?? '') === 'client') {
            return true;
        }

        if ((string) ($this->base_role ?? '') === 'client') {
            return true;
        }

        return Client::query()->where('user_id', $this->id)->exists();
    }

    /**
     * Whether the user can access the admin area.
     */
    public function canAccessAdmin(): bool
    {
        if ($this->isClientPortal()) {
            return false;
        }

        if ($this->isPartner()) {
            return false;
        }

        if ($this->hasRole(['Admin', 'Super Admin'])) {
            return true;
        }

        if ((string) ($this->access_mode ?? '') === 'custom') {
            return true;
        }

        return $this->can(\App\Support\AdminMenuPermissionRegistry::ADMIN_ACCESS_PERMISSION);
    }
}
