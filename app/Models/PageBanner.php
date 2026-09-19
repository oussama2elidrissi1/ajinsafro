<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Banniere image d'une page publique du catalogue, pilotee depuis l'admin
 * et servie au front WordPress via l'API publique.
 */
class PageBanner extends Model
{
    public const PAGE_VOYAGES = 'voyages';
    public const PAGE_HEBERGEMENT = 'hebergement';
    public const PAGE_ACTIVITES = 'activites';
    public const PAGE_GROUP_DEALS = 'group-deals';
    public const PAGE_HAJJ_OMRA = 'hajj-omra';
    public const PAGE_FORMULE_ECONOMIQUE = 'formule-economique';

    public const PAGES = [
        self::PAGE_VOYAGES,
        self::PAGE_HEBERGEMENT,
        self::PAGE_ACTIVITES,
        self::PAGE_GROUP_DEALS,
        self::PAGE_HAJJ_OMRA,
        self::PAGE_FORMULE_ECONOMIQUE,
    ];

    /** Dossier du disque public qui recoit les fichiers televerses. */
    public const UPLOAD_DIR = 'banners';

    protected $fillable = [
        'page_key',
        'image_path',
        'alt_text',
        'link_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Libelle et chemin public de chaque page, pour l'ecran d'administration.
     *
     * @return array<string, array{label: string, path: string}>
     */
    public static function catalogue(): array
    {
        return [
            self::PAGE_VOYAGES => ['label' => 'Voyages', 'path' => '/voyages/'],
            self::PAGE_HEBERGEMENT => ['label' => 'Hébergement', 'path' => '/hebergement/'],
            self::PAGE_ACTIVITES => ['label' => 'Activités', 'path' => '/activites/'],
            self::PAGE_GROUP_DEALS => ['label' => 'Group Deals', 'path' => '/group-deals/'],
            self::PAGE_HAJJ_OMRA => ['label' => 'Hajj & Omra', 'path' => '/hajj-omra/'],
            self::PAGE_FORMULE_ECONOMIQUE => ['label' => 'Formule économique', 'path' => '/formule-economique/'],
        ];
    }

    public static function isKnownPage(string $pageKey): bool
    {
        return in_array($pageKey, self::PAGES, true);
    }

    public static function forPage(string $pageKey): ?self
    {
        return static::query()->where('page_key', $pageKey)->first();
    }

    /**
     * La banniere ne s'affiche que si elle est activee ET qu'un fichier existe.
     */
    public function isDisplayable(): bool
    {
        return $this->is_active && $this->image_path !== null && $this->imageUrl() !== null;
    }

    /**
     * URL publique absolue de l'image, ou null si le fichier manque.
     */
    public function imageUrl(): ?string
    {
        $path = trim((string) $this->image_path, '/');
        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $url = Storage::disk('public')->url($path);
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim((string) config('app.admin_url', config('app.url')), '/') . '/' . ltrim($url, '/');
    }

    /**
     * Cle du transient WordPress qui met cette banniere en cache.
     */
    public function cacheKey(): string
    {
        return static::cacheKeyFor($this->page_key);
    }

    public static function cacheKeyFor(string $pageKey): string
    {
        return 'ajth_page_banner_' . $pageKey . '_v1';
    }
}
