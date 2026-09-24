<?php

namespace Database\Seeders;

use App\Models\Departure;
use App\Models\TravelProgramDay;
use App\Models\Voyage;
use App\Models\VoyageTheme;
use App\Models\Wp\WpPostMeta;
use App\Support\LegacyDateParser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Import complet du catalogue historique ajinsafro.ma (194 programmes) dans le catalogue Laravel.
 *
 * Remplace {@see LegacyProgramsSeeder} (89 programmes, contenu partiel) : le jeu de 194 en est un
 * sur-ensemble strict, keyé sur le même `legacy_id`, donc les 89 déjà importés sont **mis à jour**
 * et enrichis (itinéraire jour par jour, dates de départ, hôtels, suppléments, images, SEO).
 *
 * Règles conservées :
 *  - Laravel possède la donnée métier ; chaque programme est un {@see Voyage} en brouillon.
 *  - L'URL publique garde le chemin d'origine `.ma` ; seul le domaine change.
 *  - Rien n'est inventé : ce qui manque est listé sous `logistics_meta.completion` (« À compléter »).
 *  - Idempotent : relancer ne duplique rien et n'écrase pas une fiche déjà reprise par un agent.
 *
 * Lancement : php artisan db:seed --class=LegacyContentSeeder
 */
class LegacyContentSeeder extends Seeder
{
    /** Dataset d'extraction du 23/09/2026 (194 programmes, 225 anciennes URLs). */
    public const DATASET_PATH = 'database/data/legacy-content-194.json';

    public const SOURCE = 'ajinsafro.ma';

    public const COMPLETION_INCOMPLETE = Voyage::LEGACY_COMPLETION_INCOMPLETE;

    public const COMPLETION_LABEL = Voyage::LEGACY_COMPLETION_LABEL;

    /** URLs techniques de l'ancien site : pas de chemin SEO exploitable. */
    private const TECHNICAL_PATHS = ['onedeal.php', 'buy.php'];

    public function run(): void
    {
        $stats = [
            'created' => 0, 'updated' => 0, 'skipped' => 0,
            'days' => 0, 'departures' => 0, 'themes' => 0, 'wp_linked' => 0,
        ];

        foreach ($this->dataset() as $program) {
            $outcome = $this->importProgram($program);
            $stats[$outcome['result']]++;
            $stats['days'] += $outcome['days'];
            $stats['departures'] += $outcome['departures'];
            $stats['themes'] += $outcome['themes'];
            if ($outcome['wp_linked']) {
                $stats['wp_linked']++;
            }
        }

        $this->command?->info(sprintf(
            'Catalogue historique : %d créés, %d mis à jour, %d ignorés — %d jours de programme, %d départs datés, %d thèmes, %d liés à un tour WordPress.',
            $stats['created'],
            $stats['updated'],
            $stats['skipped'],
            $stats['days'],
            $stats['departures'],
            $stats['themes'],
            $stats['wp_linked']
        ));
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
        $rows = $decoded['programmes'] ?? null;

        if (! is_array($rows)) {
            throw new \RuntimeException('Dataset historique illisible : '.json_last_error_msg());
        }

        return array_values(array_filter($rows, static fn ($row) => is_array($row) && isset($row['id'])));
    }

    /**
     * @param  array<string, mixed>  $program
     * @return array{result: string, days: int, departures: int, themes: int, wp_linked: bool}
     */
    private function importProgram(array $program): array
    {
        $legacyId = (int) $program['id'];
        $name = $this->title($program);

        if ($legacyId <= 0 || $name === '') {
            return ['result' => 'skipped', 'days' => 0, 'departures' => 0, 'themes' => 0, 'wp_linked' => false];
        }

        $url = $this->legacyUrl($program);
        $voyage = $this->findExisting($legacyId, $url['slug']);
        $result = $voyage ? 'updated' : 'created';

        // Une fiche déjà reprise par un agent n'est jamais réécrite : on ne rafraîchit que les
        // métadonnées de migration (URLs historiques, lien WordPress).
        $contentIsEditable = $voyage === null || $this->isStillIncomplete($voyage);

        if ($voyage === null) {
            $voyage = new Voyage(['slug' => $url['slug']]);
        }

        if ($contentIsEditable) {
            $voyage->fill($this->voyageAttributes($program, $url, $name));
        }

        $wpPostId = $voyage->wp_post_id ? (int) $voyage->wp_post_id : $this->findWpPostId($legacyId);
        if ($wpPostId) {
            $voyage->wp_post_id = $wpPostId;
        }

        $voyage->logistics_meta = $this->mergeMeta($voyage, $program, $url, $wpPostId);
        $voyage->save();

        $days = $departures = $themes = 0;
        if ($contentIsEditable) {
            $days = $this->seedProgramDays($voyage, $program);
            $departures = $this->seedDepartures($voyage, $program);
            $themes = $this->syncThemes($voyage, $program);
        }

        return [
            'result' => $result,
            'days' => $days,
            'departures' => $departures,
            'themes' => $themes,
            'wp_linked' => (bool) $wpPostId,
        ];
    }

    /** Titre commercial réel de l'offre, pas le libellé d'inventaire. */
    private function title(array $program): string
    {
        foreach (['titre_h1', 'h1', 'programme_inventaire', 'titre_page'] as $key) {
            $value = trim((string) ($program[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

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
     * Choisit le chemin public historique à rejouer. L'ancien site exposait plusieurs URLs par
     * programme : on retient l'URL SEO (« Programme principal ») et on écarte les pages techniques
     * `/onedeal.php?id=` et `/team/buy.php?id=`, qui n'ont pas de chemin réutilisable.
     *
     * @return array{slug: string, path: ?string, prefix: ?string, preserved: bool, urls: list<string>, paths: list<string>}
     */
    private function legacyUrl(array $program): array
    {
        $entries = is_array($program['urls_anciennes'] ?? null) ? $program['urls_anciennes'] : [];

        $urls = [];
        $paths = [];
        $best = null;

        foreach ($entries as $entry) {
            $url = trim((string) ($entry['url'] ?? ''));
            $path = trim((string) ($entry['chemin'] ?? ''));
            if ($url !== '') {
                $urls[] = $url;
            }
            if ($path === '') {
                continue;
            }

            // Toutes les anciennes URLs figurent dans la table des 301, y compris les pages
            // techniques ; seules les URLs SEO peuvent en revanche fournir le slug public.
            $paths[] = $path;

            if ($this->isTechnicalPath($path)) {
                continue;
            }
            $segments = array_values(array_filter(
                explode('/', (string) parse_url($path, PHP_URL_PATH)),
                static fn ($s) => $s !== ''
            ));
            if (count($segments) < 2) {
                continue;
            }

            $candidate = [
                'prefix' => $segments[0],
                'slug' => $segments[1],
                'path' => '/'.$segments[0].'/'.$segments[1],
                'main' => str_contains(mb_strtolower((string) ($entry['type'] ?? '')), 'principal'),
            ];

            // L'URL « Programme principal » prime ; sinon la première exploitable fait foi.
            if ($best === null || ($candidate['main'] && ! $best['main'])) {
                $best = $candidate;
            }
        }

        if ($best !== null) {
            return [
                'slug' => $best['slug'],
                'path' => $best['path'],
                'prefix' => $best['prefix'],
                'preserved' => true,
                'urls' => array_values(array_unique($urls)),
                'paths' => array_values(array_unique($paths)),
            ];
        }

        // Aucune URL SEO historique : le chemin public reste à arbitrer.
        $fallback = trim((string) ($program['slug_net_propose'] ?? ''));
        if ($fallback === '') {
            $fallback = Str::slug($this->title($program)).'-'.(int) $program['id'];
        }

        return [
            'slug' => $fallback,
            'path' => null,
            'prefix' => null,
            'preserved' => false,
            'urls' => array_values(array_unique($urls)),
            'paths' => array_values(array_unique($paths)),
        ];
    }

    private function isTechnicalPath(string $path): bool
    {
        foreach (self::TECHNICAL_PATHS as $needle) {
            if (str_contains($path, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function voyageAttributes(array $program, array $url, string $name): array
    {
        return [
            'name' => $name,
            'slug' => $url['slug'],
            'accroche' => $this->text($program['meta_description'] ?? null),
            'description' => $this->buildDescription($program),
            'destination' => $this->destination($program),
            'duration_text' => $this->durationText($program),
            'price_from' => $this->amount($program['prix_actuel'] ?? null),
            'old_price' => $this->amount($program['prix_barre'] ?? null),
            'currency' => trim((string) ($program['devise'] ?? '')) ?: 'MAD',
            // Brouillon : la fiche n'est ni publique ni vendable tant qu'elle est « À compléter ».
            'status' => 'draft',
            'tour_price_by' => 'person',
            'tours_include' => $this->stringList($program['inclus'] ?? []),
            'tours_exclude' => $this->stringList($program['non_inclus'] ?? []),
        ];
    }

    /**
     * Destination commerciale. Le dernier élément de `destinations` est le pays pour
     * l'international ; le segment tranche pour le national, l'hébergement et l'Omra.
     */
    private function destination(array $program): ?string
    {
        $segment = (string) ($program['segment_net'] ?? '');

        if ($segment === 'hajj-omra') {
            return 'Arabie Saoudite';
        }

        if ($segment === 'voyages-maroc' || $segment === 'hebergement') {
            return 'Maroc';
        }

        $destinations = array_values(array_filter(
            array_map(static fn ($d) => trim((string) $d), (array) ($program['destinations'] ?? [])),
            static fn ($d) => $d !== ''
        ));

        return $destinations === [] ? null : end($destinations);
    }

    private function durationText(array $program): ?string
    {
        $days = (int) ($program['duree_jours'] ?? 0);
        $nights = (int) ($program['duree_nuits'] ?? 0);

        if ($days > 0 && $nights > 0) {
            return sprintf('%d jours / %d nuits', $days, $nights);
        }
        if ($days > 0) {
            return sprintf('%d jour%s', $days, $days > 1 ? 's' : '');
        }
        if ($nights > 0) {
            return sprintf('%d nuit%s', $nights, $nights > 1 ? 's' : '');
        }

        return null;
    }

    /**
     * Description éditoriale : le texte long de l'ancien site, complété par les éléments
     * commerciaux qui n'ont pas de colonne dédiée (hôtels, suppléments).
     */
    private function buildDescription(array $program): ?string
    {
        $parts = [];

        $long = $this->text($program['description_longue'] ?? null);
        if ($long !== null) {
            $parts[] = $long;
        }

        $hotels = [];
        foreach ((array) ($program['hotels'] ?? []) as $hotel) {
            if (! is_array($hotel)) {
                continue;
            }
            $label = trim(implode(' ', array_filter([
                trim((string) ($hotel['nom'] ?? '')),
                trim((string) ($hotel['categorie'] ?? '')),
            ])));
            $city = trim((string) ($hotel['ville'] ?? ''));
            if ($label === '' && $city === '') {
                continue;
            }
            $hotels[] = '- '.trim($label.($city !== '' ? ' — '.$city : ''), ' —');
        }
        if ($hotels !== []) {
            $parts[] = "Hébergement relevé sur l'ancien site :\n".implode("\n", array_unique($hotels));
        }

        $supplements = $this->stringList($program['supplements'] ?? []);
        if ($supplements !== []) {
            $parts[] = "Suppléments et tarifs relevés :\n- ".implode("\n- ", $supplements);
        }

        $notes = $this->text($program['notes_extraction'] ?? null);
        if ($notes !== null) {
            $parts[] = 'Note de récupération : '.$notes;
        }

        return $parts === [] ? null : implode("\n\n", $parts);
    }

    /**
     * Itinéraire jour par jour. `jour` est hétérogène côté source (entier, « Samedi »,
     * « 6 et 7 », « Jour de départ ») : la position fait foi pour `day_number` et le libellé
     * d'origine est conservé dans le titre.
     */
    private function seedProgramDays(Voyage $voyage, array $program): int
    {
        $steps = array_values(array_filter(
            (array) ($program['itineraire'] ?? []),
            static fn ($s) => is_array($s) && trim((string) ($s['texte'] ?? '')) !== ''
        ));

        if ($steps === []) {
            return 0;
        }

        // Le jeu de 194 apporte le vrai jour par jour : on remplace l'ossature approximative
        // posée par l'import précédent, tant que la fiche n'a pas été reprise.
        TravelProgramDay::query()->where('voyage_id', $voyage->id)->delete();

        $created = 0;
        foreach ($steps as $index => $step) {
            $label = trim((string) ($step['jour'] ?? ''));
            $title = trim((string) ($step['titre'] ?? ''));

            if ($title === '') {
                $title = $label !== '' ? Str::ucfirst($label) : 'Jour '.($index + 1);
            }

            TravelProgramDay::create([
                'voyage_id' => $voyage->id,
                'day_number' => $index + 1,
                'title' => Str::limit($title, 250, ''),
                'description' => trim((string) $step['texte']),
                'day_type' => 'visite',
                'nights' => 0,
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * Dates de départ. Seules les lignes portant une date complète deviennent un départ ; les
     * récurrences (« chaque samedi ») et les points de ramassage restent en texte dans la meta.
     */
    private function seedDepartures(Voyage $voyage, array $program): int
    {
        $created = 0;

        foreach ((array) ($program['dates_depart'] ?? []) as $line) {
            $parsed = LegacyDateParser::parse((string) $line);
            if ($parsed['start'] === null) {
                continue;
            }

            // Contrainte unique (voyage_id, start_date) : jamais deux départs le même jour.
            $exists = Departure::query()
                ->where('voyage_id', $voyage->id)
                ->whereDate('start_date', $parsed['start'])
                ->exists();

            if ($exists) {
                continue;
            }

            Departure::create([
                'voyage_id' => $voyage->id,
                'start_date' => $parsed['start'],
                'end_date' => $parsed['end'],
                // Brouillon : départ historique, non vendable tant que la fiche n'est pas reprise.
                'status' => Departure::STATUS_DRAFT,
                'total_capacity' => 0,
                'available_capacity' => 0,
                'notes' => 'Départ historique ajinsafro.ma : '.Str::limit($parsed['raw'], 200),
            ]);
            $created++;
        }

        return $created;
    }

    private function syncThemes(Voyage $voyage, array $program): int
    {
        if ($voyage->themes()->exists()) {
            return 0;
        }

        $slug = $this->themeSlug($program);
        if ($slug === null) {
            return 0;
        }

        $theme = VoyageTheme::query()->where('slug', $slug)->first();
        if (! $theme) {
            return 0;
        }

        $voyage->themes()->syncWithoutDetaching([$theme->id]);

        return 1;
    }

    private function themeSlug(array $program): ?string
    {
        $segment = (string) ($program['segment_net'] ?? '');
        $categorie = mb_strtolower((string) ($program['categorie'] ?? ''));
        $title = mb_strtolower($this->title($program));

        if ($segment === 'hajj-omra' || str_contains($title, 'omra')) {
            return 'omra';
        }
        if (str_contains($categorie, 'hébergement') || str_contains($categorie, 'hebergement')) {
            return 'sejour';
        }
        if (str_contains($title, 'week-end') || str_contains($title, 'weekend')) {
            return 'week-end';
        }
        if (str_contains($title, 'circuit')) {
            return 'circuit';
        }
        if (str_contains($title, 'séjour') || str_contains($title, 'sejour')) {
            return 'sejour';
        }
        if ($segment === 'voyages-organises') {
            return 'voyage-organise';
        }

        return null;
    }

    /**
     * Blocs de migration portés par `logistics_meta`, sans écraser les clés du formulaire
     * logistique (train / boat / transport).
     *
     * @return array<string, mixed>
     */
    private function mergeMeta(Voyage $voyage, array $program, array $url, ?int $wpPostId): array
    {
        $meta = is_array($voyage->logistics_meta) ? $voyage->logistics_meta : [];
        $legacyId = (int) $program['id'];

        $meta['legacy_import'] = [
            'source' => self::SOURCE,
            'legacy_id' => $legacyId,
            'categorie' => $this->text($program['categorie'] ?? null),
            'statut_extraction' => $this->text($program['statut_extraction'] ?? null),
            'statut_offre' => $this->text($program['statut_offre'] ?? null),
            'date_expiration' => $this->text($program['date_expiration'] ?? null),
            'prix_historique' => $this->amount($program['prix_actuel'] ?? null),
            'prix_barre_historique' => $this->amount($program['prix_barre'] ?? null),
            'remise_pct' => $program['remise_pct'] ?? null,
            'langue' => $this->text($program['langue'] ?? null),
            'a_verifier' => (bool) ($program['a_verifier'] ?? false),
            'notes_extraction' => $this->text($program['notes_extraction'] ?? null),
            'destinations' => $this->stringList($program['destinations'] ?? []),
            'hotels' => array_values(array_filter((array) ($program['hotels'] ?? []), 'is_array')),
            'supplements' => $this->stringList($program['supplements'] ?? []),
            // Récurrences et points de ramassage : pas des dates, mais à reprendre manuellement.
            'departs_non_dates' => $this->undatedDepartures($program),
            'images_a_rapatrier' => $this->contentImages($program),
            'imported_at' => now()->toDateTimeString(),
        ];

        $meta['seo'] = [
            'keep_legacy_path' => $url['preserved'],
            'legacy_slug' => $url['slug'],
            'legacy_path' => $url['path'],
            'legacy_path_prefix' => $url['prefix'],
            'legacy_urls' => $url['urls'],
            'legacy_paths' => $url['paths'],
            'target_url' => $url['path'] === null
                ? null
                : 'https://'.config('app.public_domain', 'ajinsafro.net').$url['path'],
            'titre_seo' => $this->text($program['titre_seo'] ?? $program['titre_page'] ?? null),
            'meta_description' => $this->text($program['meta_description'] ?? null),
            // Proposition de l'extraction, non appliquée : la règle retenue reste « même chemin,
            // seul le domaine change ». À arbitrer avant de figer les 301.
            'segment_net_propose' => $this->text($program['segment_net'] ?? null),
            'url_net_proposee' => $this->text($program['url_net_proposee'] ?? null),
        ];

        $meta['completion'] = [
            'status' => self::COMPLETION_INCOMPLETE,
            'label' => self::COMPLETION_LABEL,
            'missing' => $this->missing($program, $url, $wpPostId),
            'checked_at' => now()->toDateTimeString(),
        ];

        return $meta;
    }

    /**
     * @return list<string>
     */
    private function undatedDepartures(array $program): array
    {
        $out = [];
        foreach ((array) ($program['dates_depart'] ?? []) as $line) {
            if (LegacyDateParser::parse((string) $line)['start'] === null) {
                $out[] = trim((string) $line);
            }
        }

        return array_values(array_filter(array_unique($out)));
    }

    /**
     * Photos réelles de l'offre. Les assets génériques du template (logo, icônes, réseaux
     * sociaux) sont écartés : seuls les fichiers `/static/team/` sont du contenu.
     *
     * @return list<string>
     */
    private function contentImages(array $program): array
    {
        $out = [];
        foreach ((array) ($program['images'] ?? []) as $image) {
            $url = trim((string) $image);
            if ($url !== '' && str_contains($url, '/static/team/')) {
                $out[] = $url;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @return list<string>
     */
    private function missing(array $program, array $url, ?int $wpPostId): array
    {
        // Jamais repris automatiquement : ces éléments demandent une décision commerciale.
        $missing = ['images', 'departs_vendables'];

        if ($this->amount($program['prix_actuel'] ?? null) === null) {
            $missing[] = 'prix';
        }
        if ($this->durationText($program) === null) {
            $missing[] = 'duree';
        }
        if ($this->destination($program) === null) {
            $missing[] = 'destination';
        }
        if ($this->themeSlug($program) === null) {
            $missing[] = 'themes';
        }
        if ((array) ($program['itineraire'] ?? []) === []) {
            $missing[] = 'programme_jours';
        }
        if ($this->stringList($program['inclus'] ?? []) === []) {
            $missing[] = 'prestations_incluses';
        }
        if (! $url['preserved']) {
            $missing[] = 'url_publique';
        }
        if (! $wpPostId) {
            $missing[] = 'lien_wordpress';
        }
        if (($program['a_verifier'] ?? false) === true) {
            $missing[] = 'contenu_a_relire';
        }
        if (($program['statut_extraction'] ?? '') === 'partiel') {
            $missing[] = 'extraction_partielle';
        }

        return array_values(array_unique($missing));
    }

    private function findWpPostId(int $legacyId): ?int
    {
        try {
            // Connexion 'wp' : l'accès utilisé par le catalogue admin, donc celui qui fonctionne
            // réellement sur les environnements déployés.
            $postId = (int) WpPostMeta::query()
                ->where('meta_key', Voyage::WP_LEGACY_ID_META)
                ->where('meta_value', (string) $legacyId)
                ->value('post_id');

            return $postId > 0 ? $postId : null;
        } catch (\Throwable $e) {
            // Base WordPress non joignable : l'import Laravel reste valable.
            return null;
        }
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
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }
        if (is_float($value)) {
            return $value > 0 ? (int) round($value) : null;
        }
        if (is_string($value) && preg_match('/\d+/', str_replace([' ', ',', "\u{00A0}"], '', $value), $m)) {
            return (int) $m[0] > 0 ? (int) $m[0] : null;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (! is_string($item)) {
                continue;
            }
            $item = trim($item);
            if ($item !== '') {
                $out[] = $item;
            }
        }

        return array_values(array_unique($out));
    }
}
