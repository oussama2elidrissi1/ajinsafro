<?php

namespace App\Console\Commands;

use App\Services\UploadedImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Re-encode en WebP redimensionne les images de la home deja televersees
 * (reglages home + header) et remplace leurs URL dans les options WordPress.
 * Les fichiers d'origine sont conserves.
 */
class OptimizeHomeSettingsImages extends Command
{
    protected $signature = 'home-settings:optimize-images
        {--dry-run : Liste les images concernees sans rien ecrire}
        {--max-width=1600 : Largeur maximale des images de sections}';

    protected $description = 'Redimensionne et convertit en WebP les images de la page d’accueil déjà en ligne, puis met à jour les réglages WordPress.';

    private const OPTIONS = ['aj_home_settings', 'aj_header_settings', 'aj_destinations_by_region'];

    private int $converted = 0;

    private int $savedBytes = 0;

    public function handle(UploadedImageOptimizer $optimizer): int
    {
        $disk = Storage::disk('public');
        $prefix = rtrim((string) $disk->url(''), '/') . '/';
        $dryRun = (bool) $this->option('dry-run');
        $maxWidth = max(200, (int) $this->option('max-width'));

        foreach (self::OPTIONS as $option) {
            $raw = DB::connection('wp')->table('options')->where('option_name', $option)->value('option_value');
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (! is_array($decoded)) {
                $this->line("<comment>{$option}</comment> : option absente ou vide, ignorée.");
                continue;
            }

            $changed = false;
            array_walk_recursive($decoded, function (&$value, $key) use ($prefix, $optimizer, $disk, $dryRun, $maxWidth, &$changed, $option) {
                if (! is_string($value) || ! str_starts_with($value, $prefix)) {
                    return;
                }

                $path = urldecode(substr($value, strlen($prefix)));
                if (preg_match('/\.(mp4|webm|svg|gif)$/i', $path)) {
                    return;
                }

                // Le hero et le logo gardent une largeur superieure (plein ecran / retina).
                $width = str_contains($path, '/hero/') ? 1920 : (str_contains($path, '/header/') ? 800 : $maxWidth);
                $before = $disk->exists($path) ? (int) $disk->size($path) : 0;

                if ($dryRun) {
                    $this->line(sprintf('  %s (%s Ko) ← %s.%s', $path, number_format($before / 1024, 0, ',', ' '), $option, $key));

                    return;
                }

                $newPath = $optimizer->optimizeStored($path, $width);
                if ($newPath === null) {
                    return;
                }

                $after = (int) $disk->size($newPath);
                $this->converted++;
                $this->savedBytes += max(0, $before - $after);
                $this->line(sprintf('  %s → %s (%s Ko → %s Ko)', $path, $newPath, number_format($before / 1024, 0, ',', ' '), number_format($after / 1024, 0, ',', ' ')));

                $value = $prefix . $newPath;
                $changed = true;
            });

            if ($changed && ! $dryRun) {
                DB::connection('wp')->table('options')->updateOrInsert(
                    ['option_name' => $option],
                    ['option_value' => json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'autoload' => 'no']
                );
                if ($option === 'aj_header_settings') {
                    // Le plugin WordPress met en cache le header tant que cet horodatage ne bouge pas.
                    DB::connection('wp')->table('options')->updateOrInsert(
                        ['option_name' => 'aj_header_settings_ts'],
                        ['option_value' => (string) now()->timestamp, 'autoload' => 'no']
                    );
                }
                $this->info("{$option} : réglages mis à jour.");
            }
        }

        $this->buildPosterVariants($optimizer, $disk, $prefix, $dryRun);

        if ($dryRun) {
            $this->info('Simulation terminée, rien n’a été écrit.');
        } else {
            $this->info(sprintf('%d image(s) converties, %s Mo économisés. Les fichiers d’origine sont conservés.', $this->converted, number_format($this->savedBytes / 1048576, 1, ',', ' ')));
        }

        return self::SUCCESS;
    }

    /**
     * Le poster du hero est l'element LCP : sans declinaisons, le mobile
     * recevrait l'image bureau entiere.
     */
    private function buildPosterVariants(UploadedImageOptimizer $optimizer, $disk, string $prefix, bool $dryRun): void
    {
        $raw = DB::connection('wp')->table('options')->where('option_name', 'aj_home_settings')->value('option_value');
        $settings = is_string($raw) ? json_decode($raw, true) : null;
        if (! is_array($settings)) {
            return;
        }

        $poster = (string) ($settings['hero']['poster_url'] ?? '');
        if (! str_starts_with($poster, $prefix)) {
            return;
        }
        if (! empty($settings['hero']['poster_variants'])) {
            $this->line('Poster du hero : déclinaisons déjà présentes.');

            return;
        }

        $path = urldecode(substr($poster, strlen($prefix)));
        if (! $disk->exists($path)) {
            $this->warn("Poster du hero introuvable sur le disque : {$path}");

            return;
        }
        if ($dryRun) {
            $this->line("  poster du hero : déclinaisons 768x900 / 1280x720 / bureau à générer depuis {$path}");

            return;
        }

        $variants = [];
        foreach ($optimizer->storeResponsiveSet($disk->path($path), dirname($path)) as $variant) {
            $variants[] = ['url' => $prefix . $variant['path'], 'width' => $variant['width'], 'height' => $variant['height']];
            $this->line(sprintf('  poster %dx%d → %s (%s Ko)', $variant['width'], $variant['height'], $variant['path'], number_format($disk->size($variant['path']) / 1024, 0, ',', ' ')));
        }
        if ($variants === []) {
            $this->warn('Poster du hero : aucune déclinaison générée (extension GD absente ?).');

            return;
        }

        $settings['hero']['poster_variants'] = $variants;
        DB::connection('wp')->table('options')->updateOrInsert(
            ['option_name' => 'aj_home_settings'],
            ['option_value' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'autoload' => 'no']
        );
        $this->info('Poster du hero : déclinaisons enregistrées.');
    }
}
