<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $activity = $this->route('activity');
        $activityId = $activity ? $activity->id : 0;
        return [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:wp.aj_activities,slug,' . $activityId . ',id',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'default_duration_minutes' => 'nullable|integer|min:0',
            'location_text' => 'nullable|string|max:255',
        ];
    }
}
