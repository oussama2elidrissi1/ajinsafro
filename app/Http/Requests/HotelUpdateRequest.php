<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HotelUpdateRequest extends FormRequest
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
            'post_content' => ['nullable', 'string'],
            'post_status' => ['required', 'in:publish,draft'],
            'post_name' => ['nullable', 'string', 'max:200'],
            'address' => ['nullable', 'string'],
            'hotel_star' => ['nullable', 'integer', 'min:1', 'max:5'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'map_lat' => ['nullable', 'string', 'max:255'],
            'map_lng' => ['nullable', 'string', 'max:255'],
            'is_featured' => ['nullable', 'in:on,off'],
            'featured_image' => $imageRule,
            'gallery_images' => ['nullable', 'array', 'max:10'],
            'gallery_images.*' => $imageRule,
            'gallery_keep_ids' => ['nullable', 'array'],
            'gallery_keep_ids.*' => ['integer', 'min:1'],
            'hotel_amenities' => ['nullable', 'string'],
            'hotel_policies' => ['nullable', 'string'],
            'hotel_phone' => ['nullable', 'string', 'max:100'],
            'hotel_email' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'post_title' => 'titre',
            'post_content' => 'contenu',
            'post_status' => 'statut',
            'post_name' => 'slug',
            'address' => 'adresse',
            'hotel_star' => 'étoiles',
            'min_price' => 'prix minimum',
            'map_lat' => 'latitude',
            'map_lng' => 'longitude',
            'is_featured' => 'à la une',
            'featured_image' => 'image à la une',
            'gallery_images' => 'galerie',
            'gallery_keep_ids' => 'galerie conservée',
            'hotel_amenities' => 'équipements',
            'hotel_policies' => 'politiques',
            'hotel_phone' => 'téléphone',
            'hotel_email' => 'email',
        ];
    }
}
