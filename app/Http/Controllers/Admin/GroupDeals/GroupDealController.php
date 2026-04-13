<?php

namespace App\Http\Controllers\Admin\GroupDeals;

use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\GroupDealParticipant;
use App\Models\GroupDealPricingTier;
use App\Models\Voyage;
use App\Services\GroupDeals\GroupDealService;
use Illuminate\Http\Request;

class GroupDealController extends Controller
{
    public function __construct(protected GroupDealService $service) {}

    // ─── Voyages ────────────────────────────────────────────────────────────────

    /**
     * /admin/group-deals/trips — liste des voyages avec Group Deal activé.
     */
    public function tripsIndex(Request $request)
    {
        $query = Voyage::where('is_group_deal', true)
            ->with(['pricingTiers', 'departures' => fn ($q) => $q->where('group_deal_enabled', true)])
            ->orderByDesc('updated_at');

        if ($search = $request->input('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $voyages = $query->paginate(20)->withQueryString();

        return view('admin.group-deals.trips.index', compact('voyages'));
    }

    /**
     * /admin/group-deals/trips/{voyage} — fiche d'un voyage Group Deal.
     */
    public function tripsShow(Voyage $voyage)
    {
        $voyage->load([
            'pricingTiers',
            'departures' => fn ($q) => $q->where('group_deal_enabled', true)->withCount([
                'groupDealParticipants as confirmed_count' => fn ($q) => $q->where('status', 'confirmed'),
            ])->orderBy('start_date'),
        ]);

        return view('admin.group-deals.trips.show', compact('voyage'));
    }

    /**
     * POST /admin/group-deals/trips/{voyage}/tiers — créer un palier.
     */
    public function tierStore(Request $request, Voyage $voyage)
    {
        $data = $request->validate([
            'min_participants' => 'required|integer|min:1|max:999',
            'price_per_person' => 'required|numeric|min:0',
            'label'            => 'nullable|string|max:100',
        ]);

        GroupDealPricingTier::create(array_merge($data, ['voyage_id' => $voyage->id]));

        return back()->with('success', 'Palier ajouté.');
    }

    /**
     * PUT /admin/group-deals/trips/{voyage}/tiers/{tier} — modifier un palier.
     */
    public function tierUpdate(Request $request, Voyage $voyage, GroupDealPricingTier $tier)
    {
        abort_if($tier->voyage_id !== $voyage->id, 403);

        $data = $request->validate([
            'min_participants' => 'required|integer|min:1|max:999',
            'price_per_person' => 'required|numeric|min:0',
            'label'            => 'nullable|string|max:100',
        ]);

        $tier->update($data);

        return back()->with('success', 'Palier mis à jour.');
    }

    /**
     * DELETE /admin/group-deals/trips/{voyage}/tiers/{tier} — supprimer un palier.
     */
    public function tierDestroy(Voyage $voyage, GroupDealPricingTier $tier)
    {
        abort_if($tier->voyage_id !== $voyage->id, 403);
        $tier->delete();

        return back()->with('success', 'Palier supprimé.');
    }

    // ─── Départs ────────────────────────────────────────────────────────────────

    /**
     * /admin/group-deals/departures — liste de tous les départs Group Deal.
     */
    public function departuresIndex(Request $request)
    {
        $query = Departure::where('group_deal_enabled', true)
            ->with('voyage:id,name,destination')
            ->withCount([
                'groupDealParticipants as confirmed_count' => fn ($q) => $q->where('status', 'confirmed'),
            ])
            ->orderBy('start_date');

        if ($voyageId = $request->input('voyage_id')) {
            $query->where('voyage_id', $voyageId);
        }

        if ($request->input('guaranteed') === '1') {
            $query->where('is_guaranteed', true);
        } elseif ($request->input('guaranteed') === '0') {
            $query->where('is_guaranteed', false);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $departures = $query->paginate(25)->withQueryString();

        $voyageOptions = Voyage::where('is_group_deal', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.group-deals.departures.index', compact('departures', 'voyageOptions'));
    }

    /**
     * /admin/group-deals/departures/{departure} — détail d'un départ.
     */
    public function departuresShow(Departure $departure)
    {
        abort_if(! $departure->group_deal_enabled, 404);

        $departure->load([
            'voyage:id,name,destination',
            'groupDealParticipants' => fn ($q) => $q->with('client:id,first_name,last_name,email')->orderBy('joined_at'),
        ]);

        $stats = $this->service->departureStats($departure);

        return view('admin.group-deals.departures.show', compact('departure', 'stats'));
    }

    /**
     * POST /admin/group-deals/departures/{departure}/recalculate — recalcul manuel.
     */
    public function departuresRecalculate(Departure $departure)
    {
        abort_if(! $departure->group_deal_enabled, 404);
        $this->service->recalculate($departure);

        return back()->with('success', 'Prix et statut garanti recalculés.');
    }

    /**
     * POST /admin/group-deals/departures/{departure}/participant — ajouter un participant depuis l'admin.
     */
    public function participantStore(Request $request, Departure $departure)
    {
        abort_if(! $departure->group_deal_enabled, 404);

        $data = $request->validate([
            'client_id'      => 'required|exists:clients,id',
            'reservation_id' => 'nullable|exists:reservations,id',
        ]);

        $client = \App\Models\Client::findOrFail($data['client_id']);

        try {
            $this->service->addParticipant($departure, $client, $data['reservation_id'] ?? null);
            return back()->with('success', 'Participant ajouté.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['client_id' => $e->getMessage()]);
        }
    }

    /**
     * PATCH /admin/group-deals/departures/{departure}/participants/{participant} — changer statut.
     */
    public function participantUpdate(Request $request, Departure $departure, GroupDealParticipant $participant)
    {
        abort_if($participant->departure_id !== $departure->id, 403);

        $data = $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled',
        ]);

        $participant->update($data);
        $this->service->recalculate($departure->fresh());

        return back()->with('success', 'Statut mis à jour.');
    }
}
