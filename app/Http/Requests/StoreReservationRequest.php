<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reservations.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'tour_id' => ['required', 'integer'],
            'travel_date_id' => ['nullable', 'integer'],

            'client_mode' => ['required', 'in:existing,new'],
            'client_external_id' => ['required_if:client_mode,existing', 'nullable', 'integer', 'exists:clients,id'],
            'client_first_name' => ['required_if:client_mode,new', 'nullable', 'string', 'max:100'],
            'client_last_name' => ['required_if:client_mode,new', 'nullable', 'string', 'max:100'],
            'client_phone' => ['nullable', 'string', 'max:50'],
            'client_email' => ['nullable', 'email', 'max:190'],
            'client_document_type' => ['nullable', 'string', 'max:50'],
            'client_document_number' => ['nullable', 'string', 'max:100'],

            'payment_type' => ['nullable', 'in:CASHPLUS,VIREMENT,ESPECE'],
            'payment_receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:5120'],

            'base_price' => ['nullable', 'numeric', 'min:0'],
            'hotel_rooms' => ['nullable', 'array'],
            'hotel_rooms.*.tour_hotel_id' => ['required_with:hotel_rooms.*', 'nullable', 'integer'],
            'hotel_rooms.*.tour_hotel_room_id' => ['required_with:hotel_rooms.*', 'nullable', 'integer'],
            'hotel_rooms.*.room_count' => ['required_with:hotel_rooms.*', 'nullable', 'integer', 'min:0'],

            'visa_ok' => ['nullable', 'boolean'],
            'visa_notes' => ['nullable', 'string', 'max:2000'],
            'visa_status' => ['nullable', 'in:not_required,pending,approved,rejected'],
            'visa_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:5120'],

            'status' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],

            'passengers' => ['nullable', 'array'],
            'passengers.*.first_name' => ['nullable', 'string', 'max:100'],
            'passengers.*.last_name' => ['nullable', 'string', 'max:100'],
            'passengers.*.type' => ['nullable', 'in:adult,child,infant'],
            'passengers.*.birth_date' => ['nullable', 'date'],
            'passengers.*.document_type' => ['nullable', 'string', 'max:50'],
            'passengers.*.document_number' => ['nullable', 'string', 'max:100'],
        ];
    }
}