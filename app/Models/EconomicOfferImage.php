<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EconomicOfferImage extends Model
{
    protected $fillable = [
        'offer_id',
        'image_path',
        'alt_text',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'image_url',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(EconomicOffer::class, 'offer_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        $path = trim((string) ($this->image_path ?? ''));
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
