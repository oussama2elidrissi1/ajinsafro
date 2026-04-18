<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWpTourRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Optional: extra validation for voyage flights (Laravel voyage_flights).
     */
    public function withValidator(Validator $validator): void
    {
        // No strict rules: outbound/inbound are optional; cabin defaults to economy in service.
        $validator->after(function (Validator $validator): void {
            $travelDates = $this->input('travel_dates', []);
            if (is_array($travelDates)) {
                foreach (array_values($travelDates) as $index => $row) {
                    if (! is_array($row)) {
                        continue;
                    }

                    $date = trim((string) ($row['date'] ?? ''));
                    $seats = $row['seats'] ?? null;
                    if ($date !== '' && ($seats === null || $seats === '')) {
                        $validator->errors()->add("travel_dates.$index.seats", 'Le nombre de places est obligatoire pour chaque date de départ.');
                    }
                }
            }

            $departureAllocations = $this->input('departure_allocations', []);
            if (! is_array($departureAllocations)) {
                return;
            }

            $tourHotels = $this->input('tour_hotels', []);
            $tourHotels = is_array($tourHotels) ? array_values($tourHotels) : [];
            $expectedHotelIndexes = [];
            $hotelIdToIndex = [];
            foreach ($tourHotels as $hotelIndex => $hotelRow) {
                if (! is_array($hotelRow)) {
                    continue;
                }

                $hotelId = isset($hotelRow['id']) && $hotelRow['id'] !== '' ? (int) $hotelRow['id'] : 0;
                $hotelName = trim((string) ($hotelRow['hotel_name'] ?? ''));
                $address = trim((string) ($hotelRow['address'] ?? ''));
                $mealPlan = trim((string) ($hotelRow['meal_plan'] ?? ''));
                $notes = trim((string) ($hotelRow['notes'] ?? ''));
                $hasHotelPayload = $hotelName !== ''
                    || $address !== ''
                    || $mealPlan !== ''
                    || $notes !== ''
                    || ! empty($hotelRow['image_id'])
                    || ! empty($hotelRow['is_optional']);

                if (! $hasHotelPayload && $hotelId <= 0) {
                    continue;
                }

                $expectedHotelIndexes[] = (int) $hotelIndex;
                if ($hotelId > 0) {
                    $hotelIdToIndex[$hotelId] = (int) $hotelIndex;
                }
            }

            $travelDateSeatsById = [];
            $travelDateSeatsByDate = [];
            $travelDates = $this->input('travel_dates', []);
            if (is_array($travelDates)) {
                foreach (array_values($travelDates) as $travelDateRow) {
                    if (! is_array($travelDateRow)) {
                        continue;
                    }

                    $isActive = ! array_key_exists('is_active', $travelDateRow) || (bool) $travelDateRow['is_active'];
                    if (! $isActive) {
                        continue;
                    }

                    $date = trim((string) ($travelDateRow['date'] ?? ''));
                    $seats = max(0, (int) ($travelDateRow['seats'] ?? 0));
                    $travelDateId = isset($travelDateRow['id']) && $travelDateRow['id'] !== '' ? (int) $travelDateRow['id'] : 0;

                    if ($travelDateId > 0) {
                        $travelDateSeatsById[$travelDateId] = $seats;
                    }
                    if ($date !== '') {
                        $travelDateSeatsByDate[$date] = $seats;
                    }
                }
            }

            foreach ($departureAllocations as $departureIndex => $departureRow) {
                if (! is_array($departureRow)) {
                    continue;
                }

                if ($expectedHotelIndexes === []) {
                    continue;
                }

                $rooms = $departureRow['rooms'] ?? [];
                if (! is_array($rooms)) {
                    continue;
                }

                $rowTravelDateId = isset($departureRow['travel_date_id']) && $departureRow['travel_date_id'] !== '' ? (int) $departureRow['travel_date_id'] : 0;
                $rowDate = trim((string) ($departureRow['date'] ?? ''));
                $targetSeats = 0;
                if ($rowTravelDateId > 0 && array_key_exists($rowTravelDateId, $travelDateSeatsById)) {
                    $targetSeats = max(0, (int) $travelDateSeatsById[$rowTravelDateId]);
                } elseif ($rowDate !== '' && array_key_exists($rowDate, $travelDateSeatsByDate)) {
                    $targetSeats = max(0, (int) $travelDateSeatsByDate[$rowDate]);
                }

                $coverageByHotelIndex = [];
                foreach ($expectedHotelIndexes as $expectedHotelIndex) {
                    $coverageByHotelIndex[$expectedHotelIndex] = 0;
                }
                $roomsTouched = false;

                foreach ($rooms as $roomIndex => $roomRow) {
                    if (! is_array($roomRow)) {
                        continue;
                    }

                    $roomType = trim((string) ($roomRow['room_type'] ?? ''));
                    $quantity = $roomRow['quantity'] ?? null;
                    $capacity = $roomRow['capacity_per_room'] ?? null;
                    $hasPayload = $roomType !== ''
                        || ! ($quantity === null || $quantity === '')
                        || ! ($capacity === null || $capacity === '');

                    if ($hasPayload && $roomType === '') {
                        $validator->errors()->add("departure_allocations.$departureIndex.rooms.$roomIndex.room_type", 'Le type de chambre est obligatoire.');
                    }

                    if (! $hasPayload || $roomType === '') {
                        continue;
                    }

                    $roomsTouched = true;
                    $quantity = max(0, (int) ($roomRow['quantity'] ?? 0));
                    $capacityPerRoom = max(1, (int) ($roomRow['capacity_per_room'] ?? 1));
                    $coveredSeats = $quantity * $capacityPerRoom;

                    $hotelIndex = isset($roomRow['hotel_index']) && $roomRow['hotel_index'] !== '' ? max(0, (int) $roomRow['hotel_index']) : null;
                    $hotelId = isset($roomRow['hotel_id']) && $roomRow['hotel_id'] !== '' ? (int) $roomRow['hotel_id'] : null;

                    if ($hotelId !== null && isset($hotelIdToIndex[$hotelId])) {
                        $hotelIndex = (int) $hotelIdToIndex[$hotelId];
                    }
                    if ($hotelIndex === null && count($expectedHotelIndexes) === 1) {
                        $hotelIndex = $expectedHotelIndexes[0];
                    }

                    if ($hotelIndex === null || ! in_array($hotelIndex, $expectedHotelIndexes, true)) {
                        $validator->errors()->add("departure_allocations.$departureIndex.rooms.$roomIndex.hotel_index", 'Associez cette chambre a un sejour.');
                        continue;
                    }

                    if (! isset($coverageByHotelIndex[$hotelIndex])) {
                        $coverageByHotelIndex[$hotelIndex] = 0;
                    }
                    $coverageByHotelIndex[$hotelIndex] += $coveredSeats;
                }

                if (! $roomsTouched || $targetSeats <= 0 || $expectedHotelIndexes === []) {
                    continue;
                }

                foreach ($expectedHotelIndexes as $expectedHotelIndex) {
                    $coveredSeats = (int) ($coverageByHotelIndex[$expectedHotelIndex] ?? 0);
                    if ($coveredSeats < $targetSeats) {
                        $stayNumber = $expectedHotelIndex + 1;
                        $validator->errors()->add(
                            "departure_allocations.$departureIndex.rooms",
                            "Le sejour {$stayNumber} couvre {$coveredSeats}/{$targetSeats} place(s) pour ce depart."
                        );
                    }
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $departurePlaces = $this->input('departure_places', []);
        $departurePlaces = is_array($departurePlaces) ? array_values($departurePlaces) : [];

        // Clean flight_options: normalize departure_place_id and airline_id to integers/null.
        $flightOptions = $this->input('flight_options', []);
        if (is_array($flightOptions)) {
            foreach (array_keys($flightOptions) as $index) {
                // departure_place_id can be numeric or temporary NEW_x from UI.
                if (isset($flightOptions[$index]['departure_place_id'])) {
                    $val = $flightOptions[$index]['departure_place_id'];
                    $normalizedPlaceId = null;

                    if (is_numeric($val) && (int) $val > 0) {
                        $normalizedPlaceId = (int) $val;
                    } elseif (is_string($val) && preg_match('/^NEW_(\d+)$/', $val, $matches)) {
                        $placeIndex = (int) ($matches[1] ?? -1);
                        $linkedPlace = $departurePlaces[$placeIndex] ?? null;
                        $linkedPlaceId = is_array($linkedPlace) ? ($linkedPlace['id'] ?? null) : null;
                        if (is_numeric($linkedPlaceId) && (int) $linkedPlaceId > 0) {
                            $normalizedPlaceId = (int) $linkedPlaceId;
                        }
                    }

                    $flightOptions[$index]['departure_place_id'] = $normalizedPlaceId;
                }

                // Normalize airline_id
                if (isset($flightOptions[$index]['airline_id'])) {
                    $val = $flightOptions[$index]['airline_id'];
                    if (! is_numeric($val) || (int) $val <= 0) {
                        $flightOptions[$index]['airline_id'] = null;
                    } else {
                        $flightOptions[$index]['airline_id'] = (int) $val;
                    }
                }
            }
            $this->merge(['flight_options' => $flightOptions]);
        }

        // Clean departure_allocations: remove rooms without hotel_index
        $departureAllocations = $this->input('departure_allocations', []);
        if (is_array($departureAllocations)) {
            foreach (array_keys($departureAllocations) as $depIndex) {
                if (isset($departureAllocations[$depIndex]['rooms']) && is_array($departureAllocations[$depIndex]['rooms'])) {
                    $rooms = $departureAllocations[$depIndex]['rooms'];
                    $cleanedRooms = [];
                    foreach ($rooms as $roomIndex => $room) {
                        if (!is_array($room)) {
                            continue;
                        }
                        // Keep only rooms that are effectively mapped to a stay.
                        $hotelIndex = $room['hotel_index'] ?? null;
                        if ($hotelIndex === 'null' || $hotelIndex === 'undefined') {
                            $hotelIndex = null;
                        }

                        if (is_numeric($hotelIndex) && (int) $hotelIndex >= 0) {
                            $room['hotel_index'] = (int) $hotelIndex;
                            $cleanedRooms[$roomIndex] = $room;
                        }
                    }
                    $departureAllocations[$depIndex]['rooms'] = $cleanedRooms;
                }
            }
            $this->merge(['departure_allocations' => $departureAllocations]);
        }

        // Decode programme_days_payload for programme days structure
        $payload = $this->input('programme_days_payload');

        if (!is_string($payload) || trim($payload) === '') {
            return;
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return;
        }

        if (isset($decoded['programme_days']) && is_array($decoded['programme_days'])) {
            $decoded = $decoded['programme_days'];
        }

        $this->merge([
            'programme_days' => array_values(array_filter($decoded, 'is_array')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Basic
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string',
            'post_status' => 'nullable|in:publish,draft,pending,private',
            
            // Location
            'locations' => 'nullable|array',
            'locations.*' => 'integer',
            'address' => 'nullable|string',
            'id_location' => 'nullable|integer',
            'location_id' => 'nullable|integer',
            'map_lat' => 'nullable|string',
            'map_lng' => 'nullable|string',
            'map_zoom' => 'nullable|integer',
            'map_type' => 'nullable|string',
            
            // General
            'is_featured' => 'nullable',
            'is_group_deal' => 'nullable|boolean',
            'tour_price_by' => 'nullable|string',
            'st_tour_external_booking' => 'nullable|string',
            'hide_adult_in_booking_form' => 'nullable',
            'max_people' => 'nullable|integer|min:0',
            'min_people' => 'nullable|integer|min:1',
            'duration_day' => 'nullable|integer|min:1',
            'destination' => 'nullable|string|max:255',
            'duration_text' => 'nullable|string|max:100',
            'voyage_theme_ids' => 'nullable|array',
            'voyage_theme_ids.*' => 'integer|exists:voyage_themes,id',

            // Contact
            'contact_email' => 'nullable|email',
            'phone' => 'nullable|string',
            'fax' => 'nullable|string',
            'website' => 'nullable|string',
            
            // Price
            'min_price' => 'nullable|numeric|min:0',
            'base_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'adult_price' => 'nullable|numeric|min:0',
            'child_price' => 'nullable|numeric|min:0',
            'infant_price' => 'nullable|numeric|min:0',
            'commission_adulte' => 'nullable|numeric|min:0',
            'commission_enfant' => 'nullable|numeric|min:0',
            'discount' => 'nullable|string',
            'discount_type' => 'nullable|string',
            'discount_by_people_type' => 'nullable|string',
            'calculator_discount_by_people_type' => 'nullable|string',
            
            // Information
            'tours_include' => 'nullable|string',
            'tours_exclude' => 'nullable|string',
            'tours_highlight' => 'nullable|string',
            'tours_faq' => 'nullable|string',
            'tours_program_style' => 'nullable|string',
            
            // Availability
            'tours_booking_period' => 'nullable|string',
            'st_booking_option_type' => 'nullable|string',
            'check_in' => 'nullable|string',
            'check_out' => 'nullable|string',
            
            // Cancel
            'st_allow_cancel' => 'nullable',
            'st_cancel_percent' => 'nullable|integer|min:0|max:100',
            'st_cancel_number_day' => 'nullable|integer|min:0',
            
            // iCal
            'ical_url' => 'nullable|string',
            
            // Media
            'thumbnail_id' => 'nullable|integer',
            'hero_image_id' => 'nullable|integer',
            'hero_use_as_thumbnail' => 'nullable',
            'hero_gallery_ids' => 'nullable|string', // 5 images pour la galerie hero (IDs séparés par des virgules)
            'gallery_ids' => 'nullable|string',
            'video' => 'nullable|string',
            
            // Map
            'st_google_map' => 'nullable|string',
            
            // Payment gateways
            'is_meta_payment_gateway_st_paypal' => 'nullable',
            'is_meta_payment_gateway_st_onepay' => 'nullable',
            'is_meta_payment_gateway_st_onepay_atm' => 'nullable',
            'is_meta_payment_gateway_st_payu' => 'nullable',
            'is_meta_payment_gateway_st_payulatam' => 'nullable',
            'is_meta_payment_gateway_st_payumoney' => 'nullable',
            'is_meta_payment_gateway_st_razor' => 'nullable',
            
            // Taxonomies
            'st_tour_type' => 'nullable|array',
            'st_tour_type.*' => 'integer',
            'durations' => 'nullable|array',
            'durations.*' => 'integer',
            'language' => 'nullable|array',
            'language.*' => 'integer',
            'languages' => 'nullable|array',
            'languages.*' => 'integer',
            
            // Tour Program
            'tours_program' => 'nullable|array',
            'tours_program.*.title' => 'nullable|string',
            'tours_program.*.desc' => 'nullable|string',

            // Programme par jours (Laravel aj_tour_days + activities)
            'programme_days' => 'nullable|array',
            'programme_days.*.id' => 'nullable|integer',
            'programme_days.*.day_id' => 'nullable|integer',
            'programme_days.*.mode' => 'nullable|string|in:free,program',
            'programme_days.*.day_title' => 'nullable|string|max:255',
            'programme_days.*.city' => 'nullable|string|max:255',
            'programme_days.*.day_type' => 'nullable|string|max:64',
            'programme_days.*.content_html' => 'nullable|string',
            'programme_days.*.notes' => 'nullable|string',
            'programme_days.*.title' => 'nullable|string|max:255',
            'programme_days.*.description' => 'nullable|string',
            'programme_days.*.hotel_id' => 'nullable',
            'programme_days.*.transfer_ids' => 'nullable',
            'programme_days.*.flights' => 'nullable',
            'programme_days.*.activities' => 'nullable|array',
            'programme_days.*.activities.*.day_activity_id' => 'nullable|integer',
            'programme_days.*.activities.*.activity_id' => 'nullable|integer',
            'programme_days.*.activities.*.sort_order' => 'nullable|integer',
            'programme_days.*.activities.*.is_mandatory' => 'nullable',
            'programme_days.*.activities.*.is_included' => 'nullable',
            'programme_days.*.activities.*.custom_title' => 'nullable|string',
            'programme_days.*.activities.*.custom_description' => 'nullable|string',

            // Activités inline (onglet Activités)
            'tour_activities' => 'nullable|array',
            'tour_activities.*.id' => 'nullable|integer',
            'tour_activities.*.activity_id' => 'required|integer|exists:wp.aj_activities,id',
            'tour_activities.*.title' => 'nullable|string|max:255',
            'tour_activities.*.description' => 'nullable|string|max:5000',
            'tour_activities.*.day_number' => 'nullable|integer|min:1',
            'tour_activities.*.sort_order' => 'nullable|integer|min:0',
            'tour_activities.*.pricing_type' => 'nullable|string|max:64',
            'tour_activities.*.unit_price' => 'nullable|numeric|min:0',
            'tour_activities.*.child_price' => 'nullable|numeric|min:0',

            // Vols (voyage_flight_options) — multi-options Aller/Retour/Segment
            'flight_options' => 'nullable|array',
            'flight_options.*.id' => 'nullable|integer',
            'flight_options.*.type' => 'nullable|string|in:outbound,return,segment',
            'flight_options.*.day_number' => 'nullable|integer|min:1',
            'flight_options.*.departure_place_id' => 'nullable|integer|min:0',
            'flight_options.*.airline_id' => 'nullable|integer',
            'flight_options.*.cabin' => 'nullable|string|in:economy,business,first',
            'flight_options.*.from_city' => 'nullable|string|max:255',
            'flight_options.*.to_city' => 'nullable|string|max:255',
            'flight_options.*.departure_date' => 'nullable|string|date',
            'flight_options.*.departure_time' => 'nullable|string|max:20',
            'flight_options.*.arrival_date' => 'nullable|string|date',
            'flight_options.*.arrival_time' => 'nullable|string|max:20',
            'flight_options.*.departure_datetime' => 'nullable|string',
            'flight_options.*.arrival_datetime' => 'nullable|string',
            'flight_options.*.flight_number' => 'nullable|string|max:50',
            'flight_options.*.baggage_cabin_kg' => 'nullable|integer|min:0',
            'flight_options.*.baggage_checkin_kg' => 'nullable|integer|min:0',
            'flight_options.*.is_tentative' => 'nullable',
            'flight_options.*.notes' => 'nullable|string|max:2000',

            // Vols Laravel voyage_flights: outbound (Jour 1) + inbound (dernier jour)
            'flights' => 'nullable|array',
            'flights.outbound' => 'nullable|array',
            'flights.outbound.airline_id' => 'nullable|integer',
            'flights.outbound.cabin' => 'nullable|string|in:economy,business,first',
            'flights.outbound.flight_number' => 'nullable|string|max:50',
            'flights.outbound.from_city' => 'nullable|string|max:100',
            'flights.outbound.to_city' => 'nullable|string|max:100',
            'flights.outbound.departure_date' => 'nullable|string|date',
            'flights.outbound.baggage_cabin_kg' => 'nullable|integer|min:0',
            'flights.outbound.baggage_checkin_kg' => 'nullable|integer|min:0',
            'flights.outbound.is_tentative' => 'nullable',
            'flights.inbound' => 'nullable|array',
            'flights.inbound.airline_id' => 'nullable|integer',
            'flights.inbound.cabin' => 'nullable|string|in:economy,business,first',
            'flights.inbound.flight_number' => 'nullable|string|max:50',
            'flights.inbound.from_city' => 'nullable|string|max:100',
            'flights.inbound.to_city' => 'nullable|string|max:100',
            'flights.inbound.departure_date' => 'nullable|string|date',
            'flights.inbound.baggage_cabin_kg' => 'nullable|integer|min:0',
            'flights.inbound.baggage_checkin_kg' => 'nullable|integer|min:0',
            'flights.inbound.is_tentative' => 'nullable',

            // Hôtel + Transferts (aj_tour_hotels, aj_tour_transfers) — multi-row support
            'tour_hotel' => 'nullable|array',
            'tour_hotel.hotel_name' => 'nullable|string|max:255',
            'tour_hotel.stars' => 'nullable|integer|min:0|max:5',
            'tour_hotel.address' => 'nullable|string|max:500',
            'tour_hotel.room_type' => 'nullable|string|max:255',
            'tour_hotel.meal_plan' => 'nullable|string|max:255',
            'tour_hotel.notes' => 'nullable|string|max:2000',
            'tour_hotel.image_id' => 'nullable|integer|min:0',
            'tour_hotels' => 'nullable|array',
            'tour_hotels.*.day_number' => 'nullable|integer|min:1',
            'tour_hotels.*.is_optional' => 'nullable|boolean',
            'tour_hotels.*.hotel_name' => 'nullable|string|max:255',
            'tour_hotels.*.stars' => 'nullable|integer|min:0|max:5',
            'tour_hotels.*.address' => 'nullable|string|max:500',
            'tour_hotels.*.room_type' => 'nullable|string|max:255',
            'tour_hotels.*.meal_plan' => 'nullable|string|max:255',
            'tour_hotels.*.notes' => 'nullable|string|max:2000',
            'tour_hotels.*.image_id' => 'nullable|integer|min:0',
            'tour_hotels.*.rooms' => 'nullable|array',
            'tour_hotels.*.rooms.*.id' => 'nullable|integer',
            'tour_hotels.*.rooms.*.room_type' => 'nullable|string|max:100',
            'tour_hotels.*.rooms.*.room_label' => 'nullable|string|max:255',
            'tour_hotels.*.rooms.*.room_code' => 'nullable|string|max:50',
            'tour_hotels.*.rooms.*.room_count' => 'nullable|integer|min:0',
            'tour_hotels.*.rooms.*.capacity_adults' => 'nullable|integer|min:0',
            'tour_hotels.*.rooms.*.capacity_children' => 'nullable|integer|min:0',
            'tour_hotels.*.rooms.*.capacity_total' => 'nullable|integer|min:0',
            'tour_hotels.*.rooms.*.supplement' => 'nullable|numeric|min:0',
            'tour_hotels.*.rooms.*.description' => 'nullable|string|max:1000',
            'tour_hotels.*.rooms.*.notes' => 'nullable|string|max:2000',
            'tour_hotels.*.rooms.*.is_active' => 'nullable',
            'tour_hotels.*.rooms.*.is_default' => 'nullable',
            'tour_hotels.*.rooms.*.date_availabilities' => 'nullable|array',
            'tour_hotels.*.rooms.*.date_availabilities.*.id' => 'nullable|integer',
            'tour_hotels.*.rooms.*.date_availabilities.*.travel_date_id' => 'nullable|integer',
            'tour_hotels.*.rooms.*.date_availabilities.*.date' => 'nullable|date',
            'tour_hotels.*.rooms.*.date_availabilities.*.available_rooms' => 'nullable|integer|min:0',
            'tour_hotels.*.rooms.*.date_availabilities.*.available_places' => 'nullable|integer|min:0',
            'tour_hotels.*.rooms.*.date_availabilities.*.status' => 'nullable|in:available,limited,full,closed',
            'tour_hotels.*.rooms.*.date_availabilities.*.supplement' => 'nullable|numeric|min:0',
            'tour_transfer_arrival' => 'nullable|array',
            'tour_transfer_arrival.from_label' => 'nullable|string|max:255',
            'tour_transfer_arrival.to_label' => 'nullable|string|max:255',
            'tour_transfer_arrival.pickup_time' => 'nullable|string|max:20',
            'tour_transfer_arrival.dropoff_time' => 'nullable|string|max:20',
            'tour_transfer_arrival.vehicle_type' => 'nullable|string|max:255',
            'tour_transfer_arrival.notes' => 'nullable|string|max:2000',
            'tour_transfer_arrival.image_id' => 'nullable|integer|min:0',
            'tour_transfer_arrivals' => 'nullable|array',
            'tour_transfer_arrivals.*.day_number' => 'nullable|integer|min:1',
            'tour_transfer_arrivals.*.is_optional' => 'nullable|boolean',
            'tour_transfer_arrivals.*.from_label' => 'nullable|string|max:255',
            'tour_transfer_arrivals.*.to_label' => 'nullable|string|max:255',
            'tour_transfer_arrivals.*.pickup_time' => 'nullable|string|max:20',
            'tour_transfer_arrivals.*.dropoff_time' => 'nullable|string|max:20',
            'tour_transfer_arrivals.*.vehicle_type' => 'nullable|string|max:255',
            'tour_transfer_arrivals.*.notes' => 'nullable|string|max:2000',
            'tour_transfer_arrivals.*.image_id' => 'nullable|integer|min:0',
            'tour_transfer_departure' => 'nullable|array',
            'tour_transfer_departure.from_label' => 'nullable|string|max:255',
            'tour_transfer_departure.to_label' => 'nullable|string|max:255',
            'tour_transfer_departure.pickup_time' => 'nullable|string|max:20',
            'tour_transfer_departure.dropoff_time' => 'nullable|string|max:20',
            'tour_transfer_departure.vehicle_type' => 'nullable|string|max:255',
            'tour_transfer_departure.notes' => 'nullable|string|max:2000',
            'tour_transfer_departure.image_id' => 'nullable|integer|min:0',
            'tour_transfer_departures' => 'nullable|array',
            'tour_transfer_departures.*.day_number' => 'nullable|integer|min:1',
            'tour_transfer_departures.*.is_optional' => 'nullable|boolean',
            'tour_transfer_departures.*.from_label' => 'nullable|string|max:255',
            'tour_transfer_departures.*.to_label' => 'nullable|string|max:255',
            'tour_transfer_departures.*.pickup_time' => 'nullable|string|max:20',
            'tour_transfer_departures.*.dropoff_time' => 'nullable|string|max:20',
            'tour_transfer_departures.*.vehicle_type' => 'nullable|string|max:255',
            'tour_transfer_departures.*.notes' => 'nullable|string|max:2000',
            'tour_transfer_departures.*.image_id' => 'nullable|integer|min:0',

            // Lieux de départ (Starting from)
            'departure_places' => 'nullable|array',
            'departure_places.*.id' => 'nullable|integer',
            'departure_places.*.name' => 'nullable|string|max:255',
            'departure_places.*.code' => 'nullable|string|max:50',
            'departure_places.*.price' => 'nullable|numeric|min:0',
            'departure_places.*.is_active' => 'nullable|boolean',
            'departure_places.*.flights' => 'nullable|array',
            'departure_places.*.flights.*.id' => 'nullable|integer',
            'departure_places.*.flights.*.airline' => 'nullable|string|max:255',
            'departure_places.*.flights.*.flight_number' => 'nullable|string|max:50',
            'departure_places.*.flights.*.from_airport' => 'nullable|string|max:255',
            'departure_places.*.flights.*.to_airport' => 'nullable|string|max:255',
            'departure_places.*.flights.*.depart_time' => 'nullable|string|max:20',
            'departure_places.*.flights.*.arrive_time' => 'nullable|string|max:20',
            'departure_places.*.flights.*.notes' => 'nullable|string|max:2000',

            // Dates disponibles (Travelling on)
            'travel_dates' => 'nullable|array',
            'travel_dates.*.id' => 'nullable|integer',
            'travel_dates.*.date' => 'nullable|date',
            'travel_dates.*.is_active' => 'nullable|boolean',
            'travel_dates.*.seats' => 'nullable|integer|min:0',
            'travel_dates.*.price_override' => 'nullable|numeric|min:0',

            // Répartition des chambres par départ (Laravel)
            'departure_allocations' => 'nullable|array',
            'departure_allocations.*.departure_id' => 'nullable|integer',
            'departure_allocations.*.travel_date_id' => 'nullable|integer',
            'departure_allocations.*.date' => 'nullable|date',
            'departure_allocations.*.rooms' => 'nullable|array',
            'departure_allocations.*.rooms.*.id' => 'nullable|integer',
            'departure_allocations.*.rooms.*.hotel_id' => 'nullable|integer',
            'departure_allocations.*.rooms.*.hotel_index' => 'nullable|integer|min:0',
            'departure_allocations.*.rooms.*.room_type' => 'nullable|string|max:100',
            'departure_allocations.*.rooms.*.quantity' => 'nullable|integer|min:0',
            'departure_allocations.*.rooms.*.capacity_per_room' => 'nullable|integer|min:1',

            // Extras réservation (Laravel voyage_extras)
            'voyage_extras' => 'nullable|array',
            'voyage_extras.*.id' => 'nullable|integer',
            'voyage_extras.*.name' => 'nullable|string|max:255',
            'voyage_extras.*.description' => 'nullable|string|max:2000',
            'voyage_extras.*.price_adult' => 'nullable|numeric|min:0',
            'voyage_extras.*.price_child' => 'nullable|numeric|min:0',
            'voyage_extras.*.is_active' => 'nullable|boolean',
            'voyage_extras.*.extra_type' => 'nullable|string|max:64',
            'voyage_extras.*.icon' => 'nullable|string|max:80',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'titre',
            'slug' => 'slug',
            'content' => 'description',
            'excerpt' => 'extrait',
            'destination' => 'destination',
            'duration_text' => 'durée',
            'adult_price' => 'prix adulte',
            'child_price' => 'prix enfant',
            'min_price' => 'prix minimum',
            'min_people' => 'nombre minimum de personnes',
            'is_group_deal' => 'afficher dans Group Deals',
            'thumbnail_id' => 'image à la une',
            'hero_image_id' => 'image principale (hero)',
            'hero_gallery_ids' => 'galerie hero (5 images)',
            'gallery_ids' => 'galerie',
            'post_status' => 'statut',
        ];
    }
}
