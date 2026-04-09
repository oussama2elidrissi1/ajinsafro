<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class VoyageTheme extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (VoyageTheme $theme): void {
            $name = trim((string) ($theme->name ?? ''));
            if ($name === '') {
                return;
            }
            $slug = trim((string) ($theme->slug ?? ''));
            if ($slug !== '') {
                return;
            }
            $base = Str::slug($name);
            if ($base === '') {
                $base = 'theme';
            }
            $candidate = $base;
            $n = 0;
            while (static::query()
                ->where('slug', $candidate)
                ->when($theme->exists, fn ($q) => $q->where('id', '!=', (int) $theme->id))
                ->exists()) {
                $n++;
                $candidate = $base.'-'.$n;
            }
            $theme->slug = $candidate;
        });
    }

    public function voyages(): BelongsToMany
    {
        return $this->belongsToMany(Voyage::class, 'voyage_voyage_theme')->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
