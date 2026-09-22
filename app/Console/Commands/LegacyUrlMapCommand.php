<?php

namespace App\Console\Commands;

use App\Models\Voyage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Exporte la table de correspondance des URLs du catalogue historique.
 *
 * Le chemin public ne change pas entre l'ancien site et la plateforme : seul le domaine change.
 * L'export sert donc à vérifier cette règle et à générer les 301 des variantes d'URL
 * (anciens tarifs dans le slug, pages `/team/buy.php?id=...`, `/onedeal.php?id=...`).
 */
class LegacyUrlMapCommand extends Command
{
    protected $signature = 'legacy:url-map
        {--domain= : Domaine cible (défaut : config app.public_domain)}
        {--out=legacy-url-map.csv : Fichier CSV écrit sur le disque local}';

    protected $description = 'Exporte la correspondance ancienne URL ajinsafro.ma -> URL actuelle des programmes importés.';

    public function handle(): int
    {
        $domain = trim((string) ($this->option('domain') ?: config('app.public_domain')));
        if ($domain === '') {
            $this->error('Aucun domaine cible : passez --domain ou renseignez PUBLIC_DOMAIN.');

            return self::FAILURE;
        }

        $rows = [];
        $canonical = 0;
        $toDefine = 0;

        Voyage::query()
            ->where('logistics_meta', 'like', '%"legacy_import"%')
            ->orderBy('id')
            ->each(function (Voyage $voyage) use (&$rows, &$canonical, &$toDefine, $domain): void {
                $legacy = $voyage->legacyImport();
                if ($legacy === null) {
                    return;
                }

                $legacyId = (int) ($legacy['legacy_id'] ?? 0);
                $targetPath = $voyage->legacyPath();

                if ($targetPath === null) {
                    // Programme sans URL SEO historique : la cible doit être arbitrée par l'agence.
                    $toDefine++;
                    $targetPath = '/voyages/'.$voyage->slug;
                } else {
                    $canonical++;
                }

                $paths = data_get($voyage->logistics_meta, 'seo.legacy_paths', []);
                foreach (is_array($paths) ? $paths : [] as $oldPath) {
                    $rows[] = [
                        $legacyId,
                        $voyage->name,
                        $oldPath,
                        $oldPath === $targetPath ? 'identique' : '301',
                        $targetPath,
                        'https://'.$domain.$targetPath,
                        $voyage->wp_post_id ? 'wp:'.$voyage->wp_post_id : 'non lié',
                        $voyage->isLegacyIncomplete() ? Voyage::LEGACY_COMPLETION_LABEL : 'complété',
                    ];
                }
            });

        if ($rows === []) {
            $this->warn('Aucun programme historique importé : lancez d\'abord db:seed --class=LegacyProgramsSeeder.');

            return self::SUCCESS;
        }

        $path = (string) $this->option('out');
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['legacy_id', 'programme', 'ancien_chemin', 'action', 'nouveau_chemin', 'nouvelle_url', 'wordpress', 'etat']);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        Storage::disk('local')->put($path, stream_get_contents($handle));
        fclose($handle);

        $this->info(sprintf(
            '%d URLs historiques exportées (%d chemins conservés à l\'identique, %d à arbitrer) -> %s',
            count($rows),
            $canonical,
            $toDefine,
            Storage::disk('local')->path($path)
        ));

        return self::SUCCESS;
    }
}
