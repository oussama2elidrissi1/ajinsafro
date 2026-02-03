<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTravelDayItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'day_number' => 'required|integer|min:1',
            'start_day' => 'nullable|integer|min:1',
            'end_day' => 'nullable|integer|min:1|gte:start_day',
            'nights' => 'nullable|integer|min:0',
            'type' => 'required|string|in:flight,hotel_stay,transfer,activity,meal,addon',
            'title' => 'required|string|max:255',
            'details' => 'nullable|string',
            'included' => 'nullable|boolean',
            'price_delta_per_person' => 'nullable|integer',
            'options_json' => 'nullable|json',
            'meta_json' => 'nullable|json',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'day_number.required' => 'Le numéro du jour est requis.',
            'type.required' => 'Le type d\'item est requis.',
            'type.in' => 'Le type doit être: flight, hotel_stay, transfer, activity, meal ou addon.',
            'title.required' => 'Le titre est requis.',
            'end_day.gte' => 'La fin doit être >= au début.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Convert string booleans to actual booleans
        if ($this->has('included')) {
            $this->merge([
                'included' => filter_var($this->input('included'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // Convert price to cents if provided in decimal format
        if ($this->has('price_delta_per_person') && is_numeric($this->input('price_delta_per_person'))) {
            $value = $this->input('price_delta_per_person');
            // If it looks like a decimal (contains dot), convert to cents
            if (strpos((string)$value, '.') !== false) {
                $this->merge([
                    'price_delta_per_person' => (int) round($value * 100),
                ]);
            }
        }
    }
}
