<?php

namespace App\Console\Commands;

use App\Models\Voyage;
use App\Models\Wp\WpPost;
use Illuminate\Console\Command;

/**
 * Remet la liste « À compléter » en accord avec l'état réel des fiches historiques.
 *
 * `completion.missing` est un instantané calculé au moment du seed. Les traitements qui suivent
 * (`legacy:push-wp`, la saisie des agents dans l'admin) remplissent les trous sans le mettre à
 * jour : le catalogue continue d'afficher des manques déjà résolus. C'est ainsi que
 * `lien_wordpress` restait listé sur 105 fiches qui avaient pourtant leur tour WordPress.
 *
 * Seules les clés vérifiables en base sont recalculées. Les autres — `departs_vendables`,
 * `contenu_a_relire`, `extraction_partielle`, `url_publique` — relèvent d'une décision humaine ou
 * de la qualité de l'extraction : elles sont conservées telles quelles, dans leur ordre d'origine.
 *
 * Le recalcul est symétrique : un élément vidé par un agent réapparaît dans la liste.
 * `completion.status` n'est jamais touché — sortir une fiche de « À compléter » reste une décision
 * commerciale.
 *
 * Dry-run par défaut ; `--execute` applique.
 */
class LegacyRefreshMissingCommand extends Command
{
    protected $signature = 'legacy:refresh-missing
        {--execute : Écrit réellement (sinon simulation)}
        {--id=* : Ne traiter que ces identifiants historiques}';

    protected $description = 'Recalcule la liste « À compléter » des fiches historiques à partir des données réelles.';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $onlyIds = array_map('intval', (array) $this->option('id'));

        $voyages = Voyage::query()
            ->with(['images', 'programDays', 'themes'])
            ->orderBy('id')
            ->get()
            ->filter(fn (Voyage $v) => $v->isLegacyImport())
            ->filter(fn (Voyage $v) => $onlyIds === []
                || in_array((int) data_get($v->logistics_meta, 'legacy_import.legacy_id'), $onlyIds, true));

        if ($voyages->isEmpty()) {
            $this->info('Aucune fiche historique à examiner.');

            return self::SUCCESS;
        }

        // Un seul aller-retour vers WordPress plutôt qu'une requête par fiche.
        $wpIds = $voyages->pluck('wp_post_id')->filter()->map('intval')->unique()->values()->all();
        $tours = $wpIds === []
            ? []
            : WpPost::query()->whereIn('ID', $wpIds)->pluck('ID')->map('intval')->all();

        $retires = [];
        $ajoutes = [];
        $touchees = 0;

        foreach ($voyages as $voyage) {
            $avant = $voyage->legacyMissing();
            $apres = $this->recompute($voyage, $tours);

            if ($avant === $apres) {
                continue;
            }

            $touchees++;

            foreach (array_diff($avant, $apres) as $cle) {
                $retires[$cle] = ($retires[$cle] ?? 0) + 1;
            }
            foreach (array_diff($apres, $avant) as $cle) {
                $ajoutes[$cle] = ($ajoutes[$cle] ?? 0) + 1;
            }

            if ($execute) {
                $meta = is_array($voyage->logistics_meta) ? $voyage->logistics_meta : [];
                $meta['completion']['missing'] = $apres;
                $voyage->logistics_meta = $meta;
                $voyage->save();
            }
        }

        $this->line(sprintf('%d fiche(s) historique(s) examinée(s), %d à corriger.', $voyages->count(), $touchees));

        if ($retires !== []) {
            $this->newLine();
            $this->line('manques résolus, à retirer :');
            arsort($retires);
            foreach ($retires as $cle => $n) {
                $this->line(sprintf('  %-22s %d', $cle, $n));
            }
        }

        if ($ajoutes !== []) {
            $this->newLine();
            $this->line('manques réapparus, à ajouter :');
            arsort($ajoutes);
            foreach ($ajoutes as $cle => $n) {
                $this->line(sprintf('  %-22s %d', $cle, $n));
            }
        }

        $this->newLine();
        $this->info($execute
            ? sprintf('Liste « À compléter » mise à jour sur %d fiche(s).', $touchees)
            : 'Simulation. Relancez avec --execute pour appliquer.');

        return self::SUCCESS;
    }

    /**
     * Liste des manques recalculée : clés vérifiables réévaluées, les autres conservées en place.
     *
     * @param  list<int>  $tours  IDs des tours WordPress qui existent réellement
     * @return list<string>
     */
    private function recompute(Voyage $voyage, array $tours): array
    {
        $wpPostId = (int) $voyage->wp_post_id;

        // Une clé vraie ici signifie « toujours manquant ».
        $verifiables = [
            'lien_wordpress' => $wpPostId <= 0 || ! in_array($wpPostId, $tours, true),
            'images' => $voyage->images->isEmpty(),
            'programme_jours' => $voyage->programDays->isEmpty(),
            'themes' => $voyage->themes->isEmpty(),
            'duree' => trim((string) $voyage->duration_text) === '',
            'prix' => (int) $voyage->price_from <= 0,
            'destination' => trim((string) $voyage->destination) === '',
            'prestations_incluses' => (array) ($voyage->tours_include ?? []) === [],
        ];

        $out = [];

        // L'ordre d'origine est préservé : la liste reste lisible d'un passage à l'autre.
        foreach ($voyage->legacyMissing() as $cle) {
            if (array_key_exists($cle, $verifiables)) {
                if ($verifiables[$cle]) {
                    $out[] = $cle;
                }

                continue;
            }

            $out[] = $cle;
        }

        // Un manque réapparu prend place à la fin.
        foreach ($verifiables as $cle => $manquant) {
            if ($manquant && ! in_array($cle, $out, true)) {
                $out[] = $cle;
            }
        }

        return array_values(array_unique($out));
    }
}
