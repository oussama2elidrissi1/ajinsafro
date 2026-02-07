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
            'name' => 'required|string|max:255',
            'iata_code' => 'nullable|string|max:10',
            'logo_url' => 'nullable|string|max:500',
            'slug' => 'nullable|string|max:255|unique:wp.aj_airlines,slug,' . $airline->id,
            'is_active' => 'nullable|boolean',
        ];
    }
}
