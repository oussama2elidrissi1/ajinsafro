<?php

namespace App\Http\Controllers\Sync;

use App\Http\Controllers\Controller;
use App\Models\Voyage;
use App\Services\Sync\SyncContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WpToLaravelController extends Controller
{
    protected SyncContext $syncContext;

    public function __construct(SyncContext $syncContext)
    {
        $this->syncContext = $syncContext;
    }

    /**
     * Upsert a tour from WordPress to Laravel.
     *
     * POST /api/sync/wp-to-laravel
     */
    public function upsertTour(Request $request): JsonResponse
    {
        // Validate token
        if (!$this->validateToken($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Validate payload
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:upsert',
            'entity_type' => 'required|in:tour',
            'source' => 'required|in:wp',
            'wp_post_id' => 'required|integer',
            'title' => 'required|string',
            'slug' => 'required|string',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string',
            'destination' => 'nullable|string',
            'duration_text' => 'nullable|string',
            'price_from' => 'nullable|integer',
            'old_price' => 'nullable|integer',
            'currency' => 'nullable|string',
            'status' => 'required|in:publish,draft,pending,private',
            'sync_hash' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->all();

        try {
            // Set sync context to prevent loop
            $this->syncContext->setSource('wp');

            DB::beginTransaction();

            // Find or create voyage by wp_post_id
            $voyage = Voyage::where('wp_post_id', $data['wp_post_id'])->first();

            $voyageData = [
                'wp_post_id' => $data['wp_post_id'],
                'name' => $data['title'],
                'slug' => $this->ensureUniqueSlug($data['slug'], $data['wp_post_id']),
                'description' => $data['content'] ?? null,
                'accroche' => $data['excerpt'] ?? null,
                'destination' => $data['destination'] ?? null,
                'duration_text' => $data['duration_text'] ?? null,
                'price_from' => $data['price_from'] ?? null,
                'old_price' => $data['old_price'] ?? null,
                'currency' => $data['currency'] ?? 'MAD',
                'status' => $this->mapWpStatus($data['status']),
                'wp_synced_at' => now(),
                'wp_sync_hash' => $data['sync_hash'],
            ];

            if ($voyage) {
                // Check if data actually changed
                if ($voyage->wp_sync_hash === $data['sync_hash']) {
                    $this->syncContext->clear();
                    DB::commit();

                    Log::info('[WpToLaravelController] Skipping update - no changes detected', [
                        'wp_post_id' => $data['wp_post_id'],
                        'voyage_id' => $voyage->id,
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'No changes detected, skipped',
                        'voyage_id' => $voyage->id,
                        'action' => 'skipped',
                    ]);
                }

                // Update existing voyage
                $voyage->update($voyageData);
                $action = 'updated';

                Log::info('[WpToLaravelController] Voyage updated from WordPress', [
                    'wp_post_id' => $data['wp_post_id'],
                    'voyage_id' => $voyage->id,
                ]);
            } else {
                // Create new voyage
                $voyage = Voyage::create($voyageData);
                $action = 'created';

                Log::info('[WpToLaravelController] Voyage created from WordPress', [
                    'wp_post_id' => $data['wp_post_id'],
                    'voyage_id' => $voyage->id,
                ]);
            }

            DB::commit();

            // Clear sync context
            $this->syncContext->clear();

            return response()->json([
                'success' => true,
                'message' => ucfirst($action) . ' successfully',
                'voyage_id' => $voyage->id,
                'action' => $action,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->syncContext->clear();

            Log::error('[WpToLaravelController] Error upserting tour from WordPress', [
                'wp_post_id' => $data['wp_post_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a tour from Laravel (triggered by WordPress deletion).
     *
     * POST /api/sync/wp-to-laravel/delete
     */
    public function deleteTour(Request $request): JsonResponse
    {
        // Validate token
        if (!$this->validateToken($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Validate payload
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:delete',
            'entity_type' => 'required|in:tour',
            'source' => 'required|in:wp',
            'wp_post_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Set sync context to prevent loop
            $this->syncContext->setSource('wp');

            $voyage = Voyage::where('wp_post_id', $request->wp_post_id)->first();

            if (!$voyage) {
                $this->syncContext->clear();
                return response()->json([
                    'success' => true,
                    'message' => 'Voyage not found, nothing to delete',
                ]);
            }

            $voyageId = $voyage->id;
            $voyage->delete();

            $this->syncContext->clear();

            Log::info('[WpToLaravelController] Voyage deleted from Laravel', [
                'wp_post_id' => $request->wp_post_id,
                'voyage_id' => $voyageId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Deleted successfully',
                'voyage_id' => $voyageId,
            ]);

        } catch (\Exception $e) {
            $this->syncContext->clear();

            Log::error('[WpToLaravelController] Error deleting tour from Laravel', [
                'wp_post_id' => $request->wp_post_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate sync token.
     *
     * @param Request $request
     * @return bool
     */
    protected function validateToken(Request $request): bool
    {
        $token = $request->bearerToken();
        $expectedToken = config('sync.webhook_token') ?? config('sync.token');

        return $token && $token === $expectedToken;
    }

    /**
     * Map WordPress post status to Laravel status.
     *
     * @param string $wpStatus
     * @return string
     */
    protected function mapWpStatus(string $wpStatus): string
    {
        return match ($wpStatus) {
            'publish' => 'actif',
            'draft', 'pending', 'private' => 'brouillon',
            default => 'brouillon',
        };
    }

    /**
     * Ensure slug uniqueness.
     *
     * @param string $slug
     * @param int $wpPostId
     * @return string
     */
    protected function ensureUniqueSlug(string $slug, int $wpPostId): string
    {
        $exists = Voyage::where('slug', $slug)
            ->where('wp_post_id', '!=', $wpPostId)
            ->exists();

        if ($exists) {
            return $slug . '-' . $wpPostId;
        }

        return $slug;
    }
}
