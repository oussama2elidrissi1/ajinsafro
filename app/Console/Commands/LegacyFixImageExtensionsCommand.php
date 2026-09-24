<?php

namespace App\Console\Commands;

use App\Models\Voyage;
use App\Models\VoyageImage;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Aligne l'extension des photos rapatriées sur leur contenu réel.
 *
 * ajinsafro.ma annonce `Content-Type: image/jpeg` pour tous ses fichiers, y compris ses PNG et
 * ses WebP. Les premiers rapatriements ont fait confiance à cet en-tête : les fichiers portent
 * donc une extension qui ment sur leur contenu. Les navigateurs reniflent le contenu et les
 * affichent correctement, mais tout traitement qui choisit un décodeur d'après l'extension
 * échouerait.
 *
 * `LegacyImportImagesCommand` ne reproduit plus le défaut ; cette commande répare l'existant et
 * peut être relancée à tout moment pour contrôler l'intégrité de la médiathèque historique.
 *
 * Le renommage est reporté partout où le chemin est référencé : `voyage_images.path`,
 * `voyages.featured_image` et `logistics_meta.legacy_import.images_importees`.
 *
 * Dry-run par défaut ; `--execute` applique.
 */
class LegacyFixImageExtensionsCommand extends Command
{
    protected $signature = 'legacy:fix-image-extensions
        {--execute : Renomme réellement (sinon simulation)}';

    protected $description = 'Aligne l’extension des photos du catalogue historique sur leur contenu réel.';

    /** Les photos rapatriées vivent toutes sous ce préfixe du disque public. */
    private const PREFIX = 'voyages/legacy/';

    private const EXT_FOR_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $disk = Storage::disk('public');

        $images = VoyageImage::query()
            ->where('path', 'like', self::PREFIX.'%')
            ->orderBy('id')
            ->get();

        $this->line(sprintf('%d photo(s) rapatriée(s) sous %s', $images->count(), self::PREFIX));
        $this->newLine();

        /** @var array<string, string> $renames ancien chemin => nouveau chemin */
        $renames = [];
        $stats = ['conformes' => 0, 'absents' => 0, 'indecodables' => 0, 'collisions' => 0];
        $transitions = [];

        foreach ($images as $image) {
            $path = (string) $image->path;

            if (! $disk->exists($path)) {
                $stats['absents']++;
                $this->warn('  fichier absent : '.$path);

                continue;
            }

            $info = @getimagesizefromstring($disk->get($path));
            $mime = is_array($info) ? strtolower((string) ($info['mime'] ?? '')) : '';
            $attendue = self::EXT_FOR_MIME[$mime] ?? null;

            if ($attendue === null) {
                $stats['indecodables']++;
                $this->warn(sprintf('  contenu non reconnu : %s (%s)', $path, $mime ?: 'type inconnu'));

                continue;
            }

            $actuelle = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $transitions[$actuelle.' -> '.$attendue] = ($transitions[$actuelle.' -> '.$attendue] ?? 0) + 1;

            // `jpg` et `jpeg` désignent le même format : cet écart d'orthographe ne ment pas.
            if ($actuelle === $attendue || ($attendue === 'jpg' && $actuelle === 'jpeg')) {
                $stats['conformes']++;

                continue;
            }

            $nouveau = preg_replace('/\.[^.\/]+$/', '', $path).'.'.$attendue;

            if ($disk->exists($nouveau)) {
                $stats['collisions']++;
                $this->warn(sprintf('  cible déjà occupée : %s -> %s', $path, $nouveau));

                continue;
            }

            $renames[$path] = $nouveau;
        }

        ksort($transitions);
        foreach ($transitions as $transition => $n) {
            $this->line(sprintf('  %-16s %d', $transition, $n));
        }

        $this->newLine();
        $this->line(sprintf(
            'conformes: %d | à renommer: %d | absents: %d | non reconnus: %d | collisions: %d',
            $stats['conformes'],
            count($renames),
            $stats['absents'],
            $stats['indecodables'],
            $stats['collisions']
        ));

        if ($renames === []) {
            $this->info('Toutes les extensions sont fidèles au contenu.');

            return self::SUCCESS;
        }

        if (! $execute) {
            $this->newLine();
            foreach (array_slice($renames, 0, 5, true) as $ancien => $nouveau) {
                $this->line(sprintf('  %s', $ancien));
                $this->line(sprintf('    -> %s', $nouveau));
            }
            $this->newLine();
            $this->info('Simulation. Relancez avec --execute pour appliquer.');

            return self::SUCCESS;
        }

        $applied = $this->rename($disk, $renames);

        $this->newLine();
        $this->info(sprintf(
            'Réparation : %d fichier(s) renommé(s), %d couverture(s) et %d fiche(s) mises à jour, %d échec(s).',
            $applied['renommes'],
            $applied['couvertures'],
            $applied['metas'],
            $applied['echecs']
        ));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $renames
     * @return array{renommes: int, echecs: int, couvertures: int, metas: int}
     */
    private function rename(Filesystem $disk, array $renames): array
    {
        $out = ['renommes' => 0, 'echecs' => 0, 'couvertures' => 0, 'metas' => 0];

        // Un déplacement qui échoue sort de la table : aucune référence ne doit être réécrite
        // vers un chemin qui n'existe pas.
        foreach ($renames as $ancien => $nouveau) {
            try {
                $deplace = $disk->move($ancien, $nouveau);
            } catch (\Throwable $e) {
                $deplace = false;
                $this->warn(sprintf('  déplacement impossible : %s (%s)', $ancien, $e->getMessage()));
            }

            if (! $deplace) {
                $out['echecs']++;
                unset($renames[$ancien]);

                continue;
            }

            VoyageImage::query()->where('path', $ancien)->update(['path' => $nouveau]);
            $out['renommes']++;
        }

        foreach (Voyage::query()->where('featured_image', 'like', self::PREFIX.'%')->get() as $voyage) {
            if (isset($renames[$voyage->featured_image])) {
                $voyage->featured_image = $renames[$voyage->featured_image];
                $voyage->save();
                $out['couvertures']++;
            }
        }

        foreach (Voyage::query()->where('logistics_meta', 'like', '%"images_importees"%')->get() as $voyage) {
            $meta = is_array($voyage->logistics_meta) ? $voyage->logistics_meta : [];
            $done = data_get($meta, 'legacy_import.images_importees', []);

            if (! is_array($done) || $done === []) {
                continue;
            }

            $modifie = false;
            foreach ($done as $url => $chemin) {
                if (is_string($chemin) && isset($renames[$chemin])) {
                    $done[$url] = $renames[$chemin];
                    $modifie = true;
                }
            }

            if ($modifie) {
                $meta['legacy_import']['images_importees'] = $done;
                $voyage->logistics_meta = $meta;
                $voyage->save();
                $out['metas']++;
            }
        }

        return $out;
    }
}
