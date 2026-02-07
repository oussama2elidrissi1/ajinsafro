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
            'max_people' => 'nullable|integer|min:1',
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
            'programme_days.*.day_title' => 'nullable|string',
            'programme_days.*.notes' => 'nullable|string',
            'programme_days.*.title' => 'nullable|string',
            'programme_days.*.description' => 'nullable|string',
            'programme_days.*.activities' => 'nullable|array',
            'programme_days.*.activities.*.day_activity_id' => 'nullable|integer',
            'programme_days.*.activities.*.activity_id' => 'nullable|integer',
            'programme_days.*.activities.*.sort_order' => 'nullable|integer',
            'programme_days.*.activities.*.is_mandatory' => 'nullable',
            'programme_days.*.activities.*.is_included' => 'nullable',
            'programme_days.*.activities.*.custom_title' => 'nullable|string',
            'programme_days.*.activities.*.custom_description' => 'nullable|string',

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

            // Hôtel + Transferts (aj_tour_hotels, aj_tour_transfers) — intégrés au CRUD voyage
            'tour_hotel' => 'nullable|array',
            'tour_hotel.hotel_name' => 'nullable|string|max:255',
            'tour_hotel.stars' => 'nullable|integer|min:0|max:5',
            'tour_hotel.address' => 'nullable|string|max:500',
            'tour_hotel.room_type' => 'nullable|string|max:255',
            'tour_hotel.meal_plan' => 'nullable|string|max:255',
            'tour_hotel.notes' => 'nullable|string|max:2000',
            'tour_hotel.image_id' => 'nullable|integer|min:0',
            'tour_transfer_arrival' => 'nullable|array',
            'tour_transfer_arrival.from_label' => 'nullable|string|max:255',
            'tour_transfer_arrival.to_label' => 'nullable|string|max:255',
            'tour_transfer_arrival.pickup_time' => 'nullable|string|max:20',
            'tour_transfer_arrival.dropoff_time' => 'nullable|string|max:20',
            'tour_transfer_arrival.vehicle_type' => 'nullable|string|max:255',
            'tour_transfer_arrival.notes' => 'nullable|string|max:2000',
            'tour_transfer_arrival.image_id' => 'nullable|integer|min:0',
            'tour_transfer_departure' => 'nullable|array',
            'tour_transfer_departure.from_label' => 'nullable|string|max:255',
            'tour_transfer_departure.to_label' => 'nullable|string|max:255',
            'tour_transfer_departure.pickup_time' => 'nullable|string|max:20',
            'tour_transfer_departure.dropoff_time' => 'nullable|string|max:20',
            'tour_transfer_departure.vehicle_type' => 'nullable|string|max:255',
            'tour_transfer_departure.notes' => 'nullable|string|max:2000',
            'tour_transfer_departure.image_id' => 'nullable|integer|min:0',
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
            'gallery_ids' => 'galerie',
            'post_status' => 'statut',
        ];
    }
}
