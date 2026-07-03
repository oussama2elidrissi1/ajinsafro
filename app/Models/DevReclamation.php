<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DevReclamation extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'ouverte';
    public const STATUS_IN_PROGRESS = 'en_cours';
    public const STATUS_RESOLVED = 'traitee';

    protected $fillable = [
        'user_id',
        'handled_by',
        'subject',
        'message',
        'page_url',
        'attachment_path',
        'status',
        'dev_response',
        'handled_at',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN => 'Ouverte',
            self::STATUS_IN_PROGRESS => 'En cours',
            self::STATUS_RESOLVED => 'Traitee',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisibleToUser(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $email = strtolower(trim((string) ($user->email ?? '')));

        return $query->where(function (Builder $visibleQuery) use ($user, $email): void {
            $visibleQuery->where('user_id', $user->id);

            if ($email !== '') {
                $visibleQuery->orWhereHas('user', function (Builder $userQuery) use ($email): void {
                    $userQuery->whereRaw('LOWER(TRIM(email)) = ?', [$email]);
                });
            }
        });
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? Storage::disk('public')->url($this->attachment_path) : null;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }
}
