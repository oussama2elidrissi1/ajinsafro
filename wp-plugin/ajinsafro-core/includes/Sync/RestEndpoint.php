<?php

namespace Ajinsafro\Sync;

use Ajinsafro\Core\Options;

class RestEndpoint
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        register_rest_route('ajinsafro-sync/v1', '/laravel-to-wp', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_sync'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
    }

    public function check_permission(\WP_REST_Request $request)
    {
        // Check if sync is enabled
        if (!Options::get('enable_sync')) {
            return new \WP_Error('sync_disabled', __('Sync is disabled', 'ajinsafro-core'), ['status' => 403]);
        }

        $hmac_secret = Options::get('hmac_secret');
        
        if (empty($hmac_secret)) {
            return new \WP_Error('no_secret', __('HMAC secret not configured', 'ajinsafro-core'), ['status' => 500]);
        }

        // Verify HMAC signature
        $signature = $request->get_header('X-AJ-Signature');
        $body = $request->get_body();

        if (empty($signature)) {
            return new \WP_Error('no_signature', __('Missing signature', 'ajinsafro-core'), ['status' => 401]);
        }

        $expected_signature = hash_hmac('sha256', $body, $hmac_secret);

        if (!hash_equals($expected_signature, $signature)) {
            return new \WP_Error('invalid_signature', __('Invalid signature', 'ajinsafro-core'), ['status' => 401]);
        }

        return true;
    }

    public function handle_sync(\WP_REST_Request $request)
    {
        $data = $request->get_json_params();

        $action = $data['action'] ?? '';
        $entity_type = $data['entity_type'] ?? '';

        if ($entity_type !== 'tour') {
            return new \WP_Error('unsupported_entity', __('Only tours are supported in V1', 'ajinsafro-core'), ['status' => 400]);
        }

        $syncer = new TourSyncer();

        try {
            if ($action === 'upsert') {
                $result = $syncer->upsert($data);
                $this->log_sync('success', $data, $result);
                return rest_ensure_response(['success' => true, 'data' => $result]);
            } elseif ($action === 'delete') {
                $result = $syncer->delete($data);
                $this->log_sync('success', $data, $result);
                return rest_ensure_response(['success' => true, 'data' => $result]);
            } else {
                return new \WP_Error('invalid_action', __('Invalid action', 'ajinsafro-core'), ['status' => 400]);
            }
        } catch (\Exception $e) {
            $this->log_sync('error', $data, ['error' => $e->getMessage()]);
            return new \WP_Error('sync_failed', $e->getMessage(), ['status' => 500]);
        }
    }

    private function log_sync($status, $data, $result)
    {
        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/ajinsafro-sync.log';

        $log_entry = sprintf(
            "[%s] %s - Action: %s, Entity: %s, Laravel ID: %s, Result: %s\n",
            current_time('Y-m-d H:i:s'),
            strtoupper($status),
            $data['action'] ?? 'unknown',
            $data['entity_type'] ?? 'unknown',
            $data['laravel_id'] ?? 'unknown',
            json_encode($result)
        );

        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
}
