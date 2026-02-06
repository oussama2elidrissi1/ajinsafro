<?php

namespace App\Http\Requests;

use App\Models\Wp\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $activity = $this->route('activity');
        return [
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique(Activity::class, 'slug')->ignore($activity),
            ],
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'default_duration_minutes' => 'nullable|integer|min:0',
            'location_text' => 'nullable|string|max:255',
        ];
    }
}
