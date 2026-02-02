<?php

namespace App\Services\Sync;

use App\Models\Voyage;
use App\Models\VoyageImage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class WpInboundMapper
{
    public function delete(array $payload): bool
    {
        $voyage = $this->findVoyage($payload);
        if (!$voyage) {
            return false;
        }
        $voyage->delete();
        return true;
    }

    public function upsert(array $payload): ?Voyage
    {
        $voyage = $this->findVoyage($payload);
        if (!$voyage) {
            $voyage = new Voyage();
        }

        $updates = [];

        $this->setIfFilled($updates, 'name', Arr::get($payload, 'title'));
        $this->setIfFilled($updates, 'slug', Arr::get($payload, 'slug'));
        $this->setIfFilled($updates, 'description', Arr::get($payload, 'content'));
        $this->setIfFilled($updates, 'featured_image', Arr::get($payload, 'featured_image'));

        $meta = Arr::get($payload, 'meta', []);
        if (is_array($meta)) {
            $this->setIfFilled($updates, 'price_from', Arr::get($meta, 'price'));
            $this->setIfFilled($updates, 'old_price', Arr::get($meta, 'old_price'));
            $this->setIfFilled($updates, 'duration_text', Arr::get($meta, 'duration_day'));
            $this->setIfFilled($updates, 'destination', Arr::get($meta, 'address'));
            $this->setIfFilled($updates, 'currency', Arr::get($meta, 'currency'));
            $this->setIfFilled($updates, 'min_people', Arr::get($meta, 'min_people'));
            $this->setIfFilled($updates, 'departure_policy', Arr::get($meta, 'departure_policy'));
            $this->setIfFilled($updates, 'status', Arr::get($meta, 'status'));
        }

        if ($this->isFilled(Arr::get($payload, 'wp_post_id'))) {
            $updates['wp_post_id'] = (int) Arr::get($payload, 'wp_post_id');
        }

        if (empty($updates['slug']) && $this->isFilled($updates['name'] ?? null)) {
            $base = Str::slug($updates['name']);
            $slug = $base ?: 'voyage';
            $n = 1;
            while (Voyage::where('slug', $slug)->when($voyage->exists, function ($q) use ($voyage) {
                $q->where('id', '!=', $voyage->id);
            })->exists()) {
                $slug = $base . '-' . $n++;
            }
            $updates['slug'] = $slug;
        }

        if (!empty($updates)) {
            $voyage->fill($updates);
            $voyage->save();
        }

        $this->mergeGallery($voyage, Arr::get($payload, 'gallery'));

        return $voyage->exists ? $voyage : null;
    }

    private function findVoyage(array $payload): ?Voyage
    {
        $wpPostId = Arr::get($payload, 'wp_post_id');
        if ($this->isFilled($wpPostId)) {
            $voyage = Voyage::where('wp_post_id', (int) $wpPostId)->first();
            if ($voyage) {
                return $voyage;
            }
        }

        $slug = Arr::get($payload, 'slug');
        if ($this->isFilled($slug)) {
            return Voyage::where('slug', $slug)->first();
        }

        return null;
    }

    private function mergeGallery(Voyage $voyage, $gallery): void
    {
        if (!$voyage->exists || !is_array($gallery)) {
            return;
        }

        $urls = array_values(array_filter($gallery, fn ($item) => $this->isFilled($item)));
        if (empty($urls)) {
            return;
        }

        $existing = $voyage->images()->pluck('path')->all();
        $sortOrder = ($voyage->images()->max('sort_order') ?? -1) + 1;

        foreach ($urls as $url) {
            if (in_array($url, $existing, true)) {
                continue;
            }
            VoyageImage::create([
                'voyage_id' => $voyage->id,
                'path' => $url,
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    private function setIfFilled(array &$updates, string $field, $value): void
    {
        if ($this->isFilled($value)) {
            $updates[$field] = $value;
        }
    }

    private function isFilled($value): bool
    {
        if (is_null($value)) {
            return false;
        }
        if (is_string($value)) {
            return trim($value) !== '';
        }
        if (is_array($value)) {
            return count($value) > 0;
        }
        return true;
    }
}
