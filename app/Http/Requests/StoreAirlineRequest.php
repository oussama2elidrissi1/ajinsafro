<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAirlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:airlines,name',
            'iata_code' => 'nullable|string|max:10',
            'logo_url' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active') && !$this->filled('is_active')) {
            $this->merge(['is_active' => false]);
        }
    }
}
