<?php

namespace App\Services\Wp;

use App\Models\Wp\WpPost;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class WpTourRepository
{
    /**
     * List all tours with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listTours(int $perPage = 20): LengthAwarePaginator
    {
        return WpPost::tours()
            ->orderByDesc('ID')
            ->paginate($perPage);
    }

    /**
     * Find tour by ID.
     *
     * @param int $id
     * @return WpPost
     */
    public function findTour(int $id): WpPost
    {
        return WpPost::tours()->findOrFail($id);
    }

    /**
     * Find tour by slug.
     *
     * @param string $slug
     * @return WpPost|null
     */
    public function findTourBySlug(string $slug): ?WpPost
    {
        return WpPost::tours()->where('post_name', $slug)->first();
    }

    /**
     * Create a new tour.
     *
     * @param array $data
     * @return WpPost
     */
    public function createTour(array $data): WpPost
    {
        // Prepare post data
        $postData = [
            'post_title' => $data['title'] ?? 'Untitled Tour',
            'post_name' => $this->ensureUniqueSlug($data['slug'] ?? Str::slug($data['title'] ?? 'tour')),
            'post_content' => $data['content'] ?? '',
            'post_excerpt' => $data['excerpt'] ?? $data['accroche'] ?? '',
            'post_status' => $data['post_status'] ?? 'publish',
            'post_type' => 'st_tours',
            'post_author' => $data['author_id'] ?? 1,
            'post_date' => now(),
            'post_date_gmt' => now('UTC'),
            'post_modified' => now(),
            'post_modified_gmt' => now('UTC'),
            'guid' => '', // Will be set after creation
            'comment_status' => 'open',
            'ping_status' => 'open',
        ];

        // Create post
        $post = WpPost::create($postData);

        // Update GUID (WordPress convention)
        $post->update([
            'guid' => config('app.url') . '/?post_type=st_tours&p=' . $post->ID,
        ]);

        // Set metas
        $this->updateTourMetas($post, $data);

        return $post->fresh();
    }

    /**
     * Update an existing tour.
     *
     * @param int $id
     * @param array $data
     * @return WpPost
     */
    public function updateTour(int $id, array $data): WpPost
    {
        $post = $this->findTour($id);

        // Prepare post data
        $postData = [
            'post_modified' => now(),
            'post_modified_gmt' => now('UTC'),
        ];

        if (isset($data['title'])) {
            $postData['post_title'] = $data['title'];
        }

        if (isset($data['slug'])) {
            $newSlug = $this->ensureUniqueSlug($data['slug'], $id);
            $postData['post_name'] = $newSlug;
        }

        if (isset($data['content'])) {
            $postData['post_content'] = $data['content'];
        }

        if (isset($data['excerpt']) || isset($data['accroche'])) {
            $postData['post_excerpt'] = $data['excerpt'] ?? $data['accroche'] ?? '';
        }

        if (isset($data['post_status'])) {
            $postData['post_status'] = $data['post_status'];
        }

        // Update post
        $post->update($postData);

        // Update metas
        $this->updateTourMetas($post, $data);

        return $post->fresh();
    }

    /**
     * Delete a tour.
     *
     * @param int $id
     * @return bool
     */
    public function deleteTour(int $id): bool
    {
        $post = $this->findTour($id);

        // Delete all metas first
        $post->metas()->delete();

        // Delete post
        return $post->delete();
    }

    /**
     * Update tour metas from data array.
     *
     * @param WpPost $post
     * @param array $data
     * @return void
     */
    protected function updateTourMetas(WpPost $post, array $data): void
    {
        $metaMapping = [
            'destination' => 'address',
            'duration_text' => 'duration_day',
            'adult_price' => 'adult_price',
            'child_price' => 'child_price',
            'min_price' => 'min_price',
            'min_people' => 'min_people',
            'gallery_ids' => 'gallery',
            'thumbnail_id' => '_thumbnail_id',
            'status' => 'status', // Laravel status if needed
        ];

        foreach ($metaMapping as $inputKey => $metaKey) {
            if (array_key_exists($inputKey, $data)) {
                $value = $data[$inputKey];
                
                // Handle null values
                if ($value === null || $value === '') {
                    continue;
                }

                // Special handling for gallery (array to CSV)
                if ($inputKey === 'gallery_ids' && is_array($value)) {
                    $value = implode(',', $value);
                }

                $post->setMeta($metaKey, $value);
            }
        }

        // Handle featured image separately if provided
        if (isset($data['featured_image'])) {
            $post->setMeta('_thumbnail_id', $data['featured_image']);
        }
    }

    /**
     * Ensure slug is unique by appending number if needed.
     *
     * @param string $slug
     * @param int|null $excludeId
     * @return string
     */
    protected function ensureUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $originalSlug = $slug;
        $counter = 2;

        while (true) {
            $query = WpPost::where('post_name', $slug)
                ->where('post_type', 'st_tours');

            if ($excludeId) {
                $query->where('ID', '!=', $excludeId);
            }

            if (!$query->exists()) {
                return $slug;
            }

            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
    }

    /**
     * Get tour with all metas loaded.
     *
     * @param int $id
     * @return array
     */
    public function getTourWithMetas(int $id): array
    {
        $post = $this->findTour($id);
        $metas = $post->getAllMetas();

        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'status' => $post->post_status,
            'author_id' => $post->post_author,
            'created_at' => $post->post_date,
            'updated_at' => $post->post_modified,
            'destination' => $metas['address'] ?? null,
            'duration_text' => $metas['duration_day'] ?? null,
            'adult_price' => $metas['adult_price'] ?? null,
            'child_price' => $metas['child_price'] ?? null,
            'min_price' => $metas['min_price'] ?? null,
            'min_people' => $metas['min_people'] ?? null,
            'thumbnail_id' => $metas['_thumbnail_id'] ?? null,
            'gallery' => isset($metas['gallery']) ? explode(',', $metas['gallery']) : [],
            'metas' => $metas,
        ];
    }
}
