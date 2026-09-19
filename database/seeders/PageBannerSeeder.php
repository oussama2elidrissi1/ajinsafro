<?php

namespace Database\Seeders;

use App\Models\PageBanner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Bannieres des pages du catalogue.
 *
 * Pour chaque page, le fichier de depart est attendu dans
 * public/images/banners/{page}.png ; il est copie sur le disque public (celui
 * des televersements) pour que toutes les bannieres se resolvent de la meme
 * facon. Sans fichier, la ligne est creee inactive : le front garde son
 * bandeau par defaut. Une image choisie depuis l'admin n'est jamais remplacee.
 */
class PageBannerSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PageBanner::catalogue() as $pageKey => $page) {
            $source = public_path('images/banners/' . $pageKey . '.png');
            $target = PageBanner::UPLOAD_DIR . '/' . $pageKey . '.png';

            $existing = PageBanner::forPage($pageKey);

            if ($existing && $existing->image_path && Storage::disk('public')->exists($existing->image_path)) {
                $this->command?->info('Banniere « ' . $page['label'] . ' » deja en place : conservee.');

                continue;
            }

            $hasSource = is_file($source);
            if ($hasSource) {
                Storage::disk('public')->put($target, (string) file_get_contents($source));
            } else {
                $this->command?->warn('Aucune image dans public/images/banners/' . $pageKey . '.png : « ' . $page['label'] . ' » creee inactive.');
            }

            PageBanner::query()->updateOrCreate(
                ['page_key' => $pageKey],
                [
                    'image_path' => $hasSource ? $target : null,
                    'alt_text' => $existing?->alt_text ?: ($page['label'] . ' avec Ajinsafro'),
                    'link_url' => $existing?->link_url,
                    'is_active' => $hasSource,
                ]
            );
        }
    }
}
