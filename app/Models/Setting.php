<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /**
     * Default values for front/homepage keys (used when no value in DB).
     */
    protected static array $defaults = [
        'brand_name' => 'Ajinsafro.ma',
        'brand_logo' => null,
        'topbar_phone' => '(000) 999 - 656 - 888',
        'topbar_email' => 'contact@ajinsafro.ma',
        'social_facebook' => null,
        'social_twitter' => null,
        'social_instagram' => null,
        'social_youtube' => null,
        'hero_type' => 'image',
        'hero_image' => null,
        'hero_video' => null,
        'hero_overlay_opacity' => '0.45',
        'hero_title' => 'Let the journey begin',
        'hero_subtitle' => 'Get the best prices on 2,000,000+ properties, worldwide',
    ];

    /**
     * Get a setting value by key.
     */
    public static function getValue(string $key, $default = null): mixed
    {
        $default = $default ?? (self::$defaults[$key] ?? null);
        $row = self::query()->where('key', $key)->first();
        return $row !== null ? $row->value : $default;
    }

    /**
     * Set a setting value.
     */
    public static function setValue(string $key, $value): void
    {
        self::query()->updateOrInsert(
            ['key' => $key],
            ['value' => $value === null ? null : (string) $value, 'updated_at' => now()]
        );
    }

    /**
     * Get the public URL for a stored file path (e.g. front/brand/logo.png).
     */
    public static function storageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        return Storage::disk('public')->url($path);
    }
}
