<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\HotelImage;
use App\Models\HotelRoomType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HotelService
{
    public function create(array $data): Hotel
    {
        return DB::transaction(function () use ($data) {
            $hotel = Hotel::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->syncAmenities($hotel, $data['amenities'] ?? []);
            $this->syncImages($hotel, $data['images'] ?? [], $data['primary_image_index'] ?? null);
            $this->syncRoomTypes($hotel, $data['room_types'] ?? []);

            return $hotel->fresh(['images', 'roomTypes', 'amenities']);
        });
    }

    public function update(Hotel $hotel, array $data): Hotel
    {
        return DB::transaction(function () use ($hotel, $data) {
            $hotel->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->syncAmenities($hotel, $data['amenities'] ?? []);
            $this->updateImages($hotel, $data);
            $this->syncRoomTypes($hotel, $data['room_types'] ?? []);

            return $hotel->fresh(['images', 'roomTypes', 'amenities']);
        });
    }

    private function syncAmenities(Hotel $hotel, array $ids): void
    {
        $ids = array_filter(array_map('intval', $ids));
        $hotel->amenities()->sync($ids);
    }

    /**
     * @param array<int,UploadedFile> $files
     */
    private function syncImages(Hotel $hotel, array $files, ?int $primaryIndex): void
    {
        $paths = [];
        foreach ($files as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $paths[] = [
                'file_path' => $this->storeImage($file),
                'position' => $index,
                'is_primary' => $primaryIndex !== null && $primaryIndex === $index,
            ];
        }

        if (empty($paths)) {
            return;
        }

        foreach ($paths as $payload) {
            $image = $hotel->images()->create($payload);
            if ($payload['is_primary'] && empty($hotel->main_image_path)) {
                $hotel->main_image_path = $image->file_path;
                $hotel->save();
            }
        }
    }

    private function updateImages(Hotel $hotel, array $data): void
    {
        $keepIds = collect($data['keep_image_ids'] ?? [])->map('intval')->filter()->values()->all();
        $primaryId = isset($data['primary_image_id']) ? (int) $data['primary_image_id'] : null;

        // Remove deleted images
        foreach ($hotel->images as $img) {
            if (! in_array($img->id, $keepIds, true)) {
                Storage::disk('public')->delete($img->file_path);
                $img->delete();
            }
        }

        // New uploads
        $this->syncImages($hotel, $data['images'] ?? [], $data['primary_image_index'] ?? null);

        // Recompute main_image_path + is_primary
        $hotel->load('images');
        $hotel->images()->update(['is_primary' => false]);
        $primaryImage = null;
        if ($primaryId) {
            $primaryImage = $hotel->images->firstWhere('id', $primaryId);
        }
        if (! $primaryImage && $hotel->images->isNotEmpty()) {
            $primaryImage = $hotel->images->first();
        }
        if ($primaryImage) {
            $primaryImage->update(['is_primary' => true]);
            $hotel->main_image_path = $primaryImage->file_path;
            $hotel->save();
        }
    }

    private function storeImage(UploadedFile $file): string
    {
        $directory = 'hotels/' . date('Y/m');
        $name = uniqid('hotel_', true) . '.' . $file->getClientOriginalExtension();
        Storage::disk('public')->putFileAs($directory, $file, $name);
        return $directory . '/' . $name;
    }

    private function syncRoomTypes(Hotel $hotel, array $rows): void
    {
        $keepIds = [];

        foreach ($rows as $position => $row) {
            if (! is_array($row)) {
                continue;
            }

            $id = isset($row['id']) ? (int) $row['id'] : 0;

            // Si c'est une MAJ (id existant), on autorise l'absence de `name`
            // (sinon on perd les updates de capacité/prix quand le champ Type est vide côté UI).
            $existingRoom = null;
            if ($id > 0) {
                $existingRoom = HotelRoomType::where('hotel_id', $hotel->id)->where('id', $id)->first();
                if (! $existingRoom) {
                    $id = 0; // traiter comme création
                }
            }

            // Pour une CREATION, `name` doit être présent.
            if ($id === 0 && empty($row['name'])) {
                continue;
            }

            $roomName = !empty($row['name']) ? $row['name'] : ($existingRoom?->name ?? '');
            if (trim((string) $roomName) === '') {
                continue;
            }

            $payload = [
                'name' => $roomName,
                'code' => $row['code'] ?? null,
                'capacity_adults' => (int) ($row['capacity_adults'] ?? 2),
                'capacity_children' => (int) ($row['capacity_children'] ?? 0),
                'quantity' => (int) ($row['quantity'] ?? 0),
                'base_price' => $row['base_price'] ?? null,
                'currency' => $row['currency'] ?? 'MAD',
                'description' => $row['description'] ?? null,
                'position' => $position,
            ];

            if ($id > 0) {
                $existingRoom->update($payload);
                $keepIds[] = $existingRoom->id;
                continue;
            }

            $room = $hotel->roomTypes()->create($payload);
            $keepIds[] = $room->id;
        }

        if (! empty($keepIds)) {
            $hotel->roomTypes()->whereNotIn('id', $keepIds)->delete();
        }
    }
}

