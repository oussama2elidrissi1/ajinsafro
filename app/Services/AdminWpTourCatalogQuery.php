<?php

namespace App\Services;

use App\Models\Voyage;
use App\Models\Wp\WpPost;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Source unique de vérité pour la liste des tours affichés dans l’admin « Circuits / voyages »
 * (WordPress `st_tours`) et le catalogue réservation workspace.
 *
 * La page /admin/circuits/voyages lit cette query (paginée) ; le workspace la consomme en liste complète.
 * Les réservations Laravel restent liées à {@see \App\Models\Voyage} via `wp_post_id` → `posts.ID`.
 *
 * Les lignes « package » du workspace doivent toujours afficher le titre et l’ID WordPress comme ici ;
 * Laravel n’enrichit que les actions (réservations, départs, stats), jamais l’identité du tour.
 */
final class AdminWpTourCatalogQuery
{
    private const TOUR_TAXONOMIES = ['st_tour_type', 'tours_cat'];

    /**
     * Requête de base : identique à VoyageController@index (sans pagination).
     * Aucun filtre « publish only » : draft, pending, publish comme dans la liste admin WP.
     */
    public static function baseQuery(): Builder
    {
        return WpPost::query()
            ->tours()
            ->orderByDesc('ID');
    }

    /**
     * Normalise et sécurise les filtres attendus par la page admin voyages.
     *
     * @return array<string, mixed>
     */
    public static function filtersFromRequest(Request $request): array
    {
        $status = trim((string) $request->input('status', ''));
        if (! in_array($status, ['publish', 'draft', 'private', 'pending'], true)) {
            $status = '';
        }

        $hasDepartures = trim((string) $request->input('has_departures', ''));
        if (! in_array($hasDepartures, ['0', '1'], true)) {
            $hasDepartures = '';
        }

        $hasLaravelPublic = trim((string) $request->input('has_laravel_public', ''));
        if (! in_array($hasLaravelPublic, ['0', '1'], true)) {
            $hasLaravelPublic = '';
        }

        $tourType = (int) $request->input('tour_type', 0);

        $priceMin = is_numeric($request->input('price_min')) ? (float) $request->input('price_min') : null;
        $priceMax = is_numeric($request->input('price_max')) ? (float) $request->input('price_max') : null;
        $durationMin = is_numeric($request->input('duration_min')) ? (int) $request->input('duration_min') : null;
        $durationMax = is_numeric($request->input('duration_max')) ? (int) $request->input('duration_max') : null;

        return [
            'status' => $status,
            'tour_type' => $tourType > 0 ? $tourType : null,
            'destination' => trim((string) $request->input('destination', '')),
            'price_min' => $priceMin,
            'price_max' => $priceMax,
            'duration_min' => $durationMin,
            'duration_max' => $durationMax,
            'modified_from' => trim((string) $request->input('modified_from', '')),
            'modified_to' => trim((string) $request->input('modified_to', '')),
            'q' => trim((string) $request->input('q', '')),
            'has_departures' => $hasDepartures,
            'has_laravel_public' => $hasLaravelPublic,
        ];
    }

    /**
     * Requête catalogue avec filtres appliqués.
     *
     * @param array<string, mixed> $filters
     */
    public static function queryFromFilters(array $filters): Builder
    {
        $query = static::baseQuery();
        static::applyFilters($query, $filters);

        return $query;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public static function catalogSummary(array $filters): array
    {
        $base = static::queryFromFilters($filters);

        $published = (clone $base)->where('post_status', 'publish')->count();
        $draft = (clone $base)->where('post_status', 'draft')->count();
        $private = (clone $base)->where('post_status', 'private')->count();
        $pending = (clone $base)->where('post_status', 'pending')->count();

        $withDepartures = (clone $base)->whereIn('ID', static::wpIdsWithDepartures())->count();

        return [
            'total' => $published + $draft + $private + $pending + (clone $base)->whereNotIn('post_status', ['publish', 'draft', 'private', 'pending'])->count(),
            'published' => $published,
            'draft' => $draft,
            'private' => $private,
            'pending' => $pending,
            'with_departures' => $withDepartures,
        ];
    }

    /**
     * @return Collection<int, array{term_id:int,name:string}>
     */
    public static function tourTypeOptions(): Collection
    {
        return DB::connection('wp')
            ->table('terms as t')
            ->join('term_taxonomy as tt', 'tt.term_id', '=', 't.term_id')
            ->whereIn('tt.taxonomy', self::TOUR_TAXONOMIES)
            ->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('term_relationships as tr')
                    ->join('posts as p', 'p.ID', '=', 'tr.object_id')
                    ->whereColumn('tr.term_taxonomy_id', 'tt.term_taxonomy_id')
                    ->where('p.post_type', 'st_tours');
            })
            ->select('t.term_id', 't.name')
            ->distinct()
            ->orderBy('t.name')
            ->get()
            ->map(fn ($row) => [
                'term_id' => (int) ($row->term_id ?? 0),
                'name' => trim((string) ($row->name ?? '')),
            ]);
    }

    /**
     * @return EloquentCollection<int, WpPost>
     */
    public static function allToursOrdered(): EloquentCollection
    {
        return static::baseQuery()->get();
    }

    /**
     * @param array<string, mixed> $filters
     */
    private static function applyFilters(Builder $query, array $filters): void
    {
        $postsTable = (new WpPost())->getTable();

        if (! empty($filters['status'])) {
            $query->where('post_status', (string) $filters['status']);
        }

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $query->where(function (Builder $sub) use ($q) {
                $sub->where('post_title', 'like', '%' . $q . '%')
                    ->orWhere('post_name', 'like', '%' . $q . '%')
                    ->orWhere('post_content', 'like', '%' . $q . '%');
            });
        }

        if (! empty($filters['tour_type'])) {
            $termId = (int) $filters['tour_type'];
            $query->whereExists(function ($sub) use ($postsTable, $termId) {
                $sub->selectRaw('1')
                    ->from('term_relationships as tr')
                    ->join('term_taxonomy as tt', 'tt.term_taxonomy_id', '=', 'tr.term_taxonomy_id')
                    ->whereColumn('tr.object_id', $postsTable . '.ID')
                    ->whereIn('tt.taxonomy', self::TOUR_TAXONOMIES)
                    ->where('tt.term_id', $termId);
            });
        }

        if (! empty($filters['destination'])) {
            $destination = (string) $filters['destination'];
            $query->where(function (Builder $sub) use ($postsTable, $destination) {
                $sub->whereExists(function ($meta) use ($postsTable, $destination) {
                    $meta->selectRaw('1')
                        ->from('postmeta as pm')
                        ->whereColumn('pm.post_id', $postsTable . '.ID')
                        ->whereIn('pm.meta_key', ['address', 'multi_location'])
                        ->where('pm.meta_value', 'like', '%' . $destination . '%');
                })
                ->orWhere('post_title', 'like', '%' . $destination . '%');
            });
        }

        if ($filters['price_min'] !== null) {
            static::whereMetaNumeric($query, 'adult_price', '>=', (float) $filters['price_min']);
        }
        if ($filters['price_max'] !== null) {
            static::whereMetaNumeric($query, 'adult_price', '<=', (float) $filters['price_max']);
        }
        if ($filters['duration_min'] !== null) {
            static::whereMetaNumeric($query, 'duration_day', '>=', (int) $filters['duration_min']);
        }
        if ($filters['duration_max'] !== null) {
            static::whereMetaNumeric($query, 'duration_day', '<=', (int) $filters['duration_max']);
        }

        if (! empty($filters['modified_from'])) {
            $query->whereDate('post_modified', '>=', (string) $filters['modified_from']);
        }
        if (! empty($filters['modified_to'])) {
            $query->whereDate('post_modified', '<=', (string) $filters['modified_to']);
        }

        if ($filters['has_laravel_public'] !== '') {
            $wpIds = static::wpIdsLinkedToLaravel();
            if ($filters['has_laravel_public'] === '1') {
                $query->whereIn('ID', $wpIds === [] ? [0] : $wpIds);
            } else {
                if ($wpIds !== []) {
                    $query->whereNotIn('ID', $wpIds);
                }
            }
        }

        if ($filters['has_departures'] !== '') {
            $wpIds = static::wpIdsWithDepartures();
            if ($filters['has_departures'] === '1') {
                $query->whereIn('ID', $wpIds === [] ? [0] : $wpIds);
            } else {
                if ($wpIds !== []) {
                    $query->whereNotIn('ID', $wpIds);
                }
            }
        }
    }

    private static function whereMetaNumeric(Builder $query, string $metaKey, string $operator, float|int $value): void
    {
        $postsTable = (new WpPost())->getTable();

        $query->whereExists(function ($sub) use ($postsTable, $metaKey, $operator, $value) {
            $sub->selectRaw('1')
                ->from('postmeta as pm')
                ->whereColumn('pm.post_id', $postsTable . '.ID')
                ->where('pm.meta_key', $metaKey)
                ->whereRaw('CAST(pm.meta_value AS DECIMAL(15,2)) ' . $operator . ' ?', [$value]);
        });
    }

    /**
     * @return list<int>
     */
    private static function wpIdsLinkedToLaravel(): array
    {
        return Voyage::query()
            ->whereNotNull('wp_post_id')
            ->where('wp_post_id', '>', 0)
            ->pluck('wp_post_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    private static function wpIdsWithDepartures(): array
    {
        return Voyage::query()
            ->whereNotNull('wp_post_id')
            ->where('wp_post_id', '>', 0)
            ->whereHas('departures')
            ->pluck('wp_post_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
