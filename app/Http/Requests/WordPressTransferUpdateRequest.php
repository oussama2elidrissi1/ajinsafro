<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WordPressTransferUpdateRequest extends FormRequest
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
            'cars_address' => ['nullable', 'string', 'max:255'],
            'cars_price' => ['nullable', 'numeric', 'min:0'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'number_car' => ['nullable', 'integer', 'min:1'],
            'is_featured' => ['nullable', 'in:on,off'],
            'featured_image' => $imageRule,
            'aj_transfer_from' => ['nullable', 'string', 'max:255'],
            'aj_transfer_to' => ['nullable', 'string', 'max:255'],
            'aj_transfer_type' => ['nullable', 'string', 'max:120'],
            'aj_transfer_capacity' => ['nullable', 'integer', 'min:1'],
            'aj_transfer_vehicle_type' => ['nullable', 'string', 'max:120'],
        ];
    }
}
