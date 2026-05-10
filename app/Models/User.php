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
        'manager_id',
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

        return asset('build/images/users/avatar-2.jpg');
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

    public function partner(): HasOne
    {
        return $this->hasOne(Partner::class);
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

    public function isComptable(): bool
    {
        return $this->hasRole('Comptable');
    }

    public function isPartner(): bool
    {
        return $this->hasRole('Partenaire');
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

        if ($this->is_admin) {
            return true;
        }

        return $this->hasRole([
            \App\Services\BranchScopeService::ROLE_SUPER_ADMIN,
            \App\Services\BranchScopeService::ROLE_SIEGE_ADMIN,
            \App\Services\BranchScopeService::ROLE_BRANCH_ADMIN,
            \App\Services\BranchScopeService::ROLE_CHEF_COMMERCIAL,
            \App\Services\BranchScopeService::ROLE_MANAGER,
            \App\Services\BranchScopeService::ROLE_COMMERCIAL,
            \App\Services\BranchScopeService::ROLE_AGENT,
            'Super Admin',
            'Admin Siège',
            'Chef Commercial',
            'Manager',
            'Agent',
        ]) || $this->can('dashboard.view');
    }
}
