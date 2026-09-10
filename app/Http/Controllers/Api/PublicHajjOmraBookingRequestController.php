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
use Illuminate\Validation\ValidationException;

class PublicHajjOmraBookingRequestController extends Controller
{
    public function store(Request $request, string $slug): JsonResponse
    {
        $package = HajjOmraPackage::query()
            ->with(['departures', 'formulas.tariffs', 'formulas.stays', 'roomPrices'])
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
            'formula_id' => ['nullable', 'integer'],
            'tariff_id' => ['nullable', 'integer'],
            'departure_id' => ['nullable', 'integer'],
            'locale' => ['nullable', Rule::in(['fr', 'ar'])],
            'message' => ['nullable', 'string', 'max:3000'],
        ]);

        $formula = null;
        $tariff = null;
        $invalid = static function (string $field) use ($data): void {
            throw ValidationException::withMessages([$field => ($data['locale'] ?? 'fr') === 'ar'
                ? 'هذا الاختيار غير متاح لهذا العرض. يرجى تحديث الصفحة والمحاولة مجدداً.'
                : 'Cette sélection n’est plus disponible pour cette offre. Actualisez la page et choisissez à nouveau.']);
        };
        if ($package->formulas->isNotEmpty()) {
            $formula = $package->formulas->firstWhere('id', $data['formula_id'] ?? null);
            if ($package->status !== HajjOmraPackage::STATUS_PUBLISHED || ! $formula || ! $formula->is_active || ! ($formula->name_fr || $formula->name_ar) || $formula->stays->isEmpty()) {
                $invalid('formula_id');
            }
            $tariff = $formula->tariffs->where('is_active', true)->firstWhere('id', $data['tariff_id'] ?? null);
            if (! $tariff || $tariff->stock <= 0) { $invalid('tariff_id'); }
        } elseif (! empty($data['formula_id'])) {
            $invalid('formula_id');
        } elseif (! empty($data['tariff_id'])) {
            $tariff = $package->roomPrices->where('is_active', true)->firstWhere('id', $data['tariff_id']);
            if (! $tariff) { $invalid('tariff_id'); }
        }

        $departure = null;
        if (! empty($data['selected_departure_date'])) {
            $departure = $package->departures
                ->first(fn (HajjOmraDeparture $item) => optional($item->departure_date)->toDateString() === $data['selected_departure_date']);
        }
        if (! empty($data['departure_id'])) {
            $departure = $package->departures->firstWhere('id', $data['departure_id']);
            if (! $departure) { $invalid('departure_id'); }
        }
        if ($formula?->departure_id) {
            if ($departure && $departure->id !== $formula->departure_id) { $invalid('departure_id'); }
            $departure = $package->departures->firstWhere('id', $formula->departure_id);
        }
        if ($formula && $departure && ($departure->status !== HajjOmraPackage::STATUS_PUBLISHED || $departure->remaining_places <= 0
            || $departure->departure_date->toDateString() < now()->toDateString())) { $invalid('departure_id'); }
        if ($formula && ! $departure && $package->departures->isNotEmpty()) { $invalid('departure_id'); }
        if ($departure && ! empty($data['selected_departure_date']) && $departure->departure_date->toDateString() !== $data['selected_departure_date']) {
            $invalid('selected_departure_date');
        }

        $bookingRequest = HajjOmraBookingRequest::create([
            'package_id' => $package->id,
            'departure_id' => $departure?->id,
            'formula_id' => $formula?->id,
            'tariff_id' => $tariff?->id,
            'package_title' => $package->localized('title', $data['locale'] ?? 'fr'),
            'selected_departure_date' => $departure?->departure_date ?? $data['selected_departure_date'] ?? null,
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'adults' => $data['adults'],
            'children' => $data['children'] ?? 0,
            'room_type' => $tariff?->room_type ?? $data['room_type'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => HajjOmraBookingRequest::STATUS_NEW,
            'source' => 'wordpress',
        ]);

        return response()->json([
            'success' => true,
            'message' => ($data['locale'] ?? 'fr') === 'ar' ? 'تم تسجيل طلبكم. سيتواصل معكم فريقنا قريباً.' : 'Votre demande a été enregistrée. Notre équipe vous contactera rapidement.',
            'data' => [
                'id' => $bookingRequest->id,
                'status' => $bookingRequest->status,
                'status_label' => $bookingRequest->status_label,
            ],
        ], 201);
    }
}
