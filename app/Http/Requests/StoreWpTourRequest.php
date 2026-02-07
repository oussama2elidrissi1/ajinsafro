<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreWpTourRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Configure the validator (flights: exactly one default when 2 vols).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $flights = $this->input('flights', []);
            $count = 0;
            foreach ($flights as $f) {
                if (! empty($f['airline_id']) || ! empty($f['cabin_class'])) {
                    $count++;
                }
            }
            if ($count >= 1) {
                $f0 = $flights[0] ?? [];
                if (! empty($f0['airline_id']) && empty($f0['cabin_class'])) {
                    $v->errors()->add('flights.0.cabin_class', 'Le type de cabine est requis pour le vol 1.');
                }
                if (empty($f0['airline_id']) && ! empty($f0['cabin_class'])) {
                    $v->errors()->add('flights.0.airline_id', 'La compagnie aérienne est requise pour le vol 1.');
                }
            }
            if ($count === 2) {
                $defaults = 0;
                foreach ($flights as $f) {
                    if (! empty($f['is_default']) && (string) $f['is_default'] === '1') {
                        $defaults++;
                    }
                }
                if ($defaults !== 1) {
                    $v->errors()->add('flights', 'Lorsqu\'il y a deux vols, un seul doit être choisi comme vol par défaut.');
                }
            }
        });
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

            // Vols (max 2)
            'flights' => 'nullable|array',
            'flights.0.airline_id' => 'nullable|integer|exists:airlines,id',
            'flights.0.cabin_class' => 'nullable|string|in:economy,premium_economy,business,first',
            'flights.0.flight_number' => 'nullable|string|max:50',
            'flights.0.departure_airport' => 'nullable|string|max:20',
            'flights.0.arrival_airport' => 'nullable|string|max:20',
            'flights.0.departure_at' => 'nullable|string',
            'flights.0.arrival_at' => 'nullable|string',
            'flights.0.baggage' => 'nullable|string|max:50',
            'flights.0.cabin_baggage' => 'nullable|string|max:30',
            'flights.0.checkin_baggage' => 'nullable|string|max:30',
            'flights.0.price' => 'nullable|numeric|min:0',
            'flights.0.currency' => 'nullable|string|max:3',
            'flights.0.is_default' => 'nullable',
            'flights.0.is_tentative' => 'nullable',
            'flights.1.airline_id' => 'nullable|integer|exists:airlines,id',
            'flights.1.cabin_class' => 'nullable|string|in:economy,premium_economy,business,first',
            'flights.1.flight_number' => 'nullable|string|max:50',
            'flights.1.departure_airport' => 'nullable|string|max:20',
            'flights.1.arrival_airport' => 'nullable|string|max:20',
            'flights.1.departure_at' => 'nullable|string',
            'flights.1.arrival_at' => 'nullable|string',
            'flights.1.baggage' => 'nullable|string|max:50',
            'flights.1.cabin_baggage' => 'nullable|string|max:30',
            'flights.1.checkin_baggage' => 'nullable|string|max:30',
            'flights.1.price' => 'nullable|numeric|min:0',
            'flights.1.currency' => 'nullable|string|max:3',
            'flights.1.is_default' => 'nullable',
            'flights.1.is_tentative' => 'nullable',
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
            'gallery_ids' => 'galerie',
            'post_status' => 'statut',
        ];
    }
}
