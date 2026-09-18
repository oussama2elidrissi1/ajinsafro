<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TourTransfer;
use App\Models\Wp\WpPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TourTransferController extends Controller
{
    /**
     * Liste des tours avec lien vers la gestion des transferts (arrival + departure par tour).
     */
    /**
     * Etat de couverture d'un circuit : complet, partiel ou manquant.
     */
    private function coverage(array $directions): string
    {
        $hasArrival = in_array(TourTransfer::DIRECTION_ARRIVAL, $directions, true);
        $hasDeparture = in_array(TourTransfer::DIRECTION_DEPARTURE, $directions, true);

        if ($hasArrival && $hasDeparture) {
            return 'complet';
        }

        return ($hasArrival || $hasDeparture) ? 'partiel' : 'manquant';
    }

    /**
     * Liste des circuits avec leurs transferts aller / retour.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $etat = (string) $request->query('etat', 'tous');
        $etat = in_array($etat, ['manquant', 'partiel', 'complet'], true) ? $etat : 'tous';
        $editId = (int) $request->query('edit', 0);

        $wpConnectionFailed = false;
        $stats = ['total' => 0, 'complet' => 0, 'a_definir' => 0];
        $transfersByTour = [];

        try {
            // Couverture de tout le catalogue : le filtre d'etat et les
            // compteurs ne peuvent pas se contenter de la page affichee.
            $coverageByTour = TourTransfer::query()
                ->select('tour_id', 'direction')
                ->distinct()
                ->get()
                ->groupBy('tour_id')
                ->map(fn ($rows) => $this->coverage($rows->pluck('direction')->all()));

            $completIds = $coverageByTour->filter(fn ($c) => 'complet' === $c)->keys()->all();
            $partielIds = $coverageByTour->filter(fn ($c) => 'partiel' === $c)->keys()->all();

            $query = WpPost::tours();

            if ('' !== $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('post_title', 'like', '%'.$search.'%');
                    if (ctype_digit($search)) {
                        $inner->orWhere('ID', (int) $search);
                    }
                });
            }

            if ('complet' === $etat) {
                $query->whereIn('ID', $completIds ?: [0]);
            } elseif ('partiel' === $etat) {
                $query->whereIn('ID', $partielIds ?: [0]);
            } elseif ('manquant' === $etat) {
                $query->whereNotIn('ID', array_merge($completIds, $partielIds) ?: [0]);
            }

            $tours = $query->orderByDesc('ID')->paginate(20)->withQueryString();

            $totalTours = WpPost::tours()->count();
            $stats = [
                'total' => $totalTours,
                'complet' => count($completIds),
                'a_definir' => max(0, $totalTours - count($completIds)),
            ];

            // Une requete pour toute la page, puis le premier transfert de
            // chaque sens : la vue affiche une ligne par circuit.
            $rows = TourTransfer::query()
                ->whereIn('tour_id', $tours->pluck('ID')->all())
                ->orderByRaw('COALESCE(day_number, 1) ASC')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->groupBy('tour_id');

            foreach ($tours->pluck('ID')->all() as $tid) {
                $forTour = $rows->get($tid, collect());
                $arrival = $forTour->firstWhere('direction', TourTransfer::DIRECTION_ARRIVAL);
                $departure = $forTour->firstWhere('direction', TourTransfer::DIRECTION_DEPARTURE);

                $transfersByTour[$tid] = [
                    'arrival' => $arrival,
                    'departure' => $departure,
                    'total' => $forTour->count(),
                    'coverage' => $this->coverage(
                        $forTour->pluck('direction')->unique()->all()
                    ),
                ];
            }
        } catch (\Throwable $e) {
            \Log::warning('TourTransferController@index: WP connection failed', ['error' => $e->getMessage()]);
            $wpConnectionFailed = true;
            $tours = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1, ['path' => request()->url()]);
        }

        return view('admin.circuits.tour-transfers.index', compact(
            'tours',
            'transfersByTour',
            'wpConnectionFailed',
            'stats',
            'search',
            'etat',
            'editId'
        ));
    }

    /**
     * Formulaire d'édition des transferts du tour (arrival = Jour 1, departure = Dernier jour).
     */
    public function edit(int $tourId): View
    {
        $tour = WpPost::tours()->where('ID', $tourId)->firstOrFail();
        $transfers = TourTransfer::getForTour($tourId);
        // getForTour renvoie deux collections : ce formulaire edite le premier
        // transfert de chaque sens, comme l'onglet transferts du voyage.
        $arrival = $transfers['arrival']->first();
        $departure = $transfers['departure']->first();

        return view('admin.circuits.tour-transfers.edit', compact('tour', 'arrival', 'departure'));
    }

    /**
     * Enregistrer les deux transferts (arrival + departure).
     */
    public function update(Request $request, int $tourId): RedirectResponse
    {
        $tour = WpPost::tours()->where('ID', $tourId)->firstOrFail();

        $request->validate([
            'arrival.from_label' => 'nullable|string|max:255',
            'arrival.to_label' => 'nullable|string|max:255',
            'arrival.pickup_time' => 'nullable|string|max:20',
            'arrival.dropoff_time' => 'nullable|string|max:20',
            'arrival.vehicle_type' => 'nullable|string|max:255',
            'arrival.notes' => 'nullable|string|max:2000',
            'departure.from_label' => 'nullable|string|max:255',
            'departure.to_label' => 'nullable|string|max:255',
            'departure.pickup_time' => 'nullable|string|max:20',
            'departure.dropoff_time' => 'nullable|string|max:20',
            'departure.vehicle_type' => 'nullable|string|max:255',
            'departure.notes' => 'nullable|string|max:2000',
        ]);

        $arrivalData = [
            'from_label' => $request->input('arrival.from_label'),
            'to_label' => $request->input('arrival.to_label'),
            'pickup_time' => $request->input('arrival.pickup_time'),
            'dropoff_time' => $request->input('arrival.dropoff_time'),
            'vehicle_type' => $request->input('arrival.vehicle_type'),
            'notes' => $request->input('arrival.notes'),
        ];
        $departureData = [
            'from_label' => $request->input('departure.from_label'),
            'to_label' => $request->input('departure.to_label'),
            'pickup_time' => $request->input('departure.pickup_time'),
            'dropoff_time' => $request->input('departure.dropoff_time'),
            'vehicle_type' => $request->input('departure.vehicle_type'),
            'notes' => $request->input('departure.notes'),
        ];

        // « Le retour reprend l'aller inversé si vous le laissez vide. »
        if ('' === trim((string) $departureData['from_label']) && '' === trim((string) $departureData['to_label'])) {
            $departureData['from_label'] = $arrivalData['to_label'];
            $departureData['to_label'] = $arrivalData['from_label'];
        }

        $transfers = TourTransfer::getForTour($tourId);
        $arrival = $transfers['arrival']->first();
        $departure = $transfers['departure']->first();

        if ($arrival) {
            $arrival->update($arrivalData);
        } else {
            TourTransfer::create(array_merge($arrivalData, [
                'tour_id' => $tourId,
                'direction' => TourTransfer::DIRECTION_ARRIVAL,
            ]));
        }

        if ($departure) {
            $departure->update($departureData);
        } else {
            TourTransfer::create(array_merge($departureData, [
                'tour_id' => $tourId,
                'direction' => TourTransfer::DIRECTION_DEPARTURE,
            ]));
        }

        return redirect()
            ->route('admin.circuits.tour-transfers.index', $request->only('q', 'etat', 'page'))
            ->with('success', 'Transferts du circuit enregistrés.');
    }

    /**
     * Definir le meme aller / retour sur plusieurs circuits a la fois.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tour_ids' => 'required|array|min:1',
            'tour_ids.*' => 'integer',
            'arrival_from_label' => 'required|string|max:255',
            'arrival_to_label' => 'required|string|max:255',
            'departure_from_label' => 'nullable|string|max:255',
            'departure_to_label' => 'nullable|string|max:255',
        ]);

        $arrivalData = [
            'from_label' => trim($validated['arrival_from_label']),
            'to_label' => trim($validated['arrival_to_label']),
        ];

        $departureFrom = trim((string) ($validated['departure_from_label'] ?? ''));
        $departureTo = trim((string) ($validated['departure_to_label'] ?? ''));
        if ('' === $departureFrom && '' === $departureTo) {
            $departureFrom = $arrivalData['to_label'];
            $departureTo = $arrivalData['from_label'];
        }
        $departureData = ['from_label' => $departureFrom, 'to_label' => $departureTo];

        $tourIds = array_values(array_unique(array_map('intval', $validated['tour_ids'])));
        // On n'ecrit que sur des circuits reels : un id fabrique est ignore.
        $tourIds = WpPost::tours()->whereIn('ID', $tourIds)->pluck('ID')->all();
        $applied = 0;

        foreach ($tourIds as $tourId) {
            $transfers = TourTransfer::getForTour((int) $tourId);

            foreach ([
                [TourTransfer::DIRECTION_ARRIVAL, 'arrival', $arrivalData],
                [TourTransfer::DIRECTION_DEPARTURE, 'departure', $departureData],
            ] as [$direction, $key, $data]) {
                $existing = $transfers[$key]->first();
                if ($existing) {
                    $existing->update($data);
                } else {
                    TourTransfer::create(array_merge($data, [
                        'tour_id' => (int) $tourId,
                        'direction' => $direction,
                    ]));
                }
            }

            $applied++;
        }

        return redirect()
            ->route('admin.circuits.tour-transfers.index')
            ->with('success', $applied > 1
                ? $applied.' circuits mis à jour.'
                : $applied.' circuit mis à jour.');
    }

    /**
     * Créer un nouveau transfert depuis le drawer (AJAX).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tour_id' => 'required|integer',
            'direction' => 'required|in:arrival,departure',
            'from_label' => 'nullable|string|max:255',
            'to_label' => 'nullable|string|max:255',
            'pickup_time' => 'nullable|string|max:20',
            'dropoff_time' => 'nullable|string|max:20',
            'vehicle_type' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'day_number' => 'nullable|integer|min:1',
        ]);

        $transfer = TourTransfer::create($validated);

        return response()->json([
            'success' => true,
            'message' => __('Transfert créé avec succès.'),
            'transfer' => [
                'id' => $transfer->id,
                'direction' => $transfer->direction,
                'from_label' => $transfer->from_label ?? '',
                'to_label' => $transfer->to_label ?? '',
                'pickup_time' => $transfer->pickup_time ?? '',
                'dropoff_time' => $transfer->dropoff_time ?? '',
                'vehicle_type' => $transfer->vehicle_type ?? '',
                'notes' => $transfer->notes ?? '',
                'day_number' => $transfer->day_number ?? null,
                'is_optional' => $transfer->is_optional ?? false,
            ],
        ]);
    }
}
