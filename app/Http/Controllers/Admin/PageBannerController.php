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
 * Reglage « Bannieres des pages » : une image, un texte alternatif, un lien
 * et un interrupteur par page publique du catalogue, sur un seul ecran.
 */
class PageBannerController extends Controller
{
    private const MAX_UPLOAD_KB = 3072;

    public function index(): View
    {
        $existing = PageBanner::query()->whereIn('page_key', PageBanner::PAGES)->get()->keyBy('page_key');

        $banners = [];
        foreach (PageBanner::catalogue() as $pageKey => $page) {
            /** @var PageBanner $banner */
            $banner = $existing->get($pageKey) ?? new PageBanner(['page_key' => $pageKey, 'is_active' => false]);

            $banners[] = [
                'key' => $pageKey,
                'label' => $page['label'],
                'path' => $page['path'],
                'banner' => $banner,
                'imageUrl' => $banner->exists ? $banner->imageUrl() : null,
            ];
        }

        return view('admin.settings.page-banners.index', [
            'banners' => $banners,
            'maxUploadMb' => (int) round(self::MAX_UPLOAD_KB / 1024),
            'publicBase' => rtrim((string) config('app.public_url', 'https://ajinsafro.net'), '/'),
        ]);
    }

    public function redirectToPage(string $page): RedirectResponse
    {
        abort_unless(PageBanner::isKnownPage($page), 404);

        return redirect()->to(route('admin.settings.page-banners.index') . '#banner-' . $page);
    }

    public function update(Request $request, string $page): RedirectResponse
    {
        abort_unless(PageBanner::isKnownPage($page), 404);

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

        $banner = PageBanner::forPage($page) ?? new PageBanner(['page_key' => $page]);
        $label = PageBanner::catalogue()[$page]['label'];

        if ($request->hasFile('image_file')) {
            $previous = $banner->image_path;
            $stored = $request->file('image_file')->store(PageBanner::UPLOAD_DIR . '/' . $page, 'public');

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

        $notice = 'Bannière « ' . $label . ' » enregistrée.';
        if ($request->boolean('is_active') && $banner->image_path === null) {
            $notice = 'Bannière « ' . $label . ' » enregistrée. Ajoutez une image pour pouvoir l’activer.';
        }

        // Fragment ajoute a la main : passe en parametre, route() en ferait une query string.
        return redirect()
            ->to(route('admin.settings.page-banners.index') . '#banner-' . $page)
            ->with('success', $notice);
    }

    /**
     * Supprimer l'image : la banniere repasse inactive, le front reprend
     * son bandeau par defaut.
     */
    public function destroyImage(string $page): RedirectResponse
    {
        abort_unless(PageBanner::isKnownPage($page), 404);

        $banner = PageBanner::forPage($page);
        $label = PageBanner::catalogue()[$page]['label'];

        if ($banner && $banner->image_path !== null) {
            $this->forgetFile($banner->image_path);
            $banner->image_path = null;
            $banner->is_active = false;
            $banner->save();

            WpCatalogCacheInvalidator::invalidate([$banner->cacheKey()]);
        }

        // Fragment ajoute a la main : passe en parametre, route() en ferait une query string.
        return redirect()
            ->to(route('admin.settings.page-banners.index') . '#banner-' . $page)
            ->with('success', 'Image supprimée. La page « ' . $label . ' » affiche de nouveau son bandeau par défaut.');
    }

    private function forgetFile(?string $path): void
    {
        $path = trim((string) $path, '/');
        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
