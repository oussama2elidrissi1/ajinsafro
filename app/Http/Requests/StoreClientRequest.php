<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'client_type' => 'required|in:individual,company,agency',
            'status' => 'required|in:active,inactive,blocked,vip',
            'source' => 'nullable|string|in:website,whatsapp,phone,facebook,instagram,referral,walkin,admin',
            'assigned_to' => 'nullable|exists:users,id',

            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'full_name' => 'nullable|string|max:500',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'nationality' => 'nullable|string|max:100',
            'country_of_residence' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'preferred_language' => 'nullable|string|max:10',

            'email' => ['nullable', 'email', 'max:255', Rule::unique(Client::class, 'email')],
            'phone' => 'nullable|string|max:50',
            'phone_alt' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'contact_method_preference' => 'nullable|in:phone,email,whatsapp',

            'passport_number' => ['nullable', 'string', 'max:50', Rule::unique(Client::class, 'passport_number')],
            'passport_issue_country' => 'nullable|string|max:100',
            'passport_issue_date' => 'nullable|date',
            'passport_expiry_date' => 'nullable|date|after_or_equal:today',
            'national_id_number' => 'nullable|string|max:50',
            'visa_required' => 'nullable|boolean',
            'visa_status' => 'nullable|in:not_required,pending,approved,rejected',

            'traveler_category' => 'nullable|in:solo,couple,family,group,business',
            'preferred_departure_city' => 'nullable|string|max:100',
            'preferred_destination' => 'nullable|string|max:255',
            'preferred_travel_month' => 'nullable|string|max:50',
            'budget_min' => 'nullable|numeric|min:0',
            'budget_max' => 'nullable|numeric|min:0|gte:budget_min',
            'travel_interests' => 'nullable|array',
            'travel_interests.*' => 'nullable|string|max:100',
            'special_requests' => 'nullable|string|max:5000',
            'medical_notes' => 'nullable|string|max:5000',
            'dietary_requirements' => 'nullable|string|max:500',

            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'emergency_contact_relation' => 'nullable|string|max:50',

            'company_name' => 'nullable|string|max:255',
            'company_registration_number' => 'nullable|string|max:100',
            'tax_number' => 'nullable|string|max:100',
            'company_contact_person' => 'nullable|string|max:255',

            'billing_name' => 'nullable|string|max:255',
            'billing_email' => 'nullable|email|max:255',
            'billing_phone' => 'nullable|string|max:50',
            'billing_address' => 'nullable|string|max:1000',
            'billing_city' => 'nullable|string|max:100',
            'billing_country' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:20',
            'payment_terms' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',

            'newsletter_opt_in' => 'nullable|boolean',
            'sms_opt_in' => 'nullable|boolean',
            'whatsapp_opt_in' => 'nullable|boolean',
            'loyalty_points' => 'nullable|integer|min:0',
            'last_contacted_at' => 'nullable|date',
            'next_follow_up_at' => 'nullable|date',

            'avatar' => 'nullable|string|max:255',
            'internal_notes' => 'nullable|string',
            'blacklist_reason' => 'nullable|string|max:1000',
        ];

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $issue = $this->input('passport_issue_date');
            $expiry = $this->input('passport_expiry_date');
            if ($issue && $expiry && strtotime($expiry) <= strtotime($issue)) {
                $validator->errors()->add('passport_expiry_date', 'La date d’expiration doit être postérieure à la date d’émission.');
            }
            if ($this->boolean('visa_required') && ! in_array($this->input('visa_status'), ['pending', 'approved', 'rejected'], true)) {
                // Allow not_required as default; only validate if we want to require a status when visa_required
            }
        });
    }
}
