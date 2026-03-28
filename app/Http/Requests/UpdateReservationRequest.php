<?php

namespace App\Http\Requests;

class UpdateReservationRequest extends StoreReservationRequest
{
    // Same rules as Store; can be specialized later if needed.
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reservations.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'tour_id' => ['nullable', 'integer'],

            'client_mode' => ['required', 'in:existing,new'],
            'client_external_id' => ['nullable', 'integer', 'required_if:client_mode,existing'],

            'client_first_name' => ['required_without:client_external_id', 'nullable', 'string', 'max:100'],
            'client_last_name' => ['required_without:client_external_id', 'nullable', 'string', 'max:100'],
            'client_email' => ['nullable', 'email', 'max:190'],
            'client_phone' => ['nullable', 'string', 'max:50'],
            'client_document_type' => ['nullable', 'string', 'max:50'],
            'client_document_number' => ['nullable', 'string', 'max:100'],

            'payment_type' => ['nullable', 'in:CASHPLUS,VIREMENT,ESPECE'],
            'payment_receipt' => ['nullable', 'file', 'max:5120'],

            'status' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],

            'passengers' => ['nullable', 'array'],
            'passengers.*.id' => ['nullable', 'integer'],
            'passengers.*.first_name' => ['nullable', 'string', 'max:100'],
            'passengers.*.last_name' => ['nullable', 'string', 'max:100'],
            'passengers.*.type' => ['nullable', 'string', 'max:20'],
            'passengers.*.birth_date' => ['nullable', 'date'],
            'passengers.*.document_type' => ['nullable', 'string', 'max:50'],
            'passengers.*.document_number' => ['nullable', 'string', 'max:100'],
        ];
    }
}

