<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatChannel extends Model
{
    public const TYPE_DIRECT = 'direct';
    public const TYPE_BRANCH = 'branch';
    public const TYPE_GLOBAL = 'global';
    public const TYPE_RESERVATION = 'reservation';

    protected $fillable = [
        'type',
        'name',
        'branch_id',
        'reservation_id',
        'created_by',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_channel_members', 'channel_id', 'user_id')
            ->withPivot('role_in_channel', 'last_read_at')
            ->withTimestamps();
    }

    public function channelMembers(): HasMany
    {
        return $this->hasMany(ChatChannelMember::class, 'channel_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'channel_id')->orderBy('created_at');
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->name) {
            return $this->name;
        }
        if ($this->type === self::TYPE_BRANCH && $this->branch) {
            return $this->branch->display_name;
        }
        if ($this->type === self::TYPE_RESERVATION && $this->reservation) {
            return 'Réservation #' . $this->reservation->id;
        }
        if ($this->type === self::TYPE_GLOBAL) {
            return 'Réseau / Siège';
        }
        return 'Conversation';
    }
}
