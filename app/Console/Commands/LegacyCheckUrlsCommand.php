<?php

namespace App\Console\Commands;

use App\Models\Voyage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Rejoue en HTTP chaque ancienne URL d'ajinsafro.ma contre un hôte et vérifie qu'elle répond
 * comme attendu : le chemin canonique en 200 (avec et sans barre finale), toute autre forme —
 * URL technique onedeal.php?id=, route alternative, autre préfixe — en 301 vers ce chemin.
 *
 * À lancer contre ajinsafro.net avant la bascule, puis contre www.ajinsafro.ma le jour même :
 * un 404 ici est une URL indexée perdue.
 *
 * Lecture seule ; ne suit pas les redirections.
 */
class LegacyCheckUrlsCommand extends Command
{
    protected $signature = 'legacy:check-urls
        {--base= : Hôte à tester, avec le schéma (défaut : app.public_url)}
        {--id=* : Ne tester que ces identifiants historiques}
        {--limit=0 : Nombre maximum de fiches testées}
        {--show-ok : Affiche aussi les URL conformes}';

    protected $description = 'Vérifie que chaque ancienne URL du catalogue historique répond 200 sur son chemin canonique ou 301 vers lui.';

    public function handle(): int
    {
        $base = rtrim((string) ($this->option('base') ?: config('app.public_url')), '/');
        $limit = max(0, (int) $this->option('limit'));
        $onlyIds = array_map('intval', (array) $this->option('id'));
        $showOk = (bool) $this->option('show-ok');

        $voyages = Voyage::query()
            ->orderBy('id')
            ->get()
            ->filter(fn (Voyage $v) => $v->isLegacyImport())
            ->filter(fn (Voyage $v) => $onlyIds === [] || in_array((int) data_get($v->logistics_meta, 'legacy_import.legacy_id'), $onlyIds, true));

        $stats = ['ok' => 0, 'ko' => 0, 'urls' => 0];
        $failures = [];
        $done = 0;

        foreach ($voyages as $voyage) {
            if ($limit > 0 && $done >= $limit) {
                break;
            }
            $done++;

            $legacyId = (int) data_get($voyage->logistics_meta, 'legacy_import.legacy_id');
            $canonical = $voyage->legacyPath();
            $paths = array_values(array_unique(array_filter((array) data_get($voyage->logistics_meta, 'seo.legacy_paths', []), 'is_string')));

            // Le chemin canonique doit aussi répondre avec une barre finale (ancienne tolérance).
            if ($canonical !== null) {
                $paths[] = rtrim($canonical, '/') . '/';
            }

            foreach ($paths as $path) {
                $stats['urls']++;
                [$status, $location] = $this->probe($base . $path);
                $verdict = $this->verdict($path, $status, $location, $canonical, $base);

                if ($verdict === null) {
                    $stats['ok']++;
                    if ($showOk) {
                        $this->line(sprintf('  <fg=green>OK</>  legacy %-4d %s → %s', $legacyId, $path, $status));
                    }

                    continue;
                }

                $stats['ko']++;
                $failures[] = sprintf('  <fg=red>KO</>  legacy %-4d %-80s HTTP %s %s — %s', $legacyId, Str::limit($path, 78), $status, $location ? '→ ' . $location : '', $verdict);
            }
        }

        foreach ($failures as $line) {
            $this->line($line);
        }

        $this->newLine();
        $this->line(sprintf('%d URL testées sur %d fiche(s) contre %s : %d conformes, %d en défaut.', $stats['urls'], $done, $base, $stats['ok'], $stats['ko']));

        return $stats['ko'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array{0:int, 1:string} statut HTTP et chemin de redirection (vide sinon)
     */
    private function probe(string $url): array
    {
        try {
            $response = Http::withOptions(['allow_redirects' => false])
                ->timeout(15)
                ->withHeaders(['User-Agent' => 'Ajinsafro-migration-check/1.0'])
                ->head($url);
            $status = $response->status();
            // Certains serveurs refusent HEAD : on confirme en GET sans suivre.
            if ($status === 405 || $status === 403) {
                $response = Http::withOptions(['allow_redirects' => false])->timeout(15)->get($url);
                $status = $response->status();
            }
            $location = (string) $response->header('Location');
        } catch (\Throwable $e) {
            return [0, ''];
        }

        $locationPath = $location !== '' ? (string) parse_url($location, PHP_URL_PATH) : '';
        if ($locationPath !== '' && parse_url($location, PHP_URL_QUERY)) {
            $locationPath .= '?' . parse_url($location, PHP_URL_QUERY);
        }

        return [$status, $locationPath];
    }

    /** Null si conforme, sinon la raison. */
    private function verdict(string $path, int $status, string $location, ?string $canonical, string $base): ?string
    {
        // Le chemin seul compte : `…-187?lang=` est le canonique avec un paramètre, servi tel quel.
        $pathOnly = (string) parse_url($path, PHP_URL_PATH);
        $isCanonical = $canonical !== null && rtrim($pathOnly, '/') === rtrim($canonical, '/');

        if ($isCanonical) {
            if ($status === 200) {
                return null;
            }
            // La forme avec barre finale peut être redirigée vers la forme canonique sans barre.
            if ($status === 301 && rtrim($location, '/') === rtrim($canonical, '/')) {
                return null;
            }

            return 'le chemin canonique doit répondre 200';
        }

        if ($status !== 301 && $status !== 302) {
            return 'attendu : 301 vers le chemin canonique';
        }
        if ($canonical !== null && rtrim($location, '/') !== rtrim($canonical, '/')) {
            return 'redirigé ailleurs que vers le chemin canonique ' . $canonical;
        }

        return null;
    }
}
