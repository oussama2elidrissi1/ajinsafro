<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\GroupDeal;
use App\Models\GroupDealParticipant;
use App\Services\GroupDeals\GroupDealService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupDealsController extends Controller
{
    public function __construct(private GroupDealService $service)
    {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));
        $destination = trim((string) $request->input('destination', ''));
        $status = trim((string) $request->input('status', ''));
        $category = trim((string) $request->input('category', ''));

        $query = GroupDeal::query()
            ->with(['priceTiers', 'services', 'categories'])
            ->where('is_active', true)
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('departure_date');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', '%' . $search . '%')
                    ->orWhere('destination', 'like', '%' . $search . '%')
                    ->orWhere('country', 'like', '%' . $search . '%')
                    ->orWhere('city', 'like', '%' . $search . '%')
                    ->orWhere('short_description', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($destination !== '') {
            $query->where('destination', $destination);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($category !== '') {
            $query->whereHas('categories', fn ($builder) => $builder->where('slug', $category));
        }

        $deals = $query->paginate(9)->withQueryString();
        $deals->getCollection()->transform(fn (GroupDeal $deal) => $this->service->syncOfferMetrics($deal));

        $destinations = GroupDeal::query()
            ->where('is_active', true)
            ->whereNotNull('destination')
            ->where('destination', '!=', '')
            ->orderBy('destination')
            ->distinct()
            ->pluck('destination');

        $categories = \App\Models\GroupDealCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['name', 'slug']);

        return view('group-deals.index', [
            'deals' => $deals,
            'destinations' => $destinations,
            'categories' => $categories,
            'filters' => [
                'q' => $search,
                'destination' => $destination,
                'status' => $status,
                'category' => $category,
            ],
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $groupDeal = GroupDeal::query()
            ->with([
                'priceTiers',
                'services',
                'categories',
                'participants' => fn ($query) => $query
                    ->whereIn('status', [
                        GroupDealParticipant::STATUS_PENDING,
                        GroupDealParticipant::STATUS_CONFIRMED,
                        GroupDealParticipant::STATUS_PAID,
                    ])
                    ->orderByDesc('created_at'),
            ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $client = null;
        if ($request->user()) {
            $client = Client::query()->where('user_id', $request->user()->id)->first();
        }

        $groupDeal = $this->service->syncOfferMetrics($groupDeal);
        $stats = $this->service->offerStats($groupDeal);
        $shareUrl = $this->service->shareUrlForDeal(
            $groupDeal,
            $client?->user_id ?: ($request->query('ref') ? (int) $request->query('ref') : null)
        );

        return view('group-deals.show', [
            'groupDeal' => $groupDeal,
            'stats' => $stats,
            'shareUrl' => $shareUrl,
            'client' => $client,
            'shareRef' => (int) $request->query('ref', 0),
        ]);
    }

    public function participate(Request $request, string $slug): RedirectResponse
    {
        $groupDeal = GroupDeal::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();

        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:60',
            'email' => 'required|email|max:255',
            'participants_count' => 'required|integer|min:1|max:10000',
        ]);

        $client = null;
        $user = $request->user();
        if ($user) {
            $client = Client::query()->where('user_id', $user->id)->first();
        }

        $payload = array_merge($data, [
            'client_id' => $client?->id,
            'user_id' => $user?->id,
            'status' => $user ? GroupDealParticipant::STATUS_CONFIRMED : GroupDealParticipant::STATUS_PENDING,
            'payment_status' => GroupDealParticipant::PAYMENT_PENDING,
        ]);

        try {
            $participant = $this->service->registerPublicParticipant($groupDeal, $payload);
        } catch (\RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('front.group-deals.show', $groupDeal->slug)
            ->with('success', sprintf('Participation enregistree pour %s.', $participant->full_name ?: $groupDeal->title));
    }
}
