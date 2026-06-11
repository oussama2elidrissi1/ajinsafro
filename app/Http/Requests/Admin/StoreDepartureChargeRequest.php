<?php

namespace App\Http\Requests\Admin;

use App\Models\DepartureCharge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartureChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('departures_finance.manage_charges');
    }

    public function rules(): array
    {
        return [
            'charge_type_id' => ['nullable', 'integer', 'exists:charge_types,id'],
            'title' => ['required', 'string', 'max:190'],
            'supplier_name' => ['nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'payment_method' => ['required', Rule::in(DepartureCharge::PAYMENT_METHODS)],
            'payment_status' => ['required', Rule::in(DepartureCharge::PAYMENT_STATUSES)],
            'paid_at' => ['nullable', 'date'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
