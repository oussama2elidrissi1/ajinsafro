<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departure extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_FULL = 'full';
    public const STATUS_CANCELED = 'canceled';

    protected $fillable = ['voyage_id', 'start_date', 'status'];

    protected $casts = [
        'start_date' => 'date',
    ];

    public function voyage()
    {
        return $this->belongsTo(Voyage::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'Ouvert',
            self::STATUS_FULL => 'Complet',
            self::STATUS_CANCELED => 'Annulé',
            default => $this->status,
        };
    }
}
