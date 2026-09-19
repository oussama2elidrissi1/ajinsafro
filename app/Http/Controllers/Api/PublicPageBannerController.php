<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PageBanner;
use Illuminate\Http\JsonResponse;

/**
 * Banniere d'une page publique, lue par le front WordPress.
 * `data` vaut null tant que la banniere n'est pas active avec une image.
 */
class PublicPageBannerController extends Controller
{
    public function show(string $page): JsonResponse
    {
        if (! in_array($page, PageBanner::PAGES, true)) {
            return response()->json(['data' => null], 404);
        }

        $banner = PageBanner::forPage($page);

        if (! $banner || ! $banner->isDisplayable()) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'page_key' => $banner->page_key,
                'image_url' => $banner->imageUrl(),
                'alt_text' => (string) $banner->alt_text,
                'link_url' => $banner->link_url,
                'updated_at' => optional($banner->updated_at)->toIso8601String(),
            ],
        ]);
    }
}
