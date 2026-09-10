<?php

namespace App\Services\HajjOmra;

use App\Models\HajjOmraFormula;
use App\Models\HajjOmraPackage;
use App\Models\HajjOmraRoomPrice;

/** One structured commercial payload for the API and the admin preview. */
class HajjOmraCommercialPresenter
{
    public const RELATIONS = ['formulas.departure', 'formulas.tariffs', 'formulas.stays.hotel', 'formulas.stays.programDay', 'hotels', 'serviceItems', 'roomPrices', 'departures', 'programDays'];

    public function package(HajjOmraPackage $package): array
    {
        $package->loadMissing(self::RELATIONS);
        $translations = [];
        foreach ($package->bilingualFields() as $field) {
            $translations[$field.'_fr'] = $package->{$field};
            $translations[$field.'_ar'] = $package->{$field.'_ar'};
        }
        $formulas = $package->formulas->filter(fn ($formula) => $formula->is_active && ($formula->name_fr || $formula->name_ar)
            && $formula->stays->isNotEmpty() && $formula->tariffs->where('is_active', true)->isNotEmpty())
            ->map(fn ($formula) => $this->formula($formula))->values();
        return array_merge($translations, [
            'has_formulas' => $package->formulas->isNotEmpty(),
            'formulas' => $formulas->all(),
            'departures' => $package->departures->map(function ($departure) use ($formulas, $package) {
                $prices = $formulas->filter(fn ($formula) => ! $formula['departure_id'] || $formula['departure_id'] === $departure->id)
                    ->flatMap(fn ($formula) => array_values($formula['prices']))->pluck('price');
                $priceFrom = $package->formulas->isNotEmpty() ? ($prices->isEmpty() ? null : (float) $prices->min())
                    : ($package->roomPrices->isNotEmpty() ? $package->price_from_value : ($departure->price_from !== null ? (float) $departure->price_from : $package->price_from_value));
                return ['id' => $departure->id, 'departure_date' => $departure->departure_date?->toDateString(),
                    'return_date' => $departure->return_date?->toDateString(), 'departure_city' => $departure->departure_city,
                    'status' => $departure->status, 'status_label' => $departure->status_label,
                    'available_places' => $departure->available_places, 'reserved_places' => $departure->reserved_places,
                    'remaining_places' => $departure->remaining_places, 'price_from' => $priceFrom];
            })->values()->all(),
            'hotels' => $package->hotels->map(fn ($hotel) => $this->hotel($hotel))->values()->all(),
            'room_prices' => $package->roomPrices->where('is_active', true)->map(fn ($tariff) => $this->tariff($tariff))->values()->all(),
            'program_days' => $package->programDays->map(fn ($day) => [
                'id' => $day->id, 'day_number' => $day->day_number,
                'title' => $day->title, 'title_ar' => $day->title_ar,
                'description' => $day->description, 'description_ar' => $day->description_ar,
                'city' => $day->city, 'image_url' => $day->image_url,
            ])->values()->all(),
            'included_items_ar' => $package->serviceItems->where('kind', 'included')->map(fn ($item) => $item->label_ar ?: $item->label)->filter()->values()->all(),
            'excluded_items_ar' => $package->serviceItems->where('kind', 'excluded')->map(fn ($item) => $item->label_ar ?: $item->label)->filter()->values()->all(),
        ]);
    }

    public function formula(HajjOmraFormula $formula): array
    {
        $offset = 0;
        $stays = [];
        foreach ($formula->stays as $stay) {
            if (! $stay->hotel) { continue; }
            $nights = $stay->nights_override ?? $stay->hotel->nights;
            $startDay = $stay->programDay ? max(0, $stay->programDay->day_number - 1) : $offset;
            $start = $startDay !== null ? $formula->departure?->departure_date?->copy()->addDays($startDay) : null;
            $end = $start && $nights !== null ? $start->copy()->addDays($nights) : null;
            $stays[] = array_merge($this->hotel($stay->hotel), [
                'stay_id' => $stay->id, 'nights' => $nights,
                'program_day_id' => $stay->program_day_id,
                'start_day' => $startDay !== null ? $startDay + 1 : null,
                'stay_start_date' => $start?->toDateString(), 'stay_end_date' => $end?->toDateString(),
            ]);
            $offset = $startDay !== null && $nights !== null ? $startDay + $nights : null;
        }
        return [
            'id' => $formula->id, 'name_fr' => $formula->name_fr, 'name_ar' => $formula->name_ar,
            'description_fr' => $formula->description_fr, 'description_ar' => $formula->description_ar,
            'departure_id' => $formula->departure_id,
            'departure_date' => $formula->departure?->departure_date?->toDateString(),
            'return_date' => $formula->departure?->return_date?->toDateString(),
            'sort_order' => $formula->sort_order,
            'accommodations' => $stays,
            'prices' => $formula->tariffs->where('is_active', true)->mapWithKeys(fn ($tariff) => [$tariff->room_type => $this->tariff($tariff)])->all(),
        ];
    }

    private function hotel($hotel): array
    {
        return [
            'id' => $hotel->id, 'city' => $hotel->city, 'name' => $hotel->name, 'name_ar' => $hotel->name_ar,
            'stars' => $hotel->stars, 'location' => $hotel->location, 'location_ar' => $hotel->location_ar,
            'haram_distance' => $hotel->haram_distance, 'nights' => $hotel->nights,
            'meal_plan' => $hotel->meal_plan,
            'description' => $hotel->description, 'description_ar' => $hotel->description_ar, 'image_url' => $hotel->image_url,
        ];
    }

    private function tariff(HajjOmraRoomPrice $tariff): array
    {
        return ['id' => $tariff->id, 'tariff_id' => $tariff->id, 'room_type' => $tariff->room_type,
            'room_type_label' => $tariff->room_type_label,
            'room_type_label_ar' => HajjOmraRoomPrice::roomTypeOptions('ar')[$tariff->room_type] ?? $tariff->room_type_label,
            'price' => (float) $tariff->price, 'old_price' => $tariff->old_price !== null ? (float) $tariff->old_price : null,
            'stock' => $tariff->stock, 'capacity' => $tariff->capacity, 'is_active' => $tariff->is_active];
    }
}
