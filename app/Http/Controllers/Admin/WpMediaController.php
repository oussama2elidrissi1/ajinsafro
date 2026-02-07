<?php

namespace App\Http\Controllers\Admin;

use App\Models\Wp\WpPost;
use App\Services\Wp\WpHeroImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WpMediaController
{
    /**
     * GET /admin/wp-media/search?q=...&page=1
     * Search attachments in wp_posts (post_type=attachment). Returns id, title, url, mime with pagination.
     */
    public function search(Request $request): JsonResponse
    {
        $q = $request->input('q', '');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(24, max(12, (int) $request->input('per_page', 24)));

        $query = WpPost::where('post_type', 'attachment')
            ->whereIn('post_mime_type', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

        if ($q !== '') {
            $query->where(function ($qb) use ($q) {
                $qb->where('post_title', 'like', '%' . $q . '%')
                    ->orWhere('guid', 'like', '%' . $q . '%');
            });
        }

        $query->orderByDesc('ID');
        $paginator = $query->paginate($perPage, ['ID', 'post_title', 'guid', 'post_mime_type'], 'page', $page);

        $items = $paginator->getCollection()->map(function ($post) {
            $url = WpHeroImageService::getAttachmentUrl((int) $post->ID) ?: $post->guid;
            return [
                'id' => (int) $post->ID,
                'title' => $post->post_title ?: ('Attachment #' . $post->ID),
                'url' => $url,
                'mime' => $post->post_mime_type ?? '',
            ];
        });

        return response()->json([
            'data' => $items,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }
}
