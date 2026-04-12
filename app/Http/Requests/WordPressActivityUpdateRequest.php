<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WordPressActivityUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $imageRule = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];

        return [
            'post_title' => ['required', 'string', 'max:255'],
            'post_excerpt' => ['nullable', 'string', 'max:500'],
            'post_content' => ['nullable', 'string'],
            'post_status' => ['required', 'in:publish,draft'],
            'post_name' => ['nullable', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:255'],
            'type_activity' => ['nullable', 'string', 'max:120'],
            'adult_price' => ['nullable', 'numeric', 'min:0'],
            'child_price' => ['nullable', 'numeric', 'min:0'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'duration' => ['nullable', 'string', 'max:120'],
            'max_people' => ['nullable', 'integer', 'min:1'],
            'rate_review' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'is_featured' => ['nullable', 'in:on,off'],
            'featured_image' => $imageRule,
            'gallery_images' => ['nullable', 'array', 'max:10'],
            'gallery_images.*' => $imageRule,
            'gallery_keep_ids' => ['nullable', 'array'],
            'gallery_keep_ids.*' => ['integer', 'min:1'],
            'aj_activity_category' => ['nullable', 'string', 'max:120'],
            'aj_activity_place_text' => ['nullable', 'string', 'max:255'],
            'aj_activity_min_age' => ['nullable', 'integer', 'min:0'],
            'aj_activity_max_age' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
