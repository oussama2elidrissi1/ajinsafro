<?php

namespace App\Http\Requests\Admin;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canAny(['agencies.create', 'points_of_sale.create']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'code' => ['required', 'string', 'max:20', 'unique:branches,code'],
            'type' => ['required', Rule::in([Branch::TYPE_HEAD_OFFICE, Branch::TYPE_BRANCH])],
            'agency_type' => ['required', Rule::in(array_keys(Branch::agencyTypeLabels()))],
            'status' => ['required', Rule::in(array_keys(Branch::statusLabels()))],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:190'],
            'manager_user_id' => ['nullable', 'exists:users,id'],
            'default_commission_rate' => ['nullable', 'numeric', 'min:0'],
            'default_commission_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'default_commission_value' => ['nullable', 'numeric', 'min:0'],
            'monthly_revenue_target' => ['nullable', 'numeric', 'min:0'],
            'monthly_reservations_target' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'business_hours' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['file', 'max:8192'],
        ];
    }
}
