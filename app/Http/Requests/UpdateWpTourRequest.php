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

            foreach ($departureAllocations as $departureIndex => $departureRow) {
                if (! is_array($departureRow)) {
                    continue;
                }

                $rooms = $departureRow['rooms'] ?? [];
                if (! is_array($rooms)) {
                    continue;
                }

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
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
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
            'post_status' => 'nullable|in:publish,draft,pending',
            
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
            'tour_price_by' => 'nullable|string',
            'st_tour_external_booking' => 'nullable|string',
            'hide_adult_in_booking_form' => 'nullable',
            'max_people' => 'nullable|integer|min:0',
            'min_people' => 'nullable|integer|min:1',
            'duration_day' => 'nullable|integer|min:1',
            'destination' => 'nullable|string|max:255',
            'duration_text' => 'nullable|string|max:100',
            
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
            'programme_days.*.day_type' => 'nullable|string|in:arrivee,visite,transfert,libre',
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
            'tour_activities.*.pricing_type' => 'nullable|in:per_person,fixed',
            'tour_activities.*.unit_price' => 'nullable|numeric|min:0',
            'tour_activities.*.quantity' => 'nullable|integer|min:1|max:999',

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
            'thumbnail_id' => 'image à la une',
            'hero_image_id' => 'image principale (hero)',
            'hero_gallery_ids' => 'galerie hero (5 images)',
            'gallery_ids' => 'galerie',
            'post_status' => 'statut',
        ];
    }
}
