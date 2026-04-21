<?php
/**
 * Single Tour Page bootstrap (clean V1 architecture)
 *
 * @package AjinsafroTourBridge
 */

if (!defined('ABSPATH')) {
    exit;
}

class AJTB_Single_Tour_Page
{
    private const RECAP_ENDPOINT = 'ajtb-recap';
    private const RECAP_QUERY_VAR = 'ajtb_recap';
    private const CLIENT_PASSWORD_VALID_HOURS = 72;

    /**
     * Initialize V1 single tour flow.
     */
    public static function boot(): void
    {
        add_filter('template_include', [self::class, 'override_template'], 999);
        add_action('init', [self::class, 'register_recap_endpoint']);
        add_filter('query_vars', [self::class, 'register_query_vars']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets'], 20);
        add_action('wp_ajax_ajtb_v1_toggle_activity', [self::class, 'ajax_toggle_activity']);
        add_action('wp_ajax_nopriv_ajtb_v1_toggle_activity', [self::class, 'ajax_toggle_activity']);
        add_action('wp_ajax_ajtb_v1_get_rooms_extras', [self::class, 'ajax_get_rooms_extras']);
        add_action('wp_ajax_nopriv_ajtb_v1_get_rooms_extras', [self::class, 'ajax_get_rooms_extras']);
        add_action('wp_ajax_ajtb_v1_create_reservation', [self::class, 'ajax_create_reservation']);
        add_action('wp_ajax_nopriv_ajtb_v1_create_reservation', [self::class, 'ajax_create_reservation']);
    }

    public static function register_recap_endpoint(): void
    {
        add_rewrite_endpoint(self::RECAP_ENDPOINT, EP_PERMALINK);
    }

    public static function register_query_vars(array $vars): array
    {
        $vars[] = self::RECAP_ENDPOINT;
        $vars[] = self::RECAP_QUERY_VAR;
        return $vars;
    }

    public static function is_recap_request(): bool
    {
        if (isset($_GET[self::RECAP_QUERY_VAR]) && (string) $_GET[self::RECAP_QUERY_VAR] !== '') {
            return true;
        }
        $qv = get_query_var(self::RECAP_QUERY_VAR, '');
        if ($qv !== '' && $qv !== null) {
            return true;
        }
        global $wp_query;
        if (!isset($wp_query) || !is_object($wp_query)) {
            return false;
        }
        return is_array($wp_query->query_vars) && array_key_exists(self::RECAP_ENDPOINT, $wp_query->query_vars);
    }

    public static function recap_url(int $tour_id): string
    {
        $permalink = get_permalink($tour_id);
        if (!$permalink) {
            return '';
        }
        // Prefer query arg so it works even without flush_rewrite_rules / permalinks.
        return add_query_arg(self::RECAP_QUERY_VAR, '1', $permalink);
    }

    private static function table_exists(string $table): bool
    {
        global $wpdb;
        if (!isset($wpdb) || !is_object($wpdb)) {
            return false;
        }
        $table = trim($table);
        if ($table === '') {
            return false;
        }
        $found = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        return (string) $found === $table;
    }

    /**
     * Find first existing table name from candidates.
     *
     * @param string[] $candidates
     */
    private static function first_table(array $candidates): string
    {
        $candidates = array_values(array_unique(array_filter(array_map('strval', $candidates))));
        foreach ($candidates as $t) {
            if ($t !== '' && self::table_exists($t)) {
                return $t;
            }
        }
        return '';
    }

    private static function normalize_phone(string $phone): string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return '';
        }
        // Keep digits only; enough for deduping in DB.
        $digits = preg_replace('/\D+/', '', $phone);
        return is_string($digits) ? $digits : '';
    }

    private static function generate_temp_password(int $len = 10): string
    {
        $len = max(8, min(32, $len));
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#';
        $out = '';
        $max = strlen($alphabet) - 1;
        for ($i = 0; $i < $len; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }
        return $out;
    }

    /**
     * Ensure (or reuse) a Laravel client + portal user account.
     *
     * Returns:
     * - client_id (int)
     * - user_id (int|null)
     * - login (string)   // we use email as login in Laravel auth
     * - password (string|null) // only when newly created
     * - created (bool)   // account created now
     */
    private static function ensure_client_portal_account(array $payload): array
    {
        global $wpdb;

        $first = isset($payload['first_name']) ? sanitize_text_field((string) $payload['first_name']) : '';
        $last = isset($payload['last_name']) ? sanitize_text_field((string) $payload['last_name']) : '';
        $email = isset($payload['email']) ? sanitize_email((string) $payload['email']) : '';
        $phone_raw = isset($payload['phone']) ? sanitize_text_field((string) $payload['phone']) : '';
        $phone_norm = self::normalize_phone($phone_raw);

        $clients_table = 'clients';
        $users_table = 'users';

        $client = null;
        if ($email !== '') {
            $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$clients_table} WHERE email = %s ORDER BY id DESC LIMIT 1", $email), ARRAY_A);
        }
        if (!$client && $phone_norm !== '') {
            // Best-effort: match by phone OR whatsapp_number when present.
            $client = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$clients_table} WHERE REPLACE(REPLACE(REPLACE(REPLACE(phone,'+',''),' ',''),'-',''),'(', '') LIKE %s OR REPLACE(REPLACE(REPLACE(REPLACE(whatsapp_number,'+',''),' ',''),'-',''),'(', '') LIKE %s ORDER BY id DESC LIMIT 1",
                '%' . $phone_norm . '%',
                '%' . $phone_norm . '%'
            ), ARRAY_A);
        }

        $now_gmt = current_time('mysql', true);
        $client_id = 0;
        if ($client && isset($client['id'])) {
            $client_id = (int) $client['id'];
            // Soft update missing identity fields (avoid overwriting manually curated CRM data).
            $updates = [];
            if (($client['first_name'] ?? '') === '' && $first !== '') $updates['first_name'] = $first;
            if (($client['last_name'] ?? '') === '' && $last !== '') $updates['last_name'] = $last;
            if (($client['full_name'] ?? '') === '' && ($first !== '' || $last !== '')) $updates['full_name'] = trim($first . ' ' . $last);
            if (($client['email'] ?? '') === '' && $email !== '') $updates['email'] = $email;
            if (($client['phone'] ?? '') === '' && $phone_raw !== '') $updates['phone'] = $phone_raw;
            if (!empty($updates)) {
                $updates['updated_at'] = $now_gmt;
                $wpdb->update($clients_table, $updates, ['id' => $client_id]);
            }
        } else {
            // Generate CL-YYYY-XXXX compatible with Laravel Client::generateClientCode().
            $year = gmdate('Y');
            $last_code = (string) $wpdb->get_var($wpdb->prepare(
                "SELECT client_code FROM {$clients_table} WHERE client_code LIKE %s ORDER BY id DESC LIMIT 1",
                'CL-' . $year . '-%'
            ));
            $num = 1;
            if ($last_code !== '') {
                $tail = substr($last_code, -4);
                $n = is_string($tail) ? (int) $tail : 0;
                if ($n > 0) $num = $n + 1;
            }
            $client_code = 'CL-' . $year . '-' . str_pad((string) $num, 4, '0', STR_PAD_LEFT);

            if ($first === '') $first = 'Client';
            if ($last === '') $last = 'Ajinsafro';
            $full = trim($first . ' ' . $last);

            $wpdb->insert($clients_table, [
                'uuid' => wp_generate_uuid4(),
                'client_code' => $client_code,
                'client_type' => 'individual',
                'status' => 'active',
                'source' => 'wp_front_v1',
                'first_name' => $first,
                'last_name' => $last,
                'full_name' => $full,
                'email' => $email ?: null,
                'phone' => $phone_raw ?: null,
                'created_at' => $now_gmt,
                'updated_at' => $now_gmt,
            ]);
            $client_id = (int) $wpdb->insert_id;
        }

        // Ensure user account (email required in Laravel users).
        $created = false;
        $password_plain = null;
        $user_id = null;
        $login_email = $email;

        if ($login_email === '') {
            // Deterministic fallback if customer didn't provide email.
            $suffix = $phone_norm !== '' ? $phone_norm : (string) $client_id;
            $login_email = 'client+' . $suffix . '@ajinsafro.local';
        }

        $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$users_table} WHERE email = %s ORDER BY id DESC LIMIT 1", $login_email), ARRAY_A);
        if ($user && isset($user['id'])) {
            $user_id = (int) $user['id'];
        } else {
            $password_plain = self::generate_temp_password(12);
            $hash = password_hash($password_plain, PASSWORD_BCRYPT);
            $name = trim($first . ' ' . $last);
            if ($name === '') $name = 'Client Ajinsafro';
            $wpdb->insert($users_table, [
                'name' => $name,
                'email' => $login_email,
                'password' => $hash,
                'user_type' => 'client',
                'is_admin' => 0,
                'is_active' => 1,
                'access_mode' => 'role',
                'base_role' => 'client',
                'created_at' => $now_gmt,
                'updated_at' => $now_gmt,
            ]);
            $user_id = (int) $wpdb->insert_id;
            $created = $user_id > 0;
        }

        // Safety: ensure no back-office role is attached to client portal users.
        if ($user_id > 0) {
            try {
                $wpdb->delete('model_has_roles', [
                    'model_id' => $user_id,
                    'model_type' => 'App\\Models\\User',
                ]);
            } catch (Throwable $e) {
                // ignore
            }
        }

        // Link client -> user and store temp password if created (admin-only visibility).
        if ($client_id > 0) {
            $client_updates = [];
            if ($user_id) {
                $client_updates['user_id'] = $user_id;
                $client_updates['portal_username'] = $login_email;
            }
            if ($created && $password_plain) {
                $client_updates['portal_temp_password'] = $password_plain;
                $client_updates['portal_temp_password_created_at'] = $now_gmt;
            }
            if (!empty($client_updates)) {
                $client_updates['updated_at'] = $now_gmt;
                $wpdb->update($clients_table, $client_updates, ['id' => $client_id]);
            }
        }

        return [
            'client_id' => $client_id,
            'user_id' => $user_id,
            'login' => $login_email,
            'password' => $password_plain,
            'created' => (bool) $created,
        ];
    }

    /**
     * Route single st_tours requests to the clean V1 template.
     */
    public static function override_template(string $template): string
    {
        if (!self::is_target_request()) {
            return $template;
        }

        if (self::is_recap_request()) {
            $recap_template = AJTB_PLUGIN_DIR . 'templates/v1/recap-st_tours.php';
            if (file_exists($recap_template)) {
                return $recap_template;
            }
        }

        $v1_template = AJTB_PLUGIN_DIR . 'templates/v1/single-st_tours.php';
        if (file_exists($v1_template)) {
            return $v1_template;
        }

        // Hard fallback if v1 template is missing.
        $legacy_wrapper = AJTB_PLUGIN_DIR . 'templates/single-st_tours.php';
        if (file_exists($legacy_wrapper)) {
            return $legacy_wrapper;
        }

        return $template;
    }

    /**
     * Load only V1 assets on single st_tours.
     */
    public static function enqueue_assets(): void
    {
        if (!self::is_target_request()) {
            return;
        }

        $css_deps = [];

        // Reuse official Ajinsafro public header/footer assets when available.
        if (defined('AJTH_URL') && defined('AJTH_DIR') && file_exists(AJTH_DIR . 'assets/css/home.css')) {
            wp_enqueue_style(
                'ajth-fontawesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
                [],
                '6.4.0'
            );

            wp_enqueue_style(
                'ajth-google-fonts',
                'https://fonts.googleapis.com/css2?family=Cairo:wght@700;900&family=Noto+Sans+Arabic:wght@400;600;700&family=Poppins:wght@300;400;500;600;700;800;900&display=swap',
                [],
                null
            );

            wp_enqueue_style(
                'ajth-home-css',
                AJTH_URL . 'assets/css/home.css',
                ['ajth-fontawesome'],
                defined('AJTH_VERSION') ? AJTH_VERSION : AJTB_VERSION
            );
            $css_deps[] = 'ajth-home-css';

            if (file_exists(AJTH_DIR . 'assets/js/home.js')) {
                wp_enqueue_script(
                    'ajth-home-js',
                    AJTH_URL . 'assets/js/home.js',
                    [],
                    defined('AJTH_VERSION') ? AJTH_VERSION : AJTB_VERSION,
                    true
                );
            }
        }

        $tour_css_version = file_exists(AJTB_PLUGIN_DIR . 'assets/css/tour.css')
            ? (string) filemtime(AJTB_PLUGIN_DIR . 'assets/css/tour.css')
            : AJTB_VERSION;
        $tour_js_version = file_exists(AJTB_PLUGIN_DIR . 'assets/js/tour.js')
            ? (string) filemtime(AJTB_PLUGIN_DIR . 'assets/js/tour.js')
            : AJTB_VERSION;

        wp_enqueue_style(
            'ajtb-tour-css',
            AJTB_PLUGIN_URL . 'assets/css/tour.css',
            $css_deps,
            $tour_css_version
        );

        // Bootstrap modal is used on recap for credentials display.
        if (!wp_script_is('ajtb-bootstrap-bundle', 'enqueued')) {
            wp_enqueue_script(
                'ajtb-bootstrap-bundle',
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
                [],
                '5.3.3',
                true
            );
        }

        wp_enqueue_script(
            'ajtb-tour-js',
            AJTB_PLUGIN_URL . 'assets/js/tour.js',
            ['ajtb-bootstrap-bundle'],
            $tour_js_version,
            true
        );

        $post_id = (int) get_queried_object_id();
        wp_localize_script('ajtb-tour-js', 'ajtbData', [
            'postId' => $post_id,
            'tourId' => $post_id,
            'tourTitle' => $post_id > 0 ? get_the_title($post_id) : '',
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'activityNonce' => wp_create_nonce('ajtb_v1_activity_toggle'),
            'reservationNonce' => wp_create_nonce('ajtb_v1_create_reservation'),
            'activityMessages' => [
                'added' => __('Activité ajoutée à votre programme.', 'ajinsafro-tour-bridge'),
                'error' => __('Impossible d’ajouter l’activité pour le moment.', 'ajinsafro-tour-bridge'),
            ],
        ]);
    }

    /**
     * Toggle optional activity for the current user/session in V1.
     */
    public static function ajax_toggle_activity(): void
    {
        if (!class_exists('AJTB_Activity_Selections')) {
            wp_send_json_error([
                'message' => __('Service activité indisponible.', 'ajinsafro-tour-bridge'),
            ], 500);
        }

        $nonce_ok = check_ajax_referer('ajtb_v1_activity_toggle', 'nonce', false);
        if (!$nonce_ok) {
            wp_send_json_error([
                'message' => __('Requête non autorisée.', 'ajinsafro-tour-bridge'),
            ], 403);
        }

        $tour_id = isset($_POST['tour_id']) ? (int) $_POST['tour_id'] : 0;
        $day_id = isset($_POST['day_id']) ? (int) $_POST['day_id'] : 0;
        $activity_id = isset($_POST['activity_id']) ? (int) $_POST['activity_id'] : 0;
        $action = isset($_POST['activity_action']) ? sanitize_text_field((string) $_POST['activity_action']) : 'added';
        $action = $action === 'removed' ? 'removed' : 'added';

        if ($tour_id <= 0 || $day_id <= 0 || $activity_id <= 0) {
            wp_send_json_error([
                'message' => __('Paramètres incomplets pour l’activité.', 'ajinsafro-tour-bridge'),
            ], 422);
        }

        $selections = new AJTB_Activity_Selections();
        $session_token = $selections->get_session_token();
        $user_id = is_user_logged_in() ? (int) get_current_user_id() : null;

        $result = $selections->toggle($tour_id, $day_id, $activity_id, $action, $session_token, $user_id);
        if (empty($result['success'])) {
            wp_send_json_error([
                'message' => !empty($result['message']) ? (string) $result['message'] : __('Action refusée.', 'ajinsafro-tour-bridge'),
            ], 400);
        }

        wp_send_json_success([
            'message' => !empty($result['message']) ? (string) $result['message'] : __('Activité mise à jour.', 'ajinsafro-tour-bridge'),
            'day_activities' => isset($result['day_activities']) && is_array($result['day_activities']) ? $result['day_activities'] : [],
        ]);
    }

    public static function ajax_create_reservation(): void
    {
        $nonce_ok = check_ajax_referer('ajtb_v1_create_reservation', 'nonce', false);
        if (!$nonce_ok) {
            wp_send_json_error([
                'message' => __('Requête non autorisée.', 'ajinsafro-tour-bridge'),
            ], 403);
        }

        global $wpdb;

        $tour_id = isset($_POST['tour_id']) ? (int) $_POST['tour_id'] : 0;
        $departure_place_id = isset($_POST['departure_place_id']) ? (int) $_POST['departure_place_id'] : 0;
        $departure_date = isset($_POST['departure_date']) ? sanitize_text_field((string) $_POST['departure_date']) : '';
        $adults = isset($_POST['adults']) ? max(1, (int) $_POST['adults']) : 1;
        $children = isset($_POST['children']) ? max(0, (int) $_POST['children']) : 0;

        $client_mode = isset($_POST['client_mode']) ? sanitize_text_field((string) $_POST['client_mode']) : 'new';
        if ($client_mode !== 'existing') {
            $client_mode = 'new';
        }
        $client_external_id = isset($_POST['client_external_id']) ? (int) $_POST['client_external_id'] : null;
        $client_first_name = isset($_POST['client_first_name']) ? sanitize_text_field((string) $_POST['client_first_name']) : '';
        $client_last_name = isset($_POST['client_last_name']) ? sanitize_text_field((string) $_POST['client_last_name']) : '';
        $client_phone = isset($_POST['client_phone']) ? sanitize_text_field((string) $_POST['client_phone']) : '';
        $client_email = isset($_POST['client_email']) ? sanitize_email((string) $_POST['client_email']) : '';
        $client_document_type = isset($_POST['client_document_type']) ? sanitize_text_field((string) $_POST['client_document_type']) : '';
        $client_document_number = isset($_POST['client_document_number']) ? sanitize_text_field((string) $_POST['client_document_number']) : '';

        $passengers_json = isset($_POST['passengers']) ? (string) wp_unslash($_POST['passengers']) : '[]';
        $extras_json = isset($_POST['extras_json']) ? (string) wp_unslash($_POST['extras_json']) : '[]';
        $room_id = isset($_POST['room_id']) ? (int) $_POST['room_id'] : 0;
        $room_allocation_json = isset($_POST['room_allocation_json']) ? (string) wp_unslash($_POST['room_allocation_json']) : '';

        if ($tour_id <= 0 || $departure_date === '') {
            wp_send_json_error([
                'message' => __('Paramètres incomplets.', 'ajinsafro-tour-bridge'),
            ], 422);
        }

        if ($client_mode === 'new' && ($client_first_name === '' || $client_last_name === '')) {
            wp_send_json_error([
                'message' => __('Veuillez saisir le prénom et le nom du client.', 'ajinsafro-tour-bridge'),
            ], 422);
        }
        if ($client_mode === 'existing' && empty($client_external_id)) {
            wp_send_json_error([
                'message' => __('Veuillez sélectionner un client existant.', 'ajinsafro-tour-bridge'),
            ], 422);
        }

        // Always resolve/create the client account from identity data to avoid duplicates.
        // Reservation will be linked to clients.id (client_external_id).
        $account = null;
        try {
            $account = self::ensure_client_portal_account([
                'first_name' => $client_first_name,
                'last_name' => $client_last_name,
                'email' => $client_email,
                'phone' => $client_phone,
            ]);
        } catch (Throwable $e) {
            // Do not block reservation creation; log and keep legacy snapshot fields.
            error_log('[AJTB] ensure_client_portal_account failed: ' . $e->getMessage());
            $account = null;
        }

        if (is_array($account) && !empty($account['client_id'])) {
            $client_external_id = (int) $account['client_id'];
            $client_mode = 'existing';
        }

        $passengers = json_decode($passengers_json, true);
        if (!is_array($passengers)) {
            $passengers = [];
        }
        $extras = json_decode($extras_json, true);
        if (!is_array($extras)) {
            $extras = [];
        }

        $table_travel_dates = self::first_table([
            ajtb_table('aj_travel_dates'),
            'aj_travel_dates',
            $wpdb->prefix . 'aj_travel_dates',
        ]);
        if ($table_travel_dates === '') {
            wp_send_json_error([
                'message' => __('Table des dates introuvable.', 'ajinsafro-tour-bridge'),
            ], 500);
        }
        $travel_date_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_travel_dates} WHERE travel_id = %d AND date = %s ORDER BY id DESC LIMIT 1",
            $tour_id,
            $departure_date
        ));
        if ($travel_date_id <= 0) {
            wp_send_json_error([
                'message' => __('Date de départ introuvable.', 'ajinsafro-tour-bridge'),
            ], 404);
        }

        $voyages_table = self::first_table([
            'voyages',
            'aj_voyages',
            $wpdb->prefix . 'voyages',
            $wpdb->prefix . 'aj_voyages',
        ]);
        if ($voyages_table === '') {
            wp_send_json_error([
                'message' => __('Table voyages introuvable.', 'ajinsafro-tour-bridge'),
            ], 500);
        }
        $voyage_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$voyages_table} WHERE wp_post_id = %d ORDER BY id DESC LIMIT 1",
            $tour_id
        ));
        if ($voyage_id <= 0) {
            wp_send_json_error([
                'message' => __('Voyage introuvable côté Laravel.', 'ajinsafro-tour-bridge'),
            ], 404);
        }

        $departures_table = self::first_table([
            'departures',
            $wpdb->prefix . 'departures',
        ]);
        if ($departures_table === '') {
            wp_send_json_error([
                'message' => __('Table départs introuvable.', 'ajinsafro-tour-bridge'),
            ], 500);
        }
        $departure_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$departures_table} WHERE voyage_id = %d AND wp_travel_date_id = %d ORDER BY id DESC LIMIT 1",
            $voyage_id,
            $travel_date_id
        ));
        if ($departure_id <= 0) {
            wp_send_json_error([
                'message' => __('Départ introuvable côté Laravel.', 'ajinsafro-tour-bridge'),
            ], 404);
        }

        $reservations_table = 'reservations';
        $passengers_table = 'reservation_passengers';
        $extras_table = 'reservation_extras';

        $passengers_count = 1;
        foreach ($passengers as $p) {
            if (!is_array($p)) {
                continue;
            }
            $fn = trim((string) ($p['first_name'] ?? ''));
            $ln = trim((string) ($p['last_name'] ?? ''));
            if ($fn !== '' || $ln !== '') {
                $passengers_count++;
            }
        }

        $inserted = $wpdb->insert($reservations_table, [
            'tour_id' => $voyage_id,
            'voyage_id' => $voyage_id,
            'departure_id' => $departure_id,
            'travel_date_id' => $travel_date_id,
            'client_mode' => $client_mode,
            'client_external_id' => $client_external_id ?: null,
            'client_first_name' => $client_first_name ?: null,
            'client_last_name' => $client_last_name ?: null,
            'client_email' => $client_email ?: null,
            'client_phone' => $client_phone ?: null,
            'client_document_type' => $client_document_type ?: null,
            'client_document_number' => $client_document_number ?: null,
            'status' => 'pending',
            'passengers_count' => $passengers_count,
            'wp_tour_post_id' => $tour_id,
            'catalog_source_code' => 'wp_front_v1',
            'notes' => 'Front booking (WP) - departure_place_id=' . $departure_place_id . ' adults=' . $adults . ' children=' . $children . ($room_id > 0 ? (' room_id=' . $room_id) : '') . ($room_allocation_json !== '' ? (' room_alloc=' . $room_allocation_json) : ''),
            'created_at' => current_time('mysql', true),
            'updated_at' => current_time('mysql', true),
        ]);

        if (!$inserted) {
            wp_send_json_error([
                'message' => __('Impossible de créer la réservation.', 'ajinsafro-tour-bridge'),
                'debug' => $wpdb->last_error,
            ], 500);
        }

        $reservation_id = (int) $wpdb->insert_id;

        foreach ($passengers as $p) {
            if (!is_array($p)) {
                continue;
            }
            $first = sanitize_text_field((string) ($p['first_name'] ?? ''));
            $last = sanitize_text_field((string) ($p['last_name'] ?? ''));
            $type = sanitize_text_field((string) ($p['type'] ?? 'adult'));
            if ($first === '' && $last === '') {
                continue;
            }
            if (!in_array($type, ['adult', 'child', 'infant'], true)) {
                $type = 'adult';
            }
            $wpdb->insert($passengers_table, [
                'reservation_id' => $reservation_id,
                'first_name' => $first ?: null,
                'last_name' => $last ?: null,
                'type' => $type,
                'birth_date' => !empty($p['birth_date']) ? sanitize_text_field((string) $p['birth_date']) : null,
                'document_type' => !empty($p['document_type']) ? sanitize_text_field((string) $p['document_type']) : null,
                'document_number' => !empty($p['document_number']) ? sanitize_text_field((string) $p['document_number']) : null,
                'created_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true),
            ]);
        }

        foreach ($extras as $ex) {
            if (!is_array($ex)) {
                continue;
            }
            $name = trim((string) ($ex['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $wpdb->insert($extras_table, [
                'reservation_id' => $reservation_id,
                'name' => sanitize_text_field($name),
                'price' => isset($ex['price']) ? (float) $ex['price'] : 0,
                'passenger_key' => !empty($ex['passenger_key']) ? sanitize_text_field((string) $ex['passenger_key']) : null,
                'created_at' => current_time('mysql', true),
                'updated_at' => current_time('mysql', true),
            ]);
        }

        wp_send_json_success([
            'reservation_id' => $reservation_id,
            'status' => 'pending',
            'account_created' => (bool) ($account['created'] ?? false),
            'login' => (string) ($account['login'] ?? ''),
            'password' => (string) ($account['password'] ?? ''),
        ]);
    }

    /**
     * Fetch rooms availability (departure_hotel_rooms) and voyage extras (voyage_extras)
     * for the selected tour + date.
     */
    public static function ajax_get_rooms_extras(): void
    {
        $nonce_ok = check_ajax_referer('ajtb_v1_create_reservation', 'nonce', false);
        if (!$nonce_ok) {
            wp_send_json_error([
                'message' => __('Requête non autorisée.', 'ajinsafro-tour-bridge'),
            ], 403);
        }

        global $wpdb;

        $tour_id = isset($_POST['tour_id']) ? (int) $_POST['tour_id'] : 0;
        $departure_date = isset($_POST['departure_date']) ? sanitize_text_field((string) $_POST['departure_date']) : '';
        if ($tour_id <= 0 || $departure_date === '') {
            wp_send_json_error([
                'message' => __('Paramètres incomplets.', 'ajinsafro-tour-bridge'),
            ], 422);
        }

        $table_travel_dates = self::first_table([
            ajtb_table('aj_travel_dates'),
            'aj_travel_dates',
            $wpdb->prefix . 'aj_travel_dates',
        ]);
        if ($table_travel_dates === '') {
            wp_send_json_error([
                'message' => __('Table des dates introuvable.', 'ajinsafro-tour-bridge'),
            ], 500);
        }
        $travel_date_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_travel_dates} WHERE travel_id = %d AND date = %s ORDER BY id DESC LIMIT 1",
            $tour_id,
            $departure_date
        ));
        if ($travel_date_id <= 0) {
            wp_send_json_error([
                'message' => __('Date de départ introuvable.', 'ajinsafro-tour-bridge'),
            ], 404);
        }

        $voyages_table = self::first_table([
            'voyages',
            'aj_voyages',
            $wpdb->prefix . 'voyages',
            $wpdb->prefix . 'aj_voyages',
        ]);
        if ($voyages_table === '') {
            wp_send_json_error([
                'message' => __('Table voyages introuvable.', 'ajinsafro-tour-bridge'),
            ], 500);
        }
        $voyage_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$voyages_table} WHERE wp_post_id = %d ORDER BY id DESC LIMIT 1",
            $tour_id
        ));
        if ($voyage_id <= 0) {
            wp_send_json_error([
                'message' => __('Voyage introuvable côté Laravel.', 'ajinsafro-tour-bridge'),
            ], 404);
        }

        $departures_table = self::first_table([
            'departures',
            $wpdb->prefix . 'departures',
        ]);
        if ($departures_table === '') {
            wp_send_json_error([
                'message' => __('Table départs introuvable.', 'ajinsafro-tour-bridge'),
            ], 500);
        }
        $departure_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$departures_table} WHERE voyage_id = %d AND wp_travel_date_id = %d ORDER BY id DESC LIMIT 1",
            $voyage_id,
            $travel_date_id
        ));
        if ($departure_id <= 0) {
            wp_send_json_error([
                'message' => __('Départ introuvable côté Laravel.', 'ajinsafro-tour-bridge'),
            ], 404);
        }

        // Rooms allocations (CRUD "Repartition des chambres par depart")
        $rooms = [];
        $alloc_table = self::first_table([
            'departure_room_allocations',
            $wpdb->prefix . 'departure_room_allocations',
        ]);
        if ($alloc_table !== '') {
            $room_rows = $wpdb->get_results($wpdb->prepare(
                "SELECT id, hotel_id, room_type, quantity, capacity_per_room, supplement
                 FROM {$alloc_table}
                 WHERE departure_id = %d
                 ORDER BY sort_order ASC, id ASC",
                $departure_id
            ), ARRAY_A);
        } else {
            $room_rows = [];
        }
        foreach ($room_rows ?: [] as $row) {
            $qty = isset($row['quantity']) ? (int) $row['quantity'] : 0;
            $cap = isset($row['capacity_per_room']) ? (int) $row['capacity_per_room'] : 1;
            $rooms[] = [
                'id' => (int) ($row['id'] ?? 0),
                'hotel_id' => isset($row['hotel_id']) && $row['hotel_id'] !== null ? (int) $row['hotel_id'] : null,
                'room_type' => isset($row['room_type']) ? (string) $row['room_type'] : '',
                'quantity' => $qty,
                'capacity_per_room' => $cap,
                'available_rooms' => $qty,
                'available_places' => max(0, $qty) * max(1, $cap),
                'supplement' => isset($row['supplement']) ? max(0, (float) $row['supplement']) : 0.0,
            ];
        }

        // Extras (voyage-level)
        $extras = [];
        $extras_table = self::first_table([
            'voyage_extras',
            'aj_voyage_extras',
            $wpdb->prefix . 'voyage_extras',
            $wpdb->prefix . 'aj_voyage_extras',
        ]);
        $extra_rows = [];
        if ($extras_table !== '') {
            $extra_rows = $wpdb->get_results($wpdb->prepare(
                "SELECT id, name, description, price_adult, price_child, extra_type, icon
                 FROM {$extras_table}
                 WHERE voyage_id = %d AND is_active = 1
                 ORDER BY sort_order ASC, id ASC",
                $voyage_id
            ), ARRAY_A);
        }
        foreach ($extra_rows ?: [] as $row) {
            $extras[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => isset($row['name']) ? (string) $row['name'] : '',
                'description' => isset($row['description']) ? (string) $row['description'] : '',
                'price_adult' => isset($row['price_adult']) ? (float) $row['price_adult'] : 0.0,
                'price_child' => isset($row['price_child']) ? (float) $row['price_child'] : 0.0,
                'extra_type' => isset($row['extra_type']) ? (string) $row['extra_type'] : '',
                'icon' => isset($row['icon']) ? (string) $row['icon'] : '',
            ];
        }

        wp_send_json_success([
            'voyage_id' => $voyage_id,
            'departure_id' => $departure_id,
            'travel_date_id' => $travel_date_id,
            'rooms' => $rooms,
            'extras' => $extras,
        ]);
    }

    /**
     * True when the request is for single st_tours.
     */
    private static function is_target_request(): bool
    {
        return is_singular(AJTB_POST_TYPE);
    }
}
