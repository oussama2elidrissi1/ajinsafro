<?php

namespace App\Http\Requests;

use App\Models\Wp\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique(Activity::class, 'slug'),
            ],
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120', // 5MB max
            'base_price' => 'nullable|numeric|min:0',
            'default_duration_minutes' => 'nullable|integer|min:0',
            'location_text' => 'nullable|string|max:255',
            'place_text' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->filled('location_text') && $this->filled('place_text')) {
            $this->merge(['location_text' => $this->input('place_text')]);
        }

        if ($this->has('is_active') && !$this->filled('is_active')) {
            $this->merge(['is_active' => false]);
        }

        if (empty($this->slug) && $this->filled('title')) {
            $this->merge(['slug' => Str::slug($this->title)]);
        }
    }
}
