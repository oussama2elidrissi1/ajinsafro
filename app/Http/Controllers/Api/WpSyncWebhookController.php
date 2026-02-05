<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WpTourSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook endpoint for WordPress to notify Laravel of changes
 * Secured with HMAC signature
 */
class WpSyncWebhookController extends Controller
{
    protected WpTourSyncService $syncService;

    public function __construct(WpTourSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Webhook: WP notifies that a tour was updated
     * 
     * POST /api/wp-sync/tour-updated
     * Headers: X-WP-Signature (HMAC-SHA256)
     * Body: { "wp_post_id": 123, "action": "updated" }
     */
    public function tourUpdated(Request $request)
    {
        // Verify HMAC signature
        if (!$this->verifySignature($request)) {
            Log::warning('WP sync webhook: Invalid signature', [
                'ip' => $request->ip(),
                'body' => $request->all(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $wpPostId = $request->input('wp_post_id');
        $action = $request->input('action', 'updated');

        if (!$wpPostId) {
            return response()->json(['error' => 'wp_post_id required'], 400);
        }

        try {
            Log::info('WP sync webhook received', [
                'wp_post_id' => $wpPostId,
                'action' => $action,
            ]);

            // Pull from WP to Laravel (WP wins)
            $result = $this->syncService->upsertLaravelVoyageFromWp($wpPostId);

            return response()->json([
                'success' => true,
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('WP sync webhook failed', [
                'wp_post_id' => $wpPostId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify HMAC signature
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-WP-Signature');
        
        if (!$signature) {
            return false;
        }

        $secret = config('services.wordpress.webhook_secret');
        
        if (!$secret) {
            Log::error('WP webhook secret not configured');
            return false;
        }

        $body = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $body, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Manual sync trigger (for admin use)
     * GET /api/wp-sync/pull/{wp_post_id}?token=xxx
     */
    public function manualPull(Request $request, int $wpPostId)
    {
        // Simple token auth for manual triggers
        $token = $request->query('token');
        $validToken = config('services.wordpress.manual_sync_token');

        if (!$validToken || $token !== $validToken) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $result = $this->syncService->upsertLaravelVoyageFromWp($wpPostId);

            return response()->json([
                'success' => true,
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
