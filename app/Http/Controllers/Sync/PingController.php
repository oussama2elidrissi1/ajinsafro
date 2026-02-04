<?php

namespace App\Http\Controllers\Sync;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PingController extends Controller
{
    /**
     * POST /api/sync/ping
     * 
     * Test endpoint to verify HMAC authentication.
     */
    public function ping(Request $request): JsonResponse
    {
        // Validate HMAC signature
        if (!$this->validateHmac($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid HMAC signature',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ping successful - Laravel sync endpoint working',
            'timestamp' => now()->toIso8601String(),
            'source' => 'laravel',
        ]);
    }

    /**
     * Validate HMAC signature.
     *
     * @param Request $request
     * @return bool
     */
    protected function validateHmac(Request $request): bool
    {
        $secret = config('sync.webhook_secret') ?? config('sync.secret');
        
        if (empty($secret)) {
            Log::error('[PingController] SYNC_SECRET not configured');
            return false;
        }

        $signature = $request->header('X-AJ-Signature');
        
        if (empty($signature)) {
            Log::warning('[PingController] Missing X-AJ-Signature header');
            return false;
        }

        $body = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $body, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
