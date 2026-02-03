<?php

return [
    // Laravel → WordPress sync
    'wp_sync_url' => env('WP_SYNC_URL'),
    'token' => env('SYNC_TOKEN'),
    
    // WordPress → Laravel webhook token (can be same as token)
    'webhook_token' => env('SYNC_WEBHOOK_TOKEN', env('SYNC_TOKEN')),
    
    'debug' => env('SYNC_DEBUG', false),
];
