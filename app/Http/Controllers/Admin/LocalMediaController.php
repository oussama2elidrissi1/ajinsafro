<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'max:8192'],
            'context' => ['nullable', 'string', 'max:64'],
        ]);

        $context = isset($validated['context']) ? trim((string) $validated['context']) : '';
        $safeContext = $context !== '' ? Str::slug($context) : 'generic';

        $path = $request->file('image')->store('tour-media/' . $safeContext, 'public');
        $url = Storage::disk('public')->url($path);

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => $url,
        ]);
    }
}

