<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HajjOmraBookingRequest;
use App\Models\HajjOmraPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HajjOmraBookingRequestController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'status' => trim((string) $request->input('status', '')),
            'package_id' => (int) $request->input('package_id', 0),
        ];

        $query = HajjOmraBookingRequest::query()->with(['package', 'departure']);

        if ($filters['q'] !== '') {
            $query->where(function ($builder) use ($filters) {
                $builder
                    ->where('full_name', 'like', '%'.$filters['q'].'%')
                    ->orWhere('email', 'like', '%'.$filters['q'].'%')
                    ->orWhere('phone', 'like', '%'.$filters['q'].'%');
            });
        }

        if ($filters['status'] !== '' && in_array($filters['status'], HajjOmraBookingRequest::STATUSES, true)) {
            $query->where('status', $filters['status']);
        }

        if ($filters['package_id'] > 0) {
            $query->where('package_id', $filters['package_id']);
        }

        $requests = $query
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.hajj-omra.requests.index', [
            'requests' => $requests,
            'filters' => $filters,
            'statusOptions' => HajjOmraBookingRequest::statusOptions(),
            'packages' => HajjOmraPackage::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function show(HajjOmraBookingRequest $requestItem): View
    {
        $requestItem->load(['package.departures', 'departure']);

        return view('admin.hajj-omra.requests.show', [
            'requestItem' => $requestItem,
            'statusOptions' => HajjOmraBookingRequest::statusOptions(),
            'packages' => HajjOmraPackage::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function update(Request $request, HajjOmraBookingRequest $requestItem): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(HajjOmraBookingRequest::STATUSES)],
            'package_id' => ['nullable', 'integer', 'exists:hajj_omra_packages,id'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $requestItem->status = $data['status'];
        $requestItem->package_id = $data['package_id'] ?: $requestItem->package_id;
        $requestItem->internal_notes = $data['internal_notes'] ?? null;

        if ($requestItem->package_id) {
            $requestItem->package_title = HajjOmraPackage::query()->whereKey($requestItem->package_id)->value('title');
        }

        if ($requestItem->status === HajjOmraBookingRequest::STATUS_CONTACTED && $requestItem->contacted_at === null) {
            $requestItem->contacted_at = now();
        }

        if ($requestItem->status === HajjOmraBookingRequest::STATUS_CONFIRMED && $requestItem->confirmed_at === null) {
            $requestItem->confirmed_at = now();
        }

        if ($requestItem->status === HajjOmraBookingRequest::STATUS_CANCELLED && $requestItem->cancelled_at === null) {
            $requestItem->cancelled_at = now();
        }

        $requestItem->save();

        return redirect()
            ->route('admin.hajj-omra.requests.show', $requestItem)
            ->with('success', 'Demande mise a jour.');
    }
}
