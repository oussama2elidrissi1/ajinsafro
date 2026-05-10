<?php

namespace App\Http\Requests\Admin;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('agencies.edit');
    }

    public function rules(): array
    {
        /** @var \App\Models\Branch $agency */
        $agency = $this->route('agency');

        return [
            'name' => ['required', 'string', 'max:190'],
            'code' => ['required', 'string', 'max:20', Rule::unique('branches', 'code')->ignore($agency?->id)],
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
            'currency' => ['nullable', 'string', 'max:10'],
            'business_hours' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['file', 'max:8192'],
        ];
    }
}
