<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class LocalMediaController extends Controller
{
    /**
     * Upload an image to Laravel public disk (no WordPress attachment involved).
     *
     * POST /admin/local-media/upload
     * - image: file (required, image)
     * - context: string (optional) ex: tour_hotel, transfer
     */
    public function upload(Request $request): JsonResponse
    {
        $file = $request->file('image')
            ?? $request->file('file')
            ?? $request->file('media')
            ?? $request->file('upload');

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'Image invalide. Formats acceptés : JPG, PNG, WEBP. Taille max : 5 Mo.',
            ], 422);
        }

        $validator = Validator::make(
            [
                'image' => $file,
                'context' => $request->input('context'),
            ],
            [
                'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'context' => ['nullable', 'string', 'max:64'],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Image invalide. Formats acceptés : JPG, PNG, WEBP. Taille max : 5 Mo.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $context = isset($validated['context']) ? trim((string) $validated['context']) : '';
        $safeContext = $context !== '' ? Str::slug($context) : 'generic';

        $path = $file->store('voyages/local-media/'.$safeContext, 'public');
        $url = asset(Storage::disk('public')->url($path));

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => $url,
        ]);
    }
}
