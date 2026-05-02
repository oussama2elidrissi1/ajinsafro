<?php

namespace App\Http\Controllers\Admin\GroupDeals;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\GroupDeal;
use App\Models\GroupDealParticipant;
use App\Models\GroupDealPricingTier;
use App\Services\GroupDeals\GroupDealService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OfferController extends Controller
{
    public function __construct(private GroupDealService $service)
    {
    }

    public function index(Request $request): View
    {
        $query = GroupDeal::query()->withCount('participants')->with('pricingTiers')->orderByDesc('updated_at');

        if ($search = trim((string) $request->input('q', ''))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', '%'.$search.'%')
                    ->orWhere('destination', 'like', '%'.$search.'%');
            });
        }

        if ($status = trim((string) $request->input('status', ''))) {
            $query->where('status', $status);
        }

        $groupDeals = $query->paginate(20)->withQueryString();

        return view('admin.group-deals.offers.index', [
            'groupDeals' => $groupDeals,
            'statuses' => GroupDeal::STATUSES,
            'filters' => $request->only(['q', 'status']),
        ]);
    }

    public function create(): View
    {
        return view('admin.group-deals.offers.create', [
            'groupDeal' => new GroupDeal([
                'status' => GroupDeal::STATUS_DRAFT,
                'share_enabled' => true,
                'min_participants' => 4,
                'max_participants' => 20,
            ]),
            'statuses' => GroupDeal::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedDealData($request);
        $data['image'] = $this->storeImageIfProvided($request, null, $data['image'] ?? null);
        $data['images'] = $this->normalizeMultilineList($request->input('images_list'));
        $data['services_included'] = $this->normalizeMultilineList($request->input('services_included_list'));
        $data['services_excluded'] = $this->normalizeMultilineList($request->input('services_excluded_list'));

        $groupDeal = GroupDeal::create($data);
        $this->syncTierRows($groupDeal, $request);
        $this->service->syncOfferMetrics($groupDeal->fresh());

        return redirect()->route('admin.group-deals.show', $groupDeal)->with('success', 'Offre Group Deal créée.');
    }

    public function show(GroupDeal $groupDeal): View
    {
        $groupDeal->load([
            'pricingTiers',
            'participants' => fn ($query) => $query->orderByDesc('created_at'),
        ]);

        $stats = $this->service->offerStats($groupDeal);

        return view('admin.group-deals.offers.show', [
            'groupDeal' => $groupDeal->fresh(['pricingTiers', 'participants']),
            'stats' => $stats,
            'clients' => Client::query()->orderBy('full_name')->limit(200)->get(['id', 'full_name', 'email', 'phone', 'user_id']),
        ]);
    }

    public function edit(GroupDeal $groupDeal): View
    {
        $groupDeal->load('pricingTiers');

        return view('admin.group-deals.offers.edit', [
            'groupDeal' => $groupDeal,
            'statuses' => GroupDeal::STATUSES,
        ]);
    }

    public function update(Request $request, GroupDeal $groupDeal): RedirectResponse
    {
        $data = $this->validatedDealData($request, $groupDeal);
        $data['image'] = $this->storeImageIfProvided($request, $groupDeal, $data['image'] ?? $groupDeal->image);
        $data['images'] = $this->normalizeMultilineList($request->input('images_list'));
        $data['services_included'] = $this->normalizeMultilineList($request->input('services_included_list'));
        $data['services_excluded'] = $this->normalizeMultilineList($request->input('services_excluded_list'));

        $groupDeal->update($data);
        $this->syncTierRows($groupDeal, $request);
        $this->service->syncOfferMetrics($groupDeal->fresh());

        return redirect()->route('admin.group-deals.show', $groupDeal)->with('success', 'Offre Group Deal mise à jour.');
    }

    public function destroy(GroupDeal $groupDeal): RedirectResponse
    {
        $groupDeal->delete();

        return redirect()->route('admin.group-deals.index')->with('success', 'Offre Group Deal supprimée.');
    }

    public function recalculate(GroupDeal $groupDeal): RedirectResponse
    {
        $this->service->syncOfferMetrics($groupDeal);

        return back()->with('success', 'Les métriques du Group Deal ont été recalculées.');
    }

    public function tierStore(Request $request, GroupDeal $groupDeal): RedirectResponse
    {
        $data = $request->validate([
            'min_participants' => 'required|integer|min:1|max:100000',
            'max_people' => 'nullable|integer|min:1|max:100000|gte:min_participants',
            'price_per_person' => 'required|numeric|min:0',
            'label' => 'nullable|string|max:120',
            'sort_order' => 'nullable|integer|min:0|max:10000',
        ]);

        $groupDeal->pricingTiers()->create($data);
        $this->service->syncOfferMetrics($groupDeal->fresh());

        return back()->with('success', 'Palier ajouté.');
    }

    public function tierUpdate(Request $request, GroupDeal $groupDeal, GroupDealPricingTier $tier): RedirectResponse
    {
        abort_if($tier->group_deal_id !== $groupDeal->id, 403);

        $data = $request->validate([
            'min_participants' => 'required|integer|min:1|max:100000',
            'max_people' => 'nullable|integer|min:1|max:100000|gte:min_participants',
            'price_per_person' => 'required|numeric|min:0',
            'label' => 'nullable|string|max:120',
            'sort_order' => 'nullable|integer|min:0|max:10000',
        ]);

        $tier->update($data);
        $this->service->syncOfferMetrics($groupDeal->fresh());

        return back()->with('success', 'Palier mis à jour.');
    }

    public function tierDestroy(GroupDeal $groupDeal, GroupDealPricingTier $tier): RedirectResponse
    {
        abort_if($tier->group_deal_id !== $groupDeal->id, 403);
        $tier->delete();
        $this->service->syncOfferMetrics($groupDeal->fresh());

        return back()->with('success', 'Palier supprimé.');
    }

    public function participantStore(Request $request, GroupDeal $groupDeal): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'full_name' => 'required_without:client_id|string|max:255',
            'phone' => 'nullable|string|max:60',
            'email' => 'nullable|email|max:255',
            'participants_count' => 'required|integer|min:1|max:10000',
            'status' => 'required|in:pending,confirmed,paid,cancelled',
            'payment_status' => 'required|in:pending,paid,cancelled',
        ]);

        $client = null;
        if (! empty($data['client_id'])) {
            $client = Client::findOrFail($data['client_id']);
            $data['full_name'] = $client->full_name ?: trim(($client->first_name ?? '').' '.($client->last_name ?? ''));
            $data['phone'] = $data['phone'] ?: $client->phone;
            $data['email'] = $data['email'] ?: $client->email;
            $data['user_id'] = $client->user_id;
            $data['client_id'] = $client->id;
        }

        $participant = $groupDeal->participants()->create([
            'client_id' => $data['client_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'full_name' => $data['full_name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'participants_count' => $data['participants_count'],
            'status' => $data['status'],
            'selected_price' => $groupDeal->current_price,
            'payment_status' => $data['payment_status'],
            'joined_at' => now(),
        ]);

        $this->service->syncOfferMetrics($groupDeal->fresh());

        return back()->with('success', sprintf('Participant %s ajouté.', $participant->full_name));
    }

    public function participantUpdate(Request $request, GroupDeal $groupDeal, GroupDealParticipant $participant): RedirectResponse
    {
        abort_if($participant->group_deal_id !== $groupDeal->id, 403);

        $data = $request->validate([
            'status' => 'required|in:pending,confirmed,paid,cancelled',
            'payment_status' => 'required|in:pending,paid,cancelled',
        ]);

        $participant->update($data);
        $this->service->syncOfferMetrics($groupDeal->fresh());

        return back()->with('success', 'Statut participant mis à jour.');
    }

    protected function validatedDealData(Request $request, ?GroupDeal $groupDeal = null): array
    {
        $uniqueSlug = 'unique:group_deals,slug';
        if ($groupDeal) {
            $uniqueSlug .= ','.$groupDeal->id;
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', $uniqueSlug],
            'destination' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'duration_days' => 'nullable|integer|min:0|max:365',
            'duration_nights' => 'nullable|integer|min:0|max:365',
            'min_participants' => 'required|integer|min:1|max:100000',
            'max_participants' => 'required|integer|min:1|max:100000|gte:min_participants',
            'status' => 'required|in:draft,published,closed,guaranteed,cancelled',
            'badge_label' => 'nullable|string|max:120',
            'registration_deadline' => 'nullable|date',
            'image' => 'nullable|string|max:2048',
            'image_file' => 'nullable|image|max:4096',
            'program' => 'nullable|string',
            'conditions' => 'nullable|string',
            'share_enabled' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $data['slug'] = filled($data['slug'] ?? null) ? Str::slug((string) $data['slug']) : Str::slug((string) $data['title']);
        $data['share_enabled'] = (bool) $request->boolean('share_enabled', true);
        $data['is_featured'] = (bool) $request->boolean('is_featured', false);
        $data['is_active'] = (bool) $request->boolean('is_active', true);

        return $data;
    }

    protected function syncTierRows(GroupDeal $groupDeal, Request $request): void
    {
        $tiers = collect($request->input('tiers', []))
            ->map(fn ($row) => is_array($row) ? $row : [])
            ->filter(fn (array $row) => filled($row['min_participants'] ?? null) && filled($row['price_per_person'] ?? null))
            ->values();

        $groupDeal->pricingTiers()->delete();

        if ($tiers->isEmpty()) {
            return;
        }

        $validated = validator(['tiers' => $tiers->all()], [
            'tiers' => 'array',
            'tiers.*.min_participants' => 'required|integer|min:1|max:100000',
            'tiers.*.max_people' => 'nullable|integer|min:1|max:100000',
            'tiers.*.price_per_person' => 'required|numeric|min:0',
            'tiers.*.label' => 'nullable|string|max:120',
            'tiers.*.sort_order' => 'nullable|integer|min:0|max:10000',
        ])->validate();

        foreach ($validated['tiers'] as $index => $tier) {
            $groupDeal->pricingTiers()->create([
                'min_participants' => $tier['min_participants'],
                'max_people' => $tier['max_people'] ?? null,
                'price_per_person' => $tier['price_per_person'],
                'label' => $tier['label'] ?? null,
                'sort_order' => $tier['sort_order'] ?? $index + 1,
            ]);
        }
    }

    protected function normalizeMultilineList($value): array
    {
        return collect(preg_split("/\r\n|\n|\r/", (string) $value))
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values()
            ->all();
    }

    protected function storeImageIfProvided(Request $request, ?GroupDeal $groupDeal, ?string $fallback): ?string
    {
        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('group-deals', 'public');

            if ($groupDeal && $groupDeal->image && ! Str::startsWith($groupDeal->image, ['http://', 'https://'])) {
                Storage::disk('public')->delete($groupDeal->image);
            }

            return $path;
        }

        return $fallback;
    }

    public function participantsIndex(Request $request): View
    {
        $query = GroupDealParticipant::query()
            ->with(['groupDeal', 'client'])
            ->orderByDesc('created_at');

        if ($search = trim((string) $request->input('q', ''))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('full_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        $participants = $query->paginate(25)->withQueryString();

        return view('admin.group-deals.participants.index', [
            'participants' => $participants,
            'filters' => $request->only(['q']),
        ]);
    }

    public function tiersIndex(Request $request): View
    {
        $query = GroupDealPricingTier::query()
            ->with(['groupDeal', 'voyage'])
            ->orderByDesc('created_at');

        if ($search = trim((string) $request->input('q', ''))) {
            $query->whereHas('groupDeal', function ($builder) use ($search) {
                $builder->where('title', 'like', '%'.$search.'%');
            });
        }

        $tiers = $query->paginate(25)->withQueryString();

        return view('admin.group-deals.tiers.index', [
            'tiers' => $tiers,
            'filters' => $request->only(['q']),
        ]);
    }
}
