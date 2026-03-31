<?php

namespace App\Http\Requests;

class UpdateReservationRequest extends StoreReservationRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reservations.update') ?? false;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'passengers.*.id' => ['nullable', 'integer'],
        ]);
    }
}
