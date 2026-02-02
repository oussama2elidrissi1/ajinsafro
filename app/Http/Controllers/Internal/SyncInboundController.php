<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\Sync\WpInboundMapper;
use App\Support\SyncContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncInboundController extends Controller
{
    public function wpToLaravel(Request $request, WpInboundMapper $mapper): JsonResponse
    {
        $payload = $request->validate([
            'action' => 'required|in:created,updated,deleted',
            'entity_type' => 'required|in:tour',
            'wp_post_id' => 'nullable|integer',
            'slug' => 'nullable|string',
            'title' => 'nullable|string',
            'content' => 'nullable|string',
            'meta' => 'nullable|array',
            'meta.price' => 'nullable',
            'meta.old_price' => 'nullable',
            'meta.duration_day' => 'nullable',
            'meta.address' => 'nullable|string',
            'meta.currency' => 'nullable|string',
            'meta.min_people' => 'nullable|integer',
            'meta.departure_policy' => 'nullable|string',
            'meta.status' => 'nullable|string',
            'gallery' => 'nullable|array',
            'gallery.*' => 'nullable|string',
            'featured_image' => 'nullable|string',
        ]);

        $origin = strtolower($request->header('X-Sync-Origin', ''));
        if (!empty($origin)) {
            SyncContext::setOrigin($origin);
        }

        $status = 'ok';
        $errors = null;
        $voyageId = null;

        try {
            if ($payload['action'] === 'deleted') {
                $deleted = $mapper->delete($payload);
                $status = $deleted ? 'deleted' : 'not_found';
            } else {
                $voyage = $mapper->upsert($payload);
                $voyageId = $voyage?->id;
                $status = $voyage ? 'saved' : 'not_found';
            }
        } catch (\Throwable $e) {
            $status = 'error';
            $errors = $e->getMessage();
            Log::channel('sync')->error('Inbound sync failed', [
                'action' => $payload['action'] ?? null,
                'entity_type' => $payload['entity_type'] ?? null,
                'wp_post_id' => $payload['wp_post_id'] ?? null,
                'slug' => $payload['slug'] ?? null,
                'error' => $errors,
            ]);
            throw $e;
        } finally {
            SyncContext::clear();
        }

        Log::channel('sync')->info('Inbound sync processed', [
            'action' => $payload['action'] ?? null,
            'entity_type' => $payload['entity_type'] ?? null,
            'wp_post_id' => $payload['wp_post_id'] ?? null,
            'slug' => $payload['slug'] ?? null,
            'status' => $status,
            'voyage_id' => $voyageId,
        ]);

        return response()->json([
            'status' => $status,
            'voyage_id' => $voyageId,
        ]);
    }
}
