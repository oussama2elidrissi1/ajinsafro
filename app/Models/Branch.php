<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    public const TYPE_HEAD_OFFICE = 'head_office';
    public const TYPE_BRANCH = 'branch';

    protected $fillable = [
        'name',
        'code',
        'type',
        'city',
        'country',
        'address',
        'phone',
        'email',
        'manager_user_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'branch_id');
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHeadOffice($query)
    {
        return $query->where('type', self::TYPE_HEAD_OFFICE);
    }

    public function scopeBranches($query)
    {
        return $query->where('type', self::TYPE_BRANCH);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name . ($this->city ? ' (' . $this->city . ')' : '');
    }
}
