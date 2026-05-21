<?php

namespace App\Http\Requests\Admin;

use App\Models\CustomReservationRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomReservationRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reservations.create') || $this->user()?->can('reservations.view') || false;
    }

    public function rules(): array
    {
        return [
            'submit_action' => ['nullable', 'string', 'in:draft,create,create_open'],
            'status' => ['required', Rule::in(array_keys(CustomReservationRequest::statusOptions()))],
            'priority' => ['nullable', Rule::in(array_keys(CustomReservationRequest::priorityOptions()))],
            'source' => ['nullable', Rule::in(array_keys(CustomReservationRequest::sourceOptions()))],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'client_type' => ['required', 'in:particular,company,agency'],
            'client_name' => ['required', 'string', 'max:190'],
            'client_gender' => ['nullable', 'in:M,F'],
            'client_phone' => ['required', 'string', 'max:50'],
            'client_whatsapp' => ['nullable', 'string', 'max:50'],
            'whatsapp_same_as_phone' => ['nullable', 'boolean'],
            'client_email' => ['nullable', 'email', 'max:190'],
            'preferred_channels' => ['nullable', 'array'],
            'preferred_channels.*' => ['string', 'in:call,whatsapp,email'],
            'adults' => ['required', 'integer', 'min:1', 'max:99'],
            'children' => ['nullable', 'array'],
            'children.*.age' => ['nullable', 'integer', 'min:0', 'max:17'],
            'children.*.birth_date' => ['nullable', 'date'],
            'infants' => ['nullable', 'array'],
            'infants.*.age' => ['nullable', 'integer', 'min:0', 'max:3'],
            'infants.*.birth_date' => ['nullable', 'date'],
            'passengers_note' => ['nullable', 'string', 'max:2000'],
            'destination_text' => ['nullable', 'string', 'max:190'],
            'departure_city_text' => ['nullable', 'string', 'max:190'],
            'departure_date' => ['nullable', 'date'],
            'return_date' => ['nullable', 'date', 'after_or_equal:departure_date'],
            'flexible_dates' => ['nullable', 'boolean'],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'gte:budget_min'],
            'currency' => ['required', 'string', 'max:8'],
            'services' => ['nullable', 'array'],
            'services.*' => ['array'],
            'internal_notes' => ['nullable', 'string'],
            'client_notes' => ['nullable', 'string'],
            'admin_response' => ['nullable', 'string'],
            'quoted_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $services = collect($this->input('services', []))
                ->filter(fn ($value) => is_array($value) && (bool) ($value['enabled'] ?? false));

            if (trim((string) $this->input('destination_text', '')) === '' && $services->isEmpty()) {
                $validator->errors()->add('destination_text', 'Renseignez une destination ou selectionnez au moins un service.');
            }
        });
    }
}
