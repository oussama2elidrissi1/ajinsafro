<?php

namespace Database\Seeders;

use App\Models\TravelProgramDay;
use App\Models\Voyage;
use App\Models\VoyageTheme;
use App\Models\Wp\WpPostMeta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Import du catalogue historique ajinsafro.ma (89 programmes) dans le catalogue Laravel.
 *
 * Règles :
 *  - Laravel possède la donnée métier : chaque programme devient un {@see Voyage} en brouillon.
 *  - Les URLs publiques sont conservées à l'identique : le slug Laravel reprend le dernier
 *    segment de l'ancienne URL `.ma`, et le chemin complet (`/voyage-national/...`,
 *    `/voyages-international/...`) est mémorisé dans `logistics_meta.seo`. Seul le domaine change.
 *  - Aucune image, aucun départ, aucun prix de vente actif ne sont créés : tout ce qui manque
 *    est listé sous `logistics_meta.completion` avec la marque « À compléter ».
 *  - Idempotent : relancer le seeder ne duplique rien et n'écrase pas une fiche déjà complétée.
 *
 * Lancement : php artisan db:seed --class=LegacyProgramsSeeder
 */
class LegacyProgramsSeeder extends Seeder
{
    /** Dataset partagé avec le plugin WordPress de migration (même fichier, même contenu). */
    public const DATASET_PATH = 'database/data/legacy-programs.json';

    public const SOURCE = 'ajinsafro.ma';

    public const COMPLETION_INCOMPLETE = 'incomplete';

    public const COMPLETION_LABEL = 'À compléter';

    /** Meta posée sur les posts WordPress importés, sert à retrouver le tour WP. */
    public const WP_LEGACY_ID_META = Voyage::WP_LEGACY_ID_META;

    /**
     * Préfixes de chemin de l'ancien site. Ils font partie de l'URL publique et doivent être
     * rejoués tels quels sur le nouveau domaine.
     */
    public const LEGACY_PATH_PREFIXES = ['voyage-national', 'voyages-international'];

    public function run(): void
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'wp_linked' => 0];

        foreach ($this->dataset() as $program) {
            $outcome = $this->importProgram($program);
            $stats[$outcome['result']]++;
            if ($outcome['wp_linked']) {
                $stats['wp_linked']++;
            }
        }

        $this->report($stats);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function dataset(): array
    {
        $file = base_path(self::DATASET_PATH);
        if (! is_readable($file)) {
            throw new \RuntimeException('Dataset historique introuvable : '.self::DATASET_PATH);
        }

        $decoded = json_decode((string) file_get_contents($file), true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Dataset historique illisible : '.json_last_error_msg());
        }

        return array_values(array_filter($decoded, static fn ($row) => is_array($row) && isset($row['legacy_id'])));
    }

    /**
     * @param  array<string, mixed>  $program
     * @return array{result: string, wp_linked: bool}
     */
    private function importProgram(array $program): array
    {
        $legacyId = (int) $program['legacy_id'];
        $title = trim((string) ($program['title'] ?? ''));
        if ($legacyId <= 0 || $title === '') {
            return ['result' => 'skipped', 'wp_linked' => false];
        }

        $url = $this->legacyUrl($program);
        $voyage = $this->findExisting($legacyId, $url['slug']);
        $result = $voyage ? 'updated' : 'created';

        // Une fiche déjà reprise par un agent n'est jamais réécrite : on rafraîchit seulement les
        // métadonnées de migration (URLs historiques, lien WP).
        $contentIsEditable = $voyage === null || $this->isStillIncomplete($voyage);

        if ($voyage === null) {
            $voyage = new Voyage(['slug' => $url['slug']]);
        }

        if ($contentIsEditable) {
            $voyage->fill($this->voyageAttributes($program, $url));
        }

        $wpPostId = $voyage->wp_post_id ? (int) $voyage->wp_post_id : $this->findWpPostId($legacyId);
        if ($wpPostId) {
            $voyage->wp_post_id = $wpPostId;
        }

        $voyage->logistics_meta = $this->mergeMeta($voyage, $program, $url, $wpPostId);
        $voyage->save();

        if ($contentIsEditable) {
            $this->seedProgramDays($voyage, $program);
            $this->syncThemes($voyage, $program);
        }

        return ['result' => $result, 'wp_linked' => (bool) $wpPostId];
    }

    /**
     * Retrouve la fiche déjà importée, d'abord par identifiant historique puis par slug
     * (le slug est l'URL publique : deux fiches ne peuvent pas le partager).
     */
    private function findExisting(int $legacyId, string $slug): ?Voyage
    {
        $byLegacyId = Voyage::query()
            ->where('logistics_meta', 'like', '%"legacy_id":'.$legacyId.'%')
            ->get()
            ->first(fn (Voyage $v) => (int) data_get($v->logistics_meta, 'legacy_import.legacy_id') === $legacyId);

        return $byLegacyId ?? Voyage::query()->where('slug', $slug)->first();
    }

    private function isStillIncomplete(Voyage $voyage): bool
    {
        $status = (string) data_get($voyage->logistics_meta, 'completion.status', self::COMPLETION_INCOMPLETE);

        return $status === self::COMPLETION_INCOMPLETE;
    }

    /**
     * Décompose l'ancienne URL `.ma` pour rejouer exactement le même chemin sur le nouveau domaine.
     *
     * @param  array<string, mixed>  $program
     * @return array{slug: string, path: ?string, prefix: ?string, preserved: bool}
     */
    private function legacyUrl(array $program): array
    {
        $legacyId = (int) $program['legacy_id'];
        $sourceUrl = trim((string) ($program['source_url'] ?? ''));
        $path = (string) parse_url($sourceUrl, PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', $path), static fn ($s) => $s !== ''));

        $prefix = $segments[0] ?? '';
        $slug = $segments[1] ?? '';

        if (in_array($prefix, self::LEGACY_PATH_PREFIXES, true) && $slug !== '') {
            return [
                'slug' => $slug,
                'path' => '/'.$prefix.'/'.$slug,
                'prefix' => $prefix,
                'preserved' => true,
            ];
        }

        // Fiches dont seule l'ancienne page de commande (`/team/buy.php?id=...`) a été retrouvée :
        // il n'existe pas d'URL SEO historique, le chemin public reste à définir.
        return [
            'slug' => Str::slug($program['title'] ?? '').'-'.$legacyId,
            'path' => null,
            'prefix' => null,
            'preserved' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $program
     * @param  array{slug: string, path: ?string, prefix: ?string, preserved: bool}  $url
     * @return array<string, mixed>
     */
    private function voyageAttributes(array $program, array $url): array
    {
        return [
            'name' => trim((string) $program['title']),
            'slug' => $url['slug'],
            'accroche' => $this->text($program['summary'] ?? null),
            'description' => $this->buildDescription($program),
            'destination' => $this->destination($program),
            'duration_text' => $this->text($program['duration'] ?? null),
            'price_from' => $this->amount($program['price'] ?? null),
            'old_price' => $this->amount($program['value'] ?? null),
            'currency' => 'MAD',
            // Brouillon : invisible du catalogue commercial et du front public tant que la fiche
            // n'est pas complétée, et poussé en `draft` côté WordPress par la synchro.
            'status' => 'draft',
            'tour_price_by' => 'person',
            'tours_include' => $this->list($program['included'] ?? []),
            'tours_exclude' => $this->list($program['excluded'] ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $program
     */
    private function buildDescription(array $program): string
    {
        $lines = [];

        $summary = $this->text($program['summary'] ?? null);
        if ($summary !== null) {
            $lines[] = $summary;
        }

        $duration = $this->text($program['duration'] ?? null);
        if ($duration !== null) {
            $lines[] = 'Durée historique : '.$duration;
        }

        $price = $this->amount($program['price'] ?? null);
        if ($price !== null) {
            $lines[] = 'Tarif historique relevé : '.number_format($price, 0, ',', ' ').' DH';
        }

        $expiredAt = $this->text($program['source_expired_at'] ?? null);
        if ($expiredAt !== null) {
            $lines[] = 'Dernière validité connue : '.$expiredAt;
        }

        $note = $this->text($program['source_note'] ?? null);
        if ($note !== null) {
            $lines[] = $note;
        }

        $source = $this->text($program['source_url'] ?? null);
        if ($source !== null) {
            $lines[] = 'Fiche historique : '.$source;
        }

        return implode("\n\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $program
     */
    private function destination(array $program): ?string
    {
        $category = trim(Str::lower((string) ($program['category'] ?? '')));

        if (str_contains($category, 'omra')) {
            return 'Arabie Saoudite';
        }

        // Comparaison sur le début de la catégorie : « Voyage international » contient la
        // sous-chaîne « national », et « Voyage national (ancienne route international) » reste
        // un circuit marocain.
        if (str_starts_with($category, 'voyage national')) {
            return 'Maroc';
        }

        // Les circuits internationaux couvrent plusieurs pays : la destination commerciale est
        // choisie par l'agence lors de la reprise de la fiche.
        return null;
    }

    /**
     * Le programme historique est une suite d'étapes récupérées, pas un découpage par jour :
     * on l'importe comme ossature à recaler sur la durée réelle.
     *
     * @param  array<string, mixed>  $program
     */
    private function seedProgramDays(Voyage $voyage, array $program): void
    {
        $steps = $this->list($program['itinerary'] ?? []);
        if ($steps === [] || $voyage->programDays()->exists()) {
            return;
        }

        foreach ($steps as $index => $step) {
            TravelProgramDay::create([
                'voyage_id' => $voyage->id,
                'day_number' => $index + 1,
                'title' => Str::limit($step, 250, ''),
                'description' => 'Étape récupérée du programme historique — à recaler sur le jour réel.',
                'day_type' => 'visite',
                'nights' => 0,
            ]);
        }
    }

    /**
     * Thèmes déduits du titre / de la catégorie historique. Rien n'est inventé : en l'absence de
     * correspondance nette, la fiche reste sans thème et « thèmes » figure dans la liste à compléter.
     *
     * @param  array<string, mixed>  $program
     */
    private function syncThemes(Voyage $voyage, array $program): void
    {
        if ($voyage->themes()->exists()) {
            return;
        }

        $slug = $this->themeSlug($program);
        if ($slug === null) {
            return;
        }

        $theme = VoyageTheme::query()->where('slug', $slug)->first();
        if ($theme) {
            $voyage->themes()->syncWithoutDetaching([$theme->id]);
        }
    }

    /**
     * @param  array<string, mixed>  $program
     */
    private function themeSlug(array $program): ?string
    {
        if (str_contains(Str::lower((string) ($program['category'] ?? '')), 'omra')) {
            return 'omra';
        }

        $title = Str::lower((string) ($program['title'] ?? ''));

        return match (true) {
            str_contains($title, 'week-end'), str_contains($title, 'weekend') => 'week-end',
            str_contains($title, 'circuit') => 'circuit',
            str_contains($title, 'séjour') => 'sejour',
            default => null,
        };
    }

    /**
     * Métadonnées de migration : traçabilité de la source, conservation des URLs et marque
     * « À compléter » exploitée par l'admin.
     *
     * @param  array<string, mixed>  $program
     * @param  array{slug: string, path: ?string, prefix: ?string, preserved: bool}  $url
     * @return array<string, mixed>
     */
    private function mergeMeta(Voyage $voyage, array $program, array $url, ?int $wpPostId): array
    {
        $meta = is_array($voyage->logistics_meta) ? $voyage->logistics_meta : [];
        $legacyId = (int) $program['legacy_id'];

        $meta['legacy_import'] = [
            'source' => self::SOURCE,
            'legacy_id' => $legacyId,
            'category' => $this->text($program['category'] ?? null),
            'service_type' => $this->text($program['service_type'] ?? null) ?? 'tour',
            'recovery' => $this->text($program['recovery'] ?? null) ?? 'partial',
            'source_note' => $this->text($program['source_note'] ?? null),
            'source_expired_at' => $this->text($program['source_expired_at'] ?? null),
            'historical_price' => $this->amount($program['price'] ?? null),
            'historical_value' => $this->amount($program['value'] ?? null),
            'imported_at' => now()->toDateTimeString(),
        ];

        $meta['seo'] = [
            // Structure d'URL identique à l'ancien site : seul le domaine change.
            'keep_legacy_path' => true,
            'legacy_slug' => $url['slug'],
            'legacy_path' => $url['path'],
            'legacy_path_prefix' => $url['prefix'],
            'target_url' => $url['path'] ? 'https://'.config('app.public_domain').$url['path'] : null,
            'legacy_urls' => $this->list($program['legacy_urls'] ?? []),
            'legacy_paths' => $this->legacyPaths($program),
        ];

        $missing = $this->missing($program, $url, $wpPostId);
        $existingStatus = (string) data_get($meta, 'completion.status', self::COMPLETION_INCOMPLETE);

        $meta['completion'] = [
            'status' => $existingStatus === self::COMPLETION_INCOMPLETE ? self::COMPLETION_INCOMPLETE : $existingStatus,
            'label' => self::COMPLETION_LABEL,
            'missing' => $missing,
            'checked_at' => now()->toDateTimeString(),
        ];

        return $meta;
    }

    /**
     * Toutes les variantes d'URL historiques, réduites au chemin : base de la table de
     * redirections 301 lors de la bascule de domaine.
     *
     * @param  array<string, mixed>  $program
     * @return list<string>
     */
    private function legacyPaths(array $program): array
    {
        $paths = [];
        foreach ($this->list($program['legacy_urls'] ?? []) as $legacyUrl) {
            $path = (string) parse_url($legacyUrl, PHP_URL_PATH);
            $query = (string) parse_url($legacyUrl, PHP_URL_QUERY);
            if ($path === '') {
                continue;
            }
            $paths[] = $query !== '' ? $path.'?'.$query : $path;
        }

        return array_values(array_unique($paths));
    }

    /**
     * @param  array<string, mixed>  $program
     * @param  array{slug: string, path: ?string, prefix: ?string, preserved: bool}  $url
     * @return list<string>
     */
    private function missing(array $program, array $url, ?int $wpPostId): array
    {
        $missing = [
            // Aucune image n'est reprise de l'ancien site.
            'images',
            // Le programme importé est une suite d'étapes, pas un découpage jour par jour.
            'programme_jours',
            // Aucune disponibilité n'est créée : la fiche ne peut pas être vendue en l'état.
            'departs',
        ];

        if ($this->amount($program['price'] ?? null) === null) {
            $missing[] = 'prix';
        }
        if ($this->text($program['duration'] ?? null) === null) {
            $missing[] = 'duree';
        }
        if ($this->destination($program) === null) {
            $missing[] = 'destination';
        }
        if ($this->themeSlug($program) === null) {
            $missing[] = 'themes';
        }
        if (($program['recovery'] ?? '') !== 'rich') {
            $missing[] = 'contenu';
        }
        if (! $url['preserved']) {
            // Seule l'ancienne page de commande existe : l'URL publique définitive reste à arbitrer.
            $missing[] = 'url_publique';
        }
        if (($program['service_type'] ?? 'tour') !== 'tour') {
            $missing[] = 'type_offre';
        }
        if (! $wpPostId) {
            $missing[] = 'lien_wordpress';
        }

        return $missing;
    }

    /**
     * Retrouve le tour WordPress créé par le plugin de migration, sans jamais écrire côté WP.
     */
    private function findWpPostId(int $legacyId): ?int
    {
        try {
            // Connexion 'wp' : c'est l'accès utilisé par le catalogue admin, donc celui qui
            // fonctionne réellement sur les environnements déployés.
            $postId = (int) WpPostMeta::query()
                ->where('meta_key', self::WP_LEGACY_ID_META)
                ->where('meta_value', (string) $legacyId)
                ->value('post_id');
        } catch (\Throwable $e) {
            // Tables WordPress absentes (tests, environnement isolé) : le lien reste à faire.
            return null;
        }

        if ($postId <= 0) {
            return null;
        }

        $takenByOther = Voyage::query()
            ->where('wp_post_id', $postId)
            ->whereRaw("COALESCE(logistics_meta, '') NOT LIKE ?", ['%"legacy_id":'.$legacyId.'%'])
            ->exists();

        return $takenByOther ? null : $postId;
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function amount(mixed $value): ?int
    {
        return is_numeric($value) ? (int) round((float) $value) : null;
    }

    /**
     * @return list<string>
     */
    private function list(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => $this->text($item),
            $value
        )));
    }

    /**
     * @param  array<string, int>  $stats
     */
    private function report(array $stats): void
    {
        $message = sprintf(
            'Programmes historiques : %d créés, %d mis à jour, %d ignorés, %d liés à un tour WordPress.',
            $stats['created'],
            $stats['updated'],
            $stats['skipped'],
            $stats['wp_linked']
        );

        if ($this->command) {
            $this->command->info($message);

            return;
        }

        echo $message.PHP_EOL;
    }
}
