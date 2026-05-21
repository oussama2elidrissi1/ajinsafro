<?php

namespace App\Http\Requests\Admin;

class UpdateCustomReservationRequestRequest extends StoreCustomReservationRequestRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reservations.update') || $this->user()?->can('reservations.view') || false;
    }
}
