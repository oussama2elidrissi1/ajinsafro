<?php

namespace App\Http\Requests;

use App\Models\Wp\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Contracts\Validation\Validator;

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
            'activity_type' => 'nullable|string|max:120',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique(Activity::class, 'slug'),
            ],
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120',
            'gallery_state_present' => 'nullable|boolean',
            'existing_gallery_image_ids' => 'nullable|array',
            'existing_gallery_image_ids.*' => 'integer|min:1',
            'adult_price' => 'required|numeric|min:0',
            'child_price' => 'nullable|numeric|min:0',
            'min_age' => 'nullable|integer|min:0|max:120',
            'max_age' => 'nullable|integer|min:0|max:120|gte:min_age',
            'default_duration_minutes' => 'nullable|integer|min:1|max:10080',
            'region_name' => 'required|string|max:255',
            'location_text' => 'nullable|string|max:255',
            'place_text' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Images are optional for quick-create from voyage editor
            if ($this->allowsEmptyGallery() || $this->boolean('is_quick_create')) {
                return;
            }

            $existing = collect($this->input('existing_gallery_image_ids', []))
                ->filter(fn ($id) => (int) $id > 0)
                ->count();

            if (! $this->hasFile('image')
                && ! $this->hasFile('gallery_images')
                && $existing === 0) {
                $validator->errors()->add('gallery_images', 'Ajoutez au moins une image a la galerie.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $region = $this->filled('region_name')
            ? $this->input('region_name')
            : ($this->filled('location_text') ? $this->input('location_text') : $this->input('place_text'));

        if ($region) {
            $this->merge([
                'region_name' => trim((string) $region),
                'location_text' => trim((string) $region),
                'place_text' => trim((string) $region),
            ]);
        }

        if ($this->has('is_active') && ! $this->filled('is_active')) {
            $this->merge(['is_active' => false]);
        }

        if (empty($this->slug) && $this->filled('title')) {
            $this->merge(['slug' => Str::slug($this->title)]);
        }

        if ($this->filled('adult_price')) {
            $this->merge(['base_price' => $this->input('adult_price')]);
        }
    }

    private function allowsEmptyGallery(): bool
    {
        return $this->boolean('allow_empty_gallery');
    }
}
