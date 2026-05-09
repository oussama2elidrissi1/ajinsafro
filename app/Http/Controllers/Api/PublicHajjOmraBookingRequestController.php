<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HajjOmraBookingRequest;
use App\Models\HajjOmraDeparture;
use App\Models\HajjOmraPackage;
use App\Models\HajjOmraRoomPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicHajjOmraBookingRequestController extends Controller
{
    public function store(Request $request, string $slug): JsonResponse
    {
        $package = HajjOmraPackage::query()
            ->with('departures')
            ->where('slug', $slug)
            ->whereIn('status', [
                HajjOmraPackage::STATUS_PUBLISHED,
                HajjOmraPackage::STATUS_FULL,
                HajjOmraPackage::STATUS_EXPIRED,
            ])
            ->firstOrFail();

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:255'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'room_type' => ['nullable', Rule::in(HajjOmraRoomPrice::ROOM_TYPES)],
            'selected_departure_date' => ['nullable', 'date'],
            'message' => ['nullable', 'string', 'max:3000'],
        ]);

        $departure = null;
        if (! empty($data['selected_departure_date'])) {
            $departure = $package->departures
                ->first(fn (HajjOmraDeparture $item) => optional($item->departure_date)->toDateString() === $data['selected_departure_date']);
        }

        $bookingRequest = HajjOmraBookingRequest::create([
            'package_id' => $package->id,
            'departure_id' => $departure?->id,
            'package_title' => $package->title,
            'selected_departure_date' => $data['selected_departure_date'] ?? $departure?->departure_date,
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'adults' => $data['adults'],
            'children' => $data['children'] ?? 0,
            'room_type' => $data['room_type'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => HajjOmraBookingRequest::STATUS_NEW,
            'source' => 'wordpress',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Votre demande a ete enregistree. Notre equipe vous contactera rapidement.',
            'data' => [
                'id' => $bookingRequest->id,
                'status' => $bookingRequest->status,
                'status_label' => $bookingRequest->status_label,
            ],
        ], 201);
    }
}
