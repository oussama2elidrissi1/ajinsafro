<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
     * Commerciaux / agents rattachés à ce manager (même filtre agence que le portail).
     */
    public function directReports(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function partner(): HasOne
    {
        return $this->hasOne(Partner::class);
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

    /**
     * Whether the user can access the admin area (dashboard, reservations, etc.).
     * True if is_admin or has any of the Ajinsafro admin roles.
     */
    public function canAccessAdmin(): bool
    {
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
        ]);
    }
}
