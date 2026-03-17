<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHotelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('accommodations.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],

            'images' => ['nullable', 'array', 'max:15'],
            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'primary_image_id' => ['nullable', 'integer'],
            'keep_image_ids' => ['nullable', 'array'],
            'keep_image_ids.*' => ['integer'],

            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['integer', 'exists:hotel_amenities,id'],

            'room_types' => ['nullable', 'array'],
            'room_types.*.id' => ['nullable', 'integer'],
            'room_types.*.name' => ['required_with:room_types', 'string', 'max:120'],
            'room_types.*.capacity_adults' => ['nullable', 'integer', 'min:1', 'max:10'],
            'room_types.*.capacity_children' => ['nullable', 'integer', 'min:0', 'max:10'],
            'room_types.*.quantity' => ['nullable', 'integer', 'min:0'],
            'room_types.*.base_price' => ['nullable', 'numeric', 'min:0'],
            'room_types.*.currency' => ['nullable', 'string', 'max:10'],
            'room_types.*.description' => ['nullable', 'string'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        $data['is_active'] = $this->boolean('is_active', true);
        return $data;
    }
}
