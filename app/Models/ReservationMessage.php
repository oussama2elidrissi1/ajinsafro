<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReservationMessage extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_TRASH = 'trash';

    protected $table = 'reservation_messages';

    protected $fillable = [
        'from_branch_id',
        'subject',
        'body',
        'status',
        'is_important',
        'label_id',
    ];

    protected $casts = [
        'is_important' => 'boolean',
    ];

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function label(): BelongsTo
    {
        return $this->belongsTo(MessageLabel::class, 'label_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class, 'message_id');
    }

    public function stars(): HasMany
    {
        return $this->hasMany(MessageStar::class, 'message_id');
    }

    public function isReadBy(User $user): bool
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }

    public function isStarredBy(User $user): bool
    {
        return $this->stars()->where('user_id', $user->id)->exists();
    }
}
