<?php

namespace App\Services\Wp;

use App\Models\Wp\WpPost;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class WpFeaturedImageService
{
    public const MAX_FILE_SIZE = 5 * 1024 * 1024;

    public function __construct(
        protected WpHeroImageService $heroImageService,
        protected \App\Services\WordPressMediaService $media,
    ) {}

    public function listAttachments(string $search = '', int $page = 1, int $perPage = 24): LengthAwarePaginator
    {
        $page = max(1, $page);
        $perPage = min(48, max(12, $perPage));

        $query = WpPost::query()
            ->where('post_type', 'attachment')
            ->whereIn('post_mime_type', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

        $search = trim($search);
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('post_title', 'like', '%' . $search . '%')
                    ->orWhere('guid', 'like', '%' . $search . '%');
            });
        }

        return $query
            ->orderByDesc('ID')
            ->paginate($perPage, ['ID', 'post_title', 'guid', 'post_mime_type', 'post_date'], 'page', $page);
    }

    public function uploadAttachment(UploadedFile $file, int $postParentId = 0): array
    {
        return $this->heroImageService->storeUploadAndCreateAttachment($file, $postParentId);
    }

    public function getAttachmentPreviewData(int $attachmentId): ?array
    {
        $attachment = WpPost::query()
            ->where('ID', $attachmentId)
            ->where('post_type', 'attachment')
            ->first();

        if (!$attachment) {
            return null;
        }

        $url = WpHeroImageService::getAttachmentUrl($attachmentId) ?: $attachment->guid;

        return [
            'id' => (int) $attachment->ID,
            'title' => $attachment->post_title ?: ('Attachment #' . $attachment->ID),
            'url' => (string) ($url ?? ''),
            'mime' => (string) ($attachment->post_mime_type ?? ''),
        ];
    }

    public function syncTourThumbnailMeta(int $wpPostId, ?int $attachmentId): void
    {
        $tour = WpPost::tours()->where('ID', $wpPostId)->first();
        if (!$tour) {
            return;
        }

        if ($attachmentId && $attachmentId > 0) {
            $valid = $this->media->validateAttachmentIdForDisplay((int) $attachmentId);
            if ($valid) {
                $tour->setMeta('_thumbnail_id', (string) $valid);
                return;
            }

            // Ne pas écraser une valeur existante si la nouvelle est invalide/non vérifiable.
            return;
        }

        $tour->deleteMeta('_thumbnail_id');
    }
}
