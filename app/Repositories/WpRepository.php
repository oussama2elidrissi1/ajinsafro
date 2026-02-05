<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * WordPress Database Repository
 * Reads and writes to WordPress tables (cFdgeZ_ prefix)
 */
class WpRepository
{
    protected string $prefix = 'cFdgeZ_';
    protected string $connection = 'wp';

    /**
     * Get WP post by ID
     */
    public function getPost(int $postId): ?array
    {
        $post = DB::connection($this->connection)
            ->table($this->prefix . 'posts')
            ->where('ID', $postId)
            ->first();

        return $post ? (array)$post : null;
    }

    /**
     * Create new WP post
     */
    public function createPost(array $data): int
    {
        $now = Carbon::now();
        $nowGmt = Carbon::now('GMT');

        $defaults = [
            'post_date' => $now->toDateTimeString(),
            'post_date_gmt' => $nowGmt->toDateTimeString(),
            'post_modified' => $now->toDateTimeString(),
            'post_modified_gmt' => $nowGmt->toDateTimeString(),
            'post_status' => 'draft',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_password' => '',
            'to_ping' => '',
            'pinged' => '',
            'post_content_filtered' => '',
            'post_parent' => 0,
            'guid' => '',
            'menu_order' => 0,
            'post_type' => 'post',
            'post_mime_type' => '',
            'comment_count' => 0,
        ];

        $postData = array_merge($defaults, $data);

        $postId = DB::connection($this->connection)
            ->table($this->prefix . 'posts')
            ->insertGetId($postData);

        // Update GUID if not set
        if (empty($data['guid'])) {
            $this->updatePost($postId, [
                'guid' => get_option('siteurl') . '/?post_type=' . $postData['post_type'] . '&p=' . $postId
            ]);
        }

        return $postId;
    }

    /**
     * Update WP post
     */
    public function updatePost(int $postId, array $data): bool
    {
        $nowGmt = Carbon::now('GMT');
        
        // Always update modified time
        $data['post_modified'] = Carbon::now()->toDateTimeString();
        $data['post_modified_gmt'] = $nowGmt->toDateTimeString();

        return DB::connection($this->connection)
            ->table($this->prefix . 'posts')
            ->where('ID', $postId)
            ->update($data) > 0;
    }

    /**
     * Delete WP post
     */
    public function deletePost(int $postId): bool
    {
        return DB::connection($this->connection)
            ->table($this->prefix . 'posts')
            ->where('ID', $postId)
            ->delete() > 0;
    }

    /**
     * Get single post meta
     */
    public function getPostMeta(int $postId, string $metaKey): mixed
    {
        $result = DB::connection($this->connection)
            ->table($this->prefix . 'postmeta')
            ->where('post_id', $postId)
            ->where('meta_key', $metaKey)
            ->value('meta_value');

        return $result ? $this->maybeUnserialize($result) : null;
    }

    /**
     * Get all post metas
     */
    public function getAllPostMeta(int $postId): array
    {
        $metas = DB::connection($this->connection)
            ->table($this->prefix . 'postmeta')
            ->where('post_id', $postId)
            ->get(['meta_key', 'meta_value']);

        $result = [];
        foreach ($metas as $meta) {
            $result[$meta->meta_key] = $this->maybeUnserialize($meta->meta_value);
        }

        return $result;
    }

    /**
     * Update post meta (upsert)
     */
    public function updatePostMeta(int $postId, string $metaKey, mixed $metaValue): void
    {
        $serialized = is_array($metaValue) || is_object($metaValue) 
            ? serialize($metaValue) 
            : $metaValue;

        $existing = DB::connection($this->connection)
            ->table($this->prefix . 'postmeta')
            ->where('post_id', $postId)
            ->where('meta_key', $metaKey)
            ->exists();

        if ($existing) {
            DB::connection($this->connection)
                ->table($this->prefix . 'postmeta')
                ->where('post_id', $postId)
                ->where('meta_key', $metaKey)
                ->update(['meta_value' => $serialized]);
        } else {
            DB::connection($this->connection)
                ->table($this->prefix . 'postmeta')
                ->insert([
                    'post_id' => $postId,
                    'meta_key' => $metaKey,
                    'meta_value' => $serialized,
                ]);
        }
    }

    /**
     * Delete post meta
     */
    public function deletePostMeta(int $postId, string $metaKey): bool
    {
        return DB::connection($this->connection)
            ->table($this->prefix . 'postmeta')
            ->where('post_id', $postId)
            ->where('meta_key', $metaKey)
            ->delete() > 0;
    }

    /**
     * Get term by name and taxonomy
     */
    public function getTermByName(string $name, string $taxonomy): ?array
    {
        $term = DB::connection($this->connection)
            ->table($this->prefix . 'terms as t')
            ->join($this->prefix . 'term_taxonomy as tt', 't.term_id', '=', 'tt.term_id')
            ->where('t.name', $name)
            ->where('tt.taxonomy', $taxonomy)
            ->select('t.*', 'tt.term_taxonomy_id')
            ->first();

        return $term ? (array)$term : null;
    }

    /**
     * Create term if not exists
     */
    public function createTerm(string $name, string $taxonomy, string $slug = ''): int
    {
        // Check if term exists
        $existing = $this->getTermByName($name, $taxonomy);
        if ($existing) {
            return $existing['term_id'];
        }

        // Create term
        $termId = DB::connection($this->connection)
            ->table($this->prefix . 'terms')
            ->insertGetId([
                'name' => $name,
                'slug' => $slug ?: \Illuminate\Support\Str::slug($name),
            ]);

        // Create term taxonomy
        DB::connection($this->connection)
            ->table($this->prefix . 'term_taxonomy')
            ->insert([
                'term_id' => $termId,
                'taxonomy' => $taxonomy,
                'description' => '',
                'parent' => 0,
                'count' => 0,
            ]);

        return $termId;
    }

    /**
     * Set post terms (replaces existing)
     */
    public function setPostTerms(int $postId, string $taxonomy, array $termNames): void
    {
        // Get term_taxonomy_ids for this taxonomy
        $termTaxonomyIds = DB::connection($this->connection)
            ->table($this->prefix . 'term_taxonomy')
            ->where('taxonomy', $taxonomy)
            ->pluck('term_taxonomy_id', 'term_id');

        // Delete existing relationships
        DB::connection($this->connection)
            ->table($this->prefix . 'term_relationships')
            ->where('object_id', $postId)
            ->whereIn('term_taxonomy_id', $termTaxonomyIds->values())
            ->delete();

        // Add new relationships
        foreach ($termNames as $termName) {
            $termId = $this->createTerm($termName, $taxonomy);
            $termTaxonomyId = $termTaxonomyIds[$termId] ?? null;

            if ($termTaxonomyId) {
                DB::connection($this->connection)
                    ->table($this->prefix . 'term_relationships')
                    ->insert([
                        'object_id' => $postId,
                        'term_taxonomy_id' => $termTaxonomyId,
                        'term_order' => 0,
                    ]);
            }
        }

        // Update term counts
        $this->updateTermCounts($taxonomy);
    }

    /**
     * Get post terms
     */
    public function getPostTerms(int $postId, string $taxonomy): array
    {
        $terms = DB::connection($this->connection)
            ->table($this->prefix . 'terms as t')
            ->join($this->prefix . 'term_taxonomy as tt', 't.term_id', '=', 'tt.term_id')
            ->join($this->prefix . 'term_relationships as tr', 'tt.term_taxonomy_id', '=', 'tr.term_taxonomy_id')
            ->where('tr.object_id', $postId)
            ->where('tt.taxonomy', $taxonomy)
            ->select('t.name', 't.slug', 't.term_id')
            ->get();

        return $terms->pluck('name')->toArray();
    }

    /**
     * Update term counts
     */
    protected function updateTermCounts(string $taxonomy): void
    {
        $counts = DB::connection($this->connection)
            ->table($this->prefix . 'term_relationships as tr')
            ->join($this->prefix . 'term_taxonomy as tt', 'tr.term_taxonomy_id', '=', 'tt.term_taxonomy_id')
            ->where('tt.taxonomy', $taxonomy)
            ->groupBy('tt.term_taxonomy_id')
            ->selectRaw('tt.term_taxonomy_id, COUNT(*) as count')
            ->get();

        foreach ($counts as $count) {
            DB::connection($this->connection)
                ->table($this->prefix . 'term_taxonomy')
                ->where('term_taxonomy_id', $count->term_taxonomy_id)
                ->update(['count' => $count->count]);
        }
    }

    /**
     * Get attachment URL
     */
    public function getAttachmentUrl(int $attachmentId): ?string
    {
        $post = $this->getPost($attachmentId);
        
        if (!$post || $post['post_type'] !== 'attachment') {
            return null;
        }

        $guid = $post['guid'] ?? null;
        
        // Validate GUID is a URL
        if ($guid && (str_starts_with($guid, 'http://') || str_starts_with($guid, 'https://'))) {
            return $guid;
        }

        return null;
    }

    /**
     * Get option
     */
    public function getOption(string $optionName, mixed $default = null): mixed
    {
        $value = DB::connection($this->connection)
            ->table($this->prefix . 'options')
            ->where('option_name', $optionName)
            ->value('option_value');

        if ($value === null) {
            return $default;
        }

        return $this->maybeUnserialize($value);
    }

    /**
     * Helper: maybe unserialize
     */
    protected function maybeUnserialize(mixed $data): mixed
    {
        if (!is_string($data)) {
            return $data;
        }

        // Check if serialized
        if (preg_match('/^[aOs]:\d+:/', $data)) {
            $unserialized = @unserialize($data);
            return $unserialized !== false ? $unserialized : $data;
        }

        return $data;
    }

    /**
     * Find tours by criteria
     */
    public function findTours(array $criteria = [], int $limit = 100): array
    {
        $query = DB::connection($this->connection)
            ->table($this->prefix . 'posts')
            ->where('post_type', 'st_tours');

        if (isset($criteria['post_status'])) {
            $query->where('post_status', $criteria['post_status']);
        }

        if (isset($criteria['search'])) {
            $search = '%' . $criteria['search'] . '%';
            $query->where(function($q) use ($search) {
                $q->where('post_title', 'like', $search)
                  ->orWhere('post_name', 'like', $search);
            });
        }

        $posts = $query->limit($limit)->get();

        return $posts->map(fn($post) => (array)$post)->toArray();
    }
}
