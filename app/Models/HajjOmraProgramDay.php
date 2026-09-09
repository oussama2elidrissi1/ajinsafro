<?php

namespace App\Models;

use App\Support\Locale\HasBilingualFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HajjOmraProgramDay extends Model
{
    use HasBilingualFields;

    /** @var list<string> */
    protected array $bilingual = ['title', 'description'];

    protected $fillable = [
        'package_id',
        'day_number',
        'title',
        'title_ar',
        'description',
        'description_ar',
        'city',
        'image_path',
        'sort_order',
    ];

    protected $casts = [
        'day_number' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'image_url',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(HajjOmraPackage::class, 'package_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        $path = (string) ($this->image_path ?? '');
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
