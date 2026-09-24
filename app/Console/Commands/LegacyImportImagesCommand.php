<?php

namespace App\Console\Commands;

use App\Models\Voyage;
use App\Models\VoyageImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Rapatrie dans la médiathèque les photos des programmes importés du catalogue historique.
 *
 * Les URLs relevées pointent encore sur `ajinsafro.ma` : tant que les fichiers ne sont pas copiés,
 * les fiches perdront leurs visuels à la coupure du domaine. Le seeder les a donc listées dans
 * `logistics_meta.legacy_import.images_a_rapatrier` sans créer de ligne `voyage_images`.
 *
 * Cette commande télécharge chaque fichier sur le disque `public`, crée la galerie et pose la photo
 * de couverture. Elle doit tourner **sur le serveur** (c'est son stockage qui reçoit les fichiers)
 * et celui-ci doit joindre ajinsafro.ma.
 *
 * Dry-run par défaut ; `--execute` télécharge réellement.
 */
class LegacyImportImagesCommand extends Command
{
    protected $signature = 'legacy:import-images
        {--execute : Télécharge réellement (sinon simulation)}
        {--limit=0 : Nombre maximum de programmes traités}
        {--id=* : Ne traiter que ces identifiants historiques}
        {--timeout=20 : Délai maximum par image, en secondes}';

    protected $description = 'Télécharge les photos des programmes historiques depuis ajinsafro.ma vers la médiathèque.';

    /** Dossier de destination sur le disque public. */
    private const TARGET_DIR = 'voyages/legacy';

    /** Un visuel de catalogue plus lourd est presque sûrement une erreur de contenu. */
    private const MAX_BYTES = 12_000_000;

    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $limit = max(0, (int) $this->option('limit'));
        $timeout = max(5, (int) $this->option('timeout'));
        $onlyIds = array_map('intval', (array) $this->option('id'));

        $voyages = Voyage::query()
            ->where('logistics_meta', 'like', '%"images_a_rapatrier"%')
            ->orderBy('id')
            ->get()
            ->filter(fn (Voyage $v) => $v->isLegacyImport())
            ->filter(fn (Voyage $v) => $onlyIds === []
                || in_array((int) data_get($v->logistics_meta, 'legacy_import.legacy_id'), $onlyIds, true))
            ->filter(fn (Voyage $v) => $this->pendingUrls($v) !== []);

        if ($voyages->isEmpty()) {
            $this->info('Aucune image en attente de rapatriement.');

            return self::SUCCESS;
        }

        $this->line(sprintf(
            '%d programme(s) avec des images à rapatrier, %d fichier(s) au total.',
            $voyages->count(),
            $voyages->sum(fn (Voyage $v) => count($this->pendingUrls($v)))
        ));
        $this->newLine();

        $stats = ['imported' => 0, 'failed' => 0, 'programs' => 0, 'covers' => 0];
        $processed = 0;

        foreach ($voyages as $voyage) {
            if ($limit > 0 && $processed >= $limit) {
                break;
            }
            $processed++;
            $stats['programs']++;

            $legacyId = (int) data_get($voyage->logistics_meta, 'legacy_import.legacy_id');
            $pending = $this->pendingUrls($voyage);

            if (! $execute) {
                $this->line(sprintf('  legacy %-4d %-46s %d image(s)', $legacyId, \Illuminate\Support\Str::limit($voyage->slug, 45), count($pending)));
                $stats['imported'] += count($pending);

                continue;
            }

            $failures = [];
            $imported = $this->importFor($voyage, $legacyId, $pending, $timeout, $stats, $failures);

            $this->line(sprintf(
                '  legacy %-4d %-46s %d/%d',
                $legacyId,
                \Illuminate\Support\Str::limit($voyage->slug, 45),
                $imported,
                count($pending)
            ));

            // Les échecs sont listés sous le programme concerné, pas au fil du téléchargement.
            foreach ($failures as $failure) {
                $this->warn('       '.$failure);
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%s : %d image(s) sur %d programme(s), %d couverture(s) posée(s), %d échec(s).',
            $execute ? 'Rapatriement' : 'Simulation (relancez avec --execute)',
            $stats['imported'],
            $stats['programs'],
            $stats['covers'],
            $stats['failed']
        ));

        if (! $execute) {
            $this->line('Le serveur doit pouvoir joindre ajinsafro.ma pour que le téléchargement aboutisse.');
        }

        return self::SUCCESS;
    }

    /**
     * URLs listées par le seeder et pas encore copiées dans la médiathèque.
     *
     * @return list<string>
     */
    private function pendingUrls(Voyage $voyage): array
    {
        $wanted = data_get($voyage->logistics_meta, 'legacy_import.images_a_rapatrier', []);
        $done = data_get($voyage->logistics_meta, 'legacy_import.images_importees', []);

        if (! is_array($wanted)) {
            return [];
        }
        $done = is_array($done) ? $done : [];

        $out = [];
        foreach ($wanted as $url) {
            if (is_string($url) && $url !== '' && ! array_key_exists($url, $done)) {
                $out[] = $url;
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $pending
     * @param  array<string, int>  $stats
     * @param  list<string>  $failures
     */
    private function importFor(Voyage $voyage, int $legacyId, array $pending, int $timeout, array &$stats, array &$failures): int
    {
        $meta = is_array($voyage->logistics_meta) ? $voyage->logistics_meta : [];
        $done = data_get($meta, 'legacy_import.images_importees', []);
        $done = is_array($done) ? $done : [];

        $sort = (int) VoyageImage::query()->where('voyage_id', $voyage->id)->max('sort_order');
        $imported = 0;

        foreach ($pending as $url) {
            $file = $this->download($url, $timeout);

            if ($file === null) {
                $stats['failed']++;
                $failures[] = 'indisponible : '.\Illuminate\Support\Str::limit($url, 88);

                continue;
            }

            $sort++;
            $path = sprintf('%s/%d/%d.%s', self::TARGET_DIR, $legacyId, $sort, $file['ext']);

            if (! Storage::disk('public')->put($path, $file['body'])) {
                $stats['failed']++;
                $failures[] = 'écriture impossible : '.$path;

                continue;
            }

            VoyageImage::create([
                'voyage_id' => $voyage->id,
                'path' => $path,
                'sort_order' => $sort,
            ]);

            // La première photo rapatriée sert de couverture si la fiche n'en a pas.
            if (empty($voyage->featured_image)) {
                $voyage->featured_image = $path;
                $stats['covers']++;
            }

            $done[$url] = $path;
            $imported++;
            $stats['imported']++;
        }

        if ($imported > 0) {
            $meta['legacy_import']['images_importees'] = $done;

            // La fiche n'attend plus ses visuels : on retire la mention de la liste des manques.
            $missing = data_get($meta, 'completion.missing', []);
            if (is_array($missing) && $this->pendingAfter($meta) === 0) {
                $meta['completion']['missing'] = array_values(array_diff($missing, ['images']));
            }

            $voyage->logistics_meta = $meta;
            $voyage->save();
        }

        return $imported;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function pendingAfter(array $meta): int
    {
        $wanted = data_get($meta, 'legacy_import.images_a_rapatrier', []);
        $done = data_get($meta, 'legacy_import.images_importees', []);

        return count(array_diff(is_array($wanted) ? $wanted : [], array_keys(is_array($done) ? $done : [])));
    }

    /**
     * @return array{body: string, ext: string}|null
     */
    private function download(string $url, int $timeout): ?array
    {
        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(min(10, $timeout))
                ->retry(2, 500, throw: false)
                ->withHeaders(['User-Agent' => 'Ajinsafro-migration/1.0'])
                ->get($url);
        } catch (\Throwable $e) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $body = $response->body();
        if ($body === '' || strlen($body) > self::MAX_BYTES) {
            return null;
        }

        // Le type déclaré ne suffit pas : on vérifie que le corps est bien une image décodable.
        $info = @getimagesizefromstring($body);
        if ($info === false) {
            return null;
        }

        // Le type détecté prime sur l'en-tête : ajinsafro.ma annonce `image/jpeg` pour tous ses
        // fichiers, y compris les PNG et les WebP. Se fier à l'en-tête donnerait une extension
        // qui ment sur le contenu.
        $detected = strtolower((string) ($info['mime'] ?? ''));
        $declared = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        $ext = self::ALLOWED_MIME[$detected] ?? self::ALLOWED_MIME[$declared] ?? null;

        if ($ext === null) {
            return null;
        }

        return ['body' => $body, 'ext' => $ext];
    }
}
