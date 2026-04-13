<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Voyage;
use App\Services\GroupDealParticipationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupDealsController extends Controller
{
    private const VISIBLE_STATUSES = ['actif', 'published', 'active', 'publish'];

    public function __construct(private GroupDealParticipationService $participationService)
    {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));
        $destination = trim((string) $request->input('destination', ''));
        $groupSize = max(2, (int) $request->input('group_size', 6));

        $dealsQuery = Voyage::query()
            ->whereIn('status', self::VISIBLE_STATUSES)
            ->where('is_group_deal', true)
            ->with(['departures' => function ($q) {
                // Eager load tous les départs pertinents pour éviter N+1
                // Le service choisira le meilleur
                $q->whereIn('status', ['open', 'full'])
                    ->where('group_deal_enabled', true)
                    ->with(['reservations' => function ($rq) {
                        // Eager load les réservations confirmées avec leurs passagers
                        $rq->whereIn('status', ['confirmed', 'partially_paid', 'paid'])
                            ->with('passengers');
                    }]);
            }]);

        if ($search !== '') {
            $like = '%'.$search.'%';
            $dealsQuery->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                    ->orWhere('destination', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        if ($destination !== '') {
            $dealsQuery->where('destination', $destination);
        }

        $deals = $dealsQuery
            ->orderByDesc('updated_at')
            ->select([
                'id',
                'name',
                'slug',
                'destination',
                'duration_text',
                'price_from',
                'currency',
                'featured_image',
                'accroche',
                'min_people',
                'max_people',
            ])
            ->paginate(9)
            ->withQueryString();

        // Enrichir chaque deal avec les métriques de participation
        // Utiliser le service pour sélectionner le meilleur départ et calculer les métriques
        $deals->getCollection()->transform(function (Voyage $voyage) {
            $metrics = $this->participationService->getMetricsForVoyage($voyage);

            if ($metrics) {
                // Métriques trouvées depuis un vrai départ
                $voyage->groupDealMetrics = $metrics;
            } else {
                // Fallback : voyage sans départ ou sans groupe disponible
                // Afficher 0 capacité = bloc ne s'affiche pas
                $voyage->groupDealMetrics = [
                    'total_capacity'        => 0,
                    'confirmed_count'       => 0,
                    'remaining_capacity'    => 0,
                    'minimum_to_guarantee'  => 0,
                    'missing_to_guarantee'  => 0,
                    'progress_percent'      => 0,
                    'is_guaranteed'         => false,
                    'is_full'               => false,
                    'is_almost_full'        => false,
                    'remaining_places'      => 0,
                    'status_label'          => 'Non disponible',
                ];
            }

            return $voyage;
        });

        $destinations = Voyage::query()
            ->whereIn('status', self::VISIBLE_STATUSES)
            ->where('is_group_deal', true)
            ->whereNotNull('destination')
            ->where('destination', '!=', '')
            ->distinct()
            ->orderBy('destination')
            ->pluck('destination')
            ->values();

        return view('group-deals.index', [
            'deals' => $deals,
            'destinations' => $destinations,
            'filters' => [
                'q' => $search,
                'destination' => $destination,
                'group_size' => $groupSize,
            ],
        ]);
    }
}
