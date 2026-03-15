<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        return $this->hasRole('Super Admin') || $this->is_admin;
    }

    public function isAdminSiege(): bool
    {
        return $this->hasRole('Admin Siège');
    }

    public function isChefCommercial(): bool
    {
        return $this->hasRole('Chef Commercial');
    }

    public function isAgent(): bool
    {
        return $this->hasRole('Agent');
    }

    public function isComptable(): bool
    {
        return $this->hasRole('Comptable');
    }

    public function isPartner(): bool
    {
        return $this->hasRole('Partenaire');
    }
}
