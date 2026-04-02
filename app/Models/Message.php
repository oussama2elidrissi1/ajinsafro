<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'recipient_id',
        'subject',
        'body',
        'preview',
        'read',
        'starred',
        'folder_sender',
        'folder_recipient',
        'read_at',
    ];

    protected $casts = [
        'read' => 'boolean',
        'starred' => 'boolean',
        'read_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Message $message): void {
            if (! $message->preview) {
                $message->preview = Str::limit(strip_tags((string) $message->body), 80);
            }
        });
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
