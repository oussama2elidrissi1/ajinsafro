<?php

namespace App\Http\Controllers\Admin;

use App\Services\Wp\WpFeaturedImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WpMediaController
{
    public function __construct(
        protected WpFeaturedImageService $featuredImageService
    ) {}

    /**
     * GET /admin/wp-media/search?q=...&page=1
     * Search attachments in wp_posts (post_type=attachment). Returns id, title, url, mime with pagination.
     */
    public function search(Request $request): JsonResponse
    {
        return $this->list($request);
    }

    /**
     * GET /admin/wp-media/list?search=&page=
     */
    public function list(Request $request): JsonResponse
    {
        $q = $request->input('search', $request->input('q', ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(24, max(12, (int) $request->input('per_page', 24)));

        $paginator = $this->featuredImageService->listAttachments($q, $page, $perPage);

        $items = collect($paginator->items())->map(function ($post) {
            return $this->featuredImageService->getAttachmentPreviewData((int) $post->ID);
        })->filter()->values();

        return response()->json([
            'data' => $items,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * POST /admin/wp-media/upload
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:' . (WpFeaturedImageService::MAX_FILE_SIZE / 1024),
            ],
            'post_parent_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $result = $this->featuredImageService->uploadAttachment(
            $request->file('image'),
            (int) $request->input('post_parent_id', 0)
        );

        $attachmentId = (int) ($result['attachment_id'] ?? 0);
        $preview = $this->featuredImageService->getAttachmentPreviewData($attachmentId);

        return response()->json([
            'success' => true,
            'attachment_id' => $attachmentId,
            'url' => $preview['url'] ?? null,
            'title' => $preview['title'] ?? null,
            'mime' => $preview['mime'] ?? null,
            'relative_path' => $result['relative_path'] ?? null,
        ]);
    }

    /**
     * POST /admin/wp-media/select
     */
    public function select(Request $request): JsonResponse
    {
        $request->validate([
            'attachment_id' => ['required', 'integer', 'min:1'],
            'wp_post_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $attachmentId = (int) $request->input('attachment_id');
        $preview = $this->featuredImageService->getAttachmentPreviewData($attachmentId);

        if (!$preview) {
            return response()->json([
                'success' => false,
                'message' => 'Média WordPress introuvable.',
            ], 422);
        }

        $wpPostId = (int) $request->input('wp_post_id', 0);
        if ($wpPostId > 0) {
            $this->featuredImageService->syncTourThumbnailMeta($wpPostId, $attachmentId);
        }

        return response()->json([
            'success' => true,
            'attachment_id' => $attachmentId,
            'url' => $preview['url'] ?? null,
            'title' => $preview['title'] ?? null,
            'mime' => $preview['mime'] ?? null,
        ]);
    }

    /**
     * POST /admin/wp-media/remove
     */
    public function remove(Request $request): JsonResponse
    {
        $request->validate([
            'wp_post_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $wpPostId = (int) $request->input('wp_post_id', 0);
        if ($wpPostId > 0) {
            $this->featuredImageService->syncTourThumbnailMeta($wpPostId, null);
        }

        return response()->json([
            'success' => true,
            'attachment_id' => null,
            'url' => null,
        ]);
    }

    /**
     * GET /admin/wp-media/get/{id}
     */
    public function get(int $id): JsonResponse
    {
        $preview = $this->featuredImageService->getAttachmentPreviewData($id);

        if (!$preview) {
            return response()->json([
                'success' => false,
                'message' => 'Média WordPress introuvable.',
            ], 404);
        }

        return response()->json(array_merge(['success' => true], $preview));
    }
}
