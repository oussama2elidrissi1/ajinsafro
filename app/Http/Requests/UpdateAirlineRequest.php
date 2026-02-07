<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAirlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $airline = $this->route('airline');
        return [
            'name' => 'required|string|max:255|unique:airlines,name,' . $airline->id,
            'code_iata' => 'nullable|string|max:10',
            'logo_path' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ];
    }
}
