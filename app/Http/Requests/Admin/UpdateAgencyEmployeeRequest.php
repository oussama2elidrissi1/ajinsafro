<?php

namespace App\Http\Requests\Admin;

use App\Models\AgencyEmployee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgencyEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('agency_employees.edit');
    }

    public function rules(): array
    {
        /** @var \App\Models\AgencyEmployee $employee */
        $employee = $this->route('employee');
        $branchId = (int) $this->input('branch_id', $employee?->branch_id);
        $canLogin = $this->boolean('can_login');

        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => array_values(array_filter([
                $canLogin ? 'required' : 'nullable',
                'email',
                'max:190',
                Rule::unique('agency_employees', 'email')
                    ->where(static function ($query) use ($branchId) {
                        return $query->where('branch_id', $branchId);
                    })
                    ->ignore($employee?->id),
                $canLogin ? Rule::unique('users', 'email')->ignore($employee?->user_id) : null,
            ])),
            'phone' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(array_keys(AgencyEmployee::statusLabels()))],
            'can_login' => ['nullable', 'boolean'],
            'role_name' => [$canLogin ? 'required' : 'nullable', Rule::exists('roles', 'name')],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'notes' => ['nullable', 'string'],
            'avatar' => ['nullable', 'image', 'max:4096'],
        ];
    }
}
