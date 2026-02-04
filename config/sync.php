<?php

return [
    // Laravel → WordPress sync
    'wp_sync_url' => env('WP_SYNC_URL'),
    
    // Authentication
    'token' => env('SYNC_TOKEN'), // Optional Bearer token
    'secret' => env('SYNC_SECRET'), // HMAC secret (required)
    
    // WordPress → Laravel webhook token (can be same as token)
    'webhook_token' => env('SYNC_WEBHOOK_TOKEN', env('SYNC_TOKEN')),
    'webhook_secret' => env('SYNC_WEBHOOK_SECRET', env('SYNC_SECRET')),
    
    'debug' => env('SYNC_DEBUG', false),
];
