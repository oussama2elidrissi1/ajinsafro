<?php

namespace App\Http\Controllers\Admin;

use App\Models\Wp\WpPost;
use App\Services\Wp\WpHeroImageService;
use App\Services\Wp\WpTourRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeroImageController
{
    public function __construct(
        protected WpHeroImageService $heroImageService,
        protected WpTourRepository $tourRepository
    ) {}

    /**
     * POST /admin/circuits/voyages/{id}/hero-image
     * Upload a file, store in WP uploads, create attachment, set tour hero_image_id.
     */
    public function upload(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'hero_image' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:' . (WpHeroImageService::MAX_FILE_SIZE / 1024), // KB
            ],
        ], [
            'hero_image.required' => 'Veuillez sélectionner une image.',
            'hero_image.image' => 'Le fichier doit être une image (jpg, png ou webp).',
            'hero_image.max' => 'L’image ne doit pas dépasser 5 Mo.',
        ]);

        $tour = $this->tourRepository->findTour($id);

        try {
            $result = $this->heroImageService->storeUploadAndCreateAttachment(
                $request->file('hero_image'),
                (int) $tour->ID
            );
            $attachmentId = $result['attachment_id'];
            $relativePath = $result['relative_path'];
        } catch (\Throwable $e) {
            \Log::error('HeroImageController@upload failed', [
                'tour_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        // Lier l'attachment au tour : image principale (custom) + image à la une WP (_thumbnail_id)
        $tour->setMeta('_tour_hero_image_id', (string) $attachmentId);
        $tour->setMeta('_thumbnail_id', (string) $attachmentId);

        $url = WpHeroImageService::getAttachmentUrl($attachmentId);

        \Log::info('HeroImageController@upload success', [
            'tour_id' => $id,
            'uploads_relative_path' => $relativePath,
            'attachment_id' => $attachmentId,
            'thumbnail_id' => $attachmentId,
        ]);

        return response()->json([
            'success' => true,
            'attachment_id' => $attachmentId,
            'url' => $url,
        ]);
    }

    /**
     * POST /admin/circuits/voyages/{id}/hero-image/select
     * Set tour hero from existing attachment ID.
     */
    public function select(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'attachment_id' => 'required|integer|min:1',
        ]);

        $tour = $this->tourRepository->findTour($id);
        $attachmentId = (int) $request->input('attachment_id');

        $attachment = WpPost::where('ID', $attachmentId)->where('post_type', 'attachment')->first();
        if (!$attachment) {
            return response()->json([
                'success' => false,
                'message' => 'Attachment introuvable.',
            ], 422);
        }

        $tour->setMeta('_tour_hero_image_id', (string) $attachmentId);
        $tour->setMeta('_thumbnail_id', (string) $attachmentId);
        $url = WpHeroImageService::getAttachmentUrl($attachmentId);

        return response()->json([
            'success' => true,
            'attachment_id' => $attachmentId,
            'url' => $url,
        ]);
    }

    /**
     * POST /admin/circuits/voyages/{id}/hero-image/remove
     * Remove hero image from tour (does not delete the attachment).
     */
    public function remove(int $id): JsonResponse
    {
        $tour = $this->tourRepository->findTour($id);
        $tour->deleteMeta('_tour_hero_image_id');
        $tour->deleteMeta('_thumbnail_id');

        return response()->json([
            'success' => true,
            'message' => 'Image principale retirée.',
        ]);
    }
}
