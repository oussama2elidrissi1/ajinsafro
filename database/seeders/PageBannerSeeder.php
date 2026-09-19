<?php

namespace Database\Seeders;

use App\Models\PageBanner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Banniere de la page Voyages.
 *
 * Le fichier de depart est attendu dans public/images/banners/voyages.png ;
 * il est copie sur le disque public (celui des televersements) pour que
 * toutes les bannieres se resolvent de la meme facon. Sans fichier, la ligne
 * est creee inactive : le front garde son bandeau par defaut.
 */
class PageBannerSeeder extends Seeder
{
    public function run(): void
    {
        $source = public_path('images/banners/voyages.png');
        $target = PageBanner::UPLOAD_DIR . '/voyages.png';

        $existing = PageBanner::forPage(PageBanner::PAGE_VOYAGES);

        // On ne remplace jamais une image choisie depuis l'admin.
        if ($existing && $existing->image_path && Storage::disk('public')->exists($existing->image_path)) {
            $this->command?->info('Banniere Voyages deja en place : conservee.');

            return;
        }

        $hasSource = is_file($source);
        if ($hasSource) {
            Storage::disk('public')->put($target, (string) file_get_contents($source));
        } else {
            $this->command?->warn('Aucune image dans public/images/banners/voyages.png : banniere creee inactive.');
        }

        PageBanner::query()->updateOrCreate(
            ['page_key' => PageBanner::PAGE_VOYAGES],
            [
                'image_path' => $hasSource ? $target : null,
                'alt_text' => $existing?->alt_text ?: 'Voyages, séjours et circuits Ajinsafro',
                'link_url' => $existing?->link_url,
                'is_active' => $hasSource,
            ]
        );
    }
}
