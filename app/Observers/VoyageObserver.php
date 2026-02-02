<?php

namespace App\Observers;

use App\Models\Voyage;
use App\Services\Sync\WpSyncService;
use App\Support\SyncContext;

class VoyageObserver
{
    public function created(Voyage $voyage): void
    {
        $this->push($voyage, 'created');
    }

    public function updated(Voyage $voyage): void
    {
        $this->push($voyage, 'updated');
    }

    public function deleted(Voyage $voyage): void
    {
        $this->push($voyage, 'deleted');
    }

    private function push(Voyage $voyage, string $action): void
    {
        if (SyncContext::isFromWp()) {
            return;
        }

        $payload = [
            'action' => $action,
            'entity_type' => 'tour',
            'wp_post_id' => $voyage->wp_post_id,
            'slug' => $voyage->slug,
            'title' => $voyage->name,
            'content' => $voyage->description,
            'meta' => [
                'price' => $voyage->price_from,
                'old_price' => $voyage->old_price,
                'duration_day' => $voyage->duration_text,
                'address' => $voyage->destination,
                'currency' => $voyage->currency,
                'min_people' => $voyage->min_people,
                'departure_policy' => $voyage->departure_policy,
                'status' => $voyage->status,
            ],
            'gallery' => $voyage->images->map(fn ($img) => $img->url)->values()->all(),
            'featured_image' => $voyage->featured_image_url,
        ];

        app(WpSyncService::class)->pushToWp($payload);
    }
}
