<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
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
            'slug' => 'nullable|string|max:255|unique:wp.aj_activities,slug',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'default_duration_minutes' => 'nullable|integer|min:0',
            'location_text' => 'nullable|string|max:255',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (empty($this->slug) && $this->filled('title')) {
            $this->merge(['slug' => Str::slug($this->title)]);
        }
    }
}
