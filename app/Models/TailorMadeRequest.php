<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TailorMadeRequest extends Model
{
    protected $table = 'tailor_made_requests';

    public const STATUS_NEW = 'new';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'type',
        'source',
        'status',
        'voyage_id',
        'wp_post_id',
        'tour_title',
        'tour_url',
        'booking_url',
        'custom_departure_place',
        'custom_departure_date',
        'adults',
        'children',
        'travellers_total',
        'price_currency',
        'price_per_person',
        'price_total',
        'client_first_name',
        'client_last_name',
        'client_phone',
        'client_email',
        'message',
        'meta',
    ];

    protected $casts = [
        'custom_departure_date' => 'date',
        'meta' => 'array',
        'price_per_person' => 'decimal:2',
        'price_total' => 'decimal:2',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_NEW => 'Nouveau',
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PROCESSING => 'En cours',
            self::STATUS_DONE => 'Traite',
            self::STATUS_CANCELLED => 'Annule',
        ];
    }

    public function statusLabelFr(): string
    {
        $map = self::statusOptions();
        $status = (string) ($this->status ?: self::STATUS_NEW);
        return $map[$status] ?? Str::ucfirst(str_replace('_', ' ', $status));
    }

    public function getReferenceAttribute(): string
    {
        $id = (int) ($this->id ?? 0);
        return 'TMR-' . str_pad((string) max(0, $id), 6, '0', STR_PAD_LEFT);
    }
}
