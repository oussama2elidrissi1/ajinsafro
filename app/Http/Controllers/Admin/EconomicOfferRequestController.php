<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EconomicOffer;
use App\Models\EconomicOfferRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EconomicOfferRequestController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'status' => trim((string) $request->input('status', '')),
        ];

        $query = EconomicOfferRequest::query()->with(['offer', 'departure']);

        if ($filters['q'] !== '') {
            $query->where(function ($builder) use ($filters) {
                $builder
                    ->where('full_name', 'like', '%'.$filters['q'].'%')
                    ->orWhere('phone', 'like', '%'.$filters['q'].'%')
                    ->orWhere('email', 'like', '%'.$filters['q'].'%')
                    ->orWhere('offer_title', 'like', '%'.$filters['q'].'%');
            });
        }

        if ($filters['status'] !== '' && in_array($filters['status'], EconomicOfferRequest::STATUSES, true)) {
            $query->where('status', $filters['status']);
        }

        return view('admin.economic-offers.requests.index', [
            'requests' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $filters,
            'statusOptions' => EconomicOfferRequest::statusOptions(),
            'offers' => EconomicOffer::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function show(EconomicOfferRequest $requestItem): View
    {
        $requestItem->load(['offer', 'departure']);

        return view('admin.economic-offers.requests.show', [
            'requestItem' => $requestItem,
            'statusOptions' => EconomicOfferRequest::statusOptions(),
            'offers' => EconomicOffer::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function update(Request $request, EconomicOfferRequest $requestItem): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(EconomicOfferRequest::STATUSES)],
            'offer_id' => ['nullable', 'integer', 'exists:economic_offers,id'],
            'responsible_agent' => ['nullable', 'string', 'max:120'],
            'internal_notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $requestItem->fill($data);
        if ($requestItem->offer_id) {
            $requestItem->offer_title = EconomicOffer::query()->whereKey($requestItem->offer_id)->value('title');
        }

        if ($requestItem->status === EconomicOfferRequest::STATUS_CONTACTED && $requestItem->contacted_at === null) {
            $requestItem->contacted_at = now();
        }
        if ($requestItem->status === EconomicOfferRequest::STATUS_CONFIRMED && $requestItem->confirmed_at === null) {
            $requestItem->confirmed_at = now();
        }
        if ($requestItem->status === EconomicOfferRequest::STATUS_CANCELLED && $requestItem->cancelled_at === null) {
            $requestItem->cancelled_at = now();
        }

        $requestItem->save();

        return redirect()
            ->route('admin.economic-offers.requests.show', $requestItem)
            ->with('success', 'Demande client mise a jour.');
    }
}
