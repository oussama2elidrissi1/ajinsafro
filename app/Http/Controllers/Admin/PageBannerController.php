<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageBanner;
use App\Services\WpCatalogCacheInvalidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Reglage « Banniere page Voyages » : image, texte alternatif, lien, activation.
 */
class PageBannerController extends Controller
{
    private const MAX_UPLOAD_KB = 3072;

    public function edit(): View
    {
        $banner = PageBanner::forPage(PageBanner::PAGE_VOYAGES)
            ?? new PageBanner(['page_key' => PageBanner::PAGE_VOYAGES, 'is_active' => false]);

        return view('admin.settings.page-banners.voyages', [
            'banner' => $banner,
            'imageUrl' => $banner->exists ? $banner->imageUrl() : null,
            'maxUploadMb' => (int) round(self::MAX_UPLOAD_KB / 1024),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'image_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:' . self::MAX_UPLOAD_KB],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'image_file.mimes' => 'L’image doit être au format JPG, PNG ou WebP.',
            'image_file.max' => 'L’image ne doit pas dépasser 3 Mo.',
            'link_url.url' => 'L’URL au clic doit être une adresse complète (https://…).',
        ]);

        $banner = PageBanner::forPage(PageBanner::PAGE_VOYAGES)
            ?? new PageBanner(['page_key' => PageBanner::PAGE_VOYAGES]);

        if ($request->hasFile('image_file')) {
            $previous = $banner->image_path;
            $stored = $request->file('image_file')->store(PageBanner::UPLOAD_DIR . '/voyages', 'public');

            if (is_string($stored) && $stored !== '') {
                $banner->image_path = $stored;
                $this->forgetFile($previous);
            }
        }

        $banner->alt_text = trim((string) ($validated['alt_text'] ?? '')) ?: null;
        $banner->link_url = trim((string) ($validated['link_url'] ?? '')) ?: null;
        // Activer une banniere sans image n'aurait aucun effet visible : on refuse.
        $banner->is_active = $request->boolean('is_active') && $banner->image_path !== null;
        $banner->save();

        WpCatalogCacheInvalidator::invalidate([$banner->cacheKey()]);

        $notice = 'Bannière de la page Voyages enregistrée.';
        if ($request->boolean('is_active') && $banner->image_path === null) {
            $notice = 'Réglages enregistrés. Ajoutez une image pour pouvoir activer la bannière.';
        }

        return redirect()
            ->route('admin.settings.page-banners.voyages.edit')
            ->with('success', $notice);
    }

    /**
     * Supprimer l'image : la banniere repasse inactive, le front reprend
     * son bandeau par defaut.
     */
    public function destroyImage(): RedirectResponse
    {
        $banner = PageBanner::forPage(PageBanner::PAGE_VOYAGES);

        if ($banner && $banner->image_path !== null) {
            $this->forgetFile($banner->image_path);
            $banner->image_path = null;
            $banner->is_active = false;
            $banner->save();

            WpCatalogCacheInvalidator::invalidate([$banner->cacheKey()]);
        }

        return redirect()
            ->route('admin.settings.page-banners.voyages.edit')
            ->with('success', 'Image supprimée. La page Voyages affiche de nouveau son bandeau par défaut.');
    }

    private function forgetFile(?string $path): void
    {
        $path = trim((string) $path, '/');
        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
