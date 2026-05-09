<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EconomicOffer;
use App\Models\EconomicOfferDeparture;
use App\Models\EconomicOfferRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicEconomicOfferRequestController extends Controller
{
    public function store(Request $request, string $slug): JsonResponse
    {
        $offer = EconomicOffer::query()
            ->with('departures')
            ->where('slug', $slug)
            ->whereIn('status', [
                EconomicOffer::STATUS_PUBLISHED,
                EconomicOffer::STATUS_FULL,
                EconomicOffer::STATUS_EXPIRED,
            ])
            ->firstOrFail();

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:255'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'selected_departure_date' => ['nullable', 'date'],
            'message' => ['nullable', 'string', 'max:3000'],
        ]);

        $departure = null;
        if (! empty($data['selected_departure_date'])) {
            $departure = $offer->departures
                ->first(fn (EconomicOfferDeparture $item) => optional($item->departure_date)->toDateString() === $data['selected_departure_date']);
        }

        $requestItem = EconomicOfferRequest::create([
            'offer_id' => $offer->id,
            'departure_id' => $departure?->id,
            'offer_title' => $offer->title,
            'selected_departure_date' => $data['selected_departure_date'] ?? $departure?->departure_date,
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'adults' => $data['adults'],
            'children' => $data['children'] ?? 0,
            'message' => $data['message'] ?? null,
            'status' => EconomicOfferRequest::STATUS_NEW,
            'source' => 'wordpress',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Votre demande a ete enregistree. Notre equipe vous contactera rapidement.',
            'data' => [
                'id' => $requestItem->id,
                'status' => $requestItem->status,
                'status_label' => $requestItem->status_label,
            ],
        ], 201);
    }
}
