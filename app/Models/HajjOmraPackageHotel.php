<?php

namespace App\Models;

use App\Support\Locale\HasBilingualFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Hebergement d'une offre Hajj / Omra (bloc Makkah, Madinah, ou etape supplementaire).
 *
 * Remplace fonctionnellement les champs plats `makkah_hotel` / `madinah_hotel` du package,
 * qui restent en base pour l'API publique et sont resynchronises a chaque enregistrement.
 */
class HajjOmraPackageHotel extends Model
{
    use HasBilingualFields;

    public const CITY_MAKKAH = 'makkah';

    public const CITY_MADINAH = 'madinah';

    public const CITY_OTHER = 'other';

    public const CITY_LABELS = [
        self::CITY_MAKKAH => 'Makkah',
        self::CITY_MADINAH => 'Madinah',
        self::CITY_OTHER => 'Autre etape',
    ];

    public const CITY_LABELS_AR = [
        self::CITY_MAKKAH => 'مكة المكرمة',
        self::CITY_MADINAH => 'المدينة المنورة',
        self::CITY_OTHER => 'محطة أخرى',
    ];

    protected $table = 'hajj_omra_package_hotels';

    /** @var list<string> */
    protected array $bilingual = ['description'];

    protected $fillable = [
        'package_id',
        'city',
        'name',
        'stars',
        'haram_distance',
        'location',
        'nights',
        'meal_plan',
        'description',
        'description_ar',
        'image_path',
        'sort_order',
    ];

    protected $casts = [
        'package_id' => 'integer',
        'stars' => 'integer',
        'nights' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'image_url',
        'city_label',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(HajjOmraPackage::class, 'package_id');
    }

    public static function cityOptions(): array
    {
        return self::CITY_LABELS;
    }

    public function getCityLabelAttribute(): string
    {
        return self::CITY_LABELS[$this->city] ?? (string) $this->city;
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
