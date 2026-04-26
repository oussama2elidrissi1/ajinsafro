<?php
/**
 * Ajinsafro Agent
 *
 * Floating public assistant backed by local Ajinsafro knowledge and live catalog data.
 *
 * @package AjinsafroTravelerHome
 */

if (! defined('ABSPATH')) {
    exit;
}

class AJTH_Ajinsafro_Agent
{
    public const OPTION_KEY = 'ajth_agent_settings';
    public const NONCE_ACTION = 'ajth_agent_chat';

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets'], 20);
        add_action('wp_footer', [$this, 'render_widget'], 5);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_init', [$this, 'handle_admin_save']);
    }

    public static function get_settings(): array
    {
        $defaults = [
            'enabled' => true,
            'title' => 'Ajinsafro Agent',
            'subtitle' => 'Votre assistant voyage Ajinsafro',
            'welcome_message' => "Bonjour 👋 Je suis Ajinsafro Agent. Je peux vous aider à trouver un voyage, vérifier les départs disponibles, consulter les prix ou vous guider pour réserver.",
            'contact_email' => 'contact@ajinsafro.ma',
            'contact_phone' => '+212 539 323 874',
            'whatsapp_url' => '',
            'show_on_public_pages' => true,
        ];

        $saved = get_option(self::OPTION_KEY, []);
        if (! is_array($saved)) {
            $saved = [];
        }

        $settings = array_replace($defaults, $saved);
        $settings['enabled'] = ! empty($settings['enabled']);
        $settings['show_on_public_pages'] = ! empty($settings['show_on_public_pages']);
        $settings['title'] = sanitize_text_field((string) $settings['title']);
        $settings['subtitle'] = sanitize_text_field((string) $settings['subtitle']);
        $settings['welcome_message'] = wp_kses_post((string) $settings['welcome_message']);
        $settings['contact_email'] = sanitize_email((string) $settings['contact_email']);
        $settings['contact_phone'] = sanitize_text_field((string) $settings['contact_phone']);
        $settings['whatsapp_url'] = esc_url_raw((string) $settings['whatsapp_url']);

        return $settings;
    }

    public function register_admin_page(): void
    {
        add_submenu_page(
            'ajth-home-settings',
            __('Ajinsafro Agent', 'ajinsafro-traveler-home'),
            __('Ajinsafro Agent', 'ajinsafro-traveler-home'),
            'manage_options',
            'ajth-agent-settings',
            [$this, 'render_admin_page']
        );
    }

    public function handle_admin_save(): void
    {
        if (empty($_POST['ajth_agent_settings_form'])) {
            return;
        }

        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Permissions insuffisantes.', 'ajinsafro-traveler-home'));
        }

        check_admin_referer('ajth_save_agent_settings', 'ajth_agent_nonce');

        $raw = isset($_POST['ajth_agent']) && is_array($_POST['ajth_agent']) ? wp_unslash($_POST['ajth_agent']) : [];
        $settings = [
            'enabled' => ! empty($raw['enabled']),
            'show_on_public_pages' => ! empty($raw['show_on_public_pages']),
            'title' => sanitize_text_field((string) ($raw['title'] ?? '')),
            'subtitle' => sanitize_text_field((string) ($raw['subtitle'] ?? '')),
            'welcome_message' => sanitize_textarea_field((string) ($raw['welcome_message'] ?? '')),
            'contact_email' => sanitize_email((string) ($raw['contact_email'] ?? '')),
            'contact_phone' => sanitize_text_field((string) ($raw['contact_phone'] ?? '')),
            'whatsapp_url' => esc_url_raw((string) ($raw['whatsapp_url'] ?? '')),
        ];

        update_option(self::OPTION_KEY, $settings, false);

        wp_safe_redirect(admin_url('admin.php?page=ajth-agent-settings&saved=1'));
        exit;
    }

    public function render_admin_page(): void
    {
        $settings = self::get_settings();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Ajinsafro Agent', 'ajinsafro-traveler-home'); ?></h1>
            <?php if (isset($_GET['saved'])) { ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Paramètres enregistrés.', 'ajinsafro-traveler-home'); ?></p></div>
            <?php } ?>
            <form method="post" action="">
                <?php wp_nonce_field('ajth_save_agent_settings', 'ajth_agent_nonce'); ?>
                <input type="hidden" name="ajth_agent_settings_form" value="1">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Activation', 'ajinsafro-traveler-home'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ajth_agent[enabled]" value="1" <?php checked($settings['enabled']); ?>>
                                <?php echo esc_html__('Activer Ajinsafro Agent', 'ajinsafro-traveler-home'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" name="ajth_agent[show_on_public_pages]" value="1" <?php checked($settings['show_on_public_pages']); ?>>
                                <?php echo esc_html__('Afficher sur toutes les pages publiques', 'ajinsafro-traveler-home'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ajth-agent-title"><?php echo esc_html__('Titre', 'ajinsafro-traveler-home'); ?></label></th>
                        <td><input id="ajth-agent-title" type="text" class="regular-text" name="ajth_agent[title]" value="<?php echo esc_attr($settings['title']); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ajth-agent-subtitle"><?php echo esc_html__('Sous-titre', 'ajinsafro-traveler-home'); ?></label></th>
                        <td><input id="ajth-agent-subtitle" type="text" class="regular-text" name="ajth_agent[subtitle]" value="<?php echo esc_attr($settings['subtitle']); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ajth-agent-welcome"><?php echo esc_html__('Message d’accueil', 'ajinsafro-traveler-home'); ?></label></th>
                        <td><textarea id="ajth-agent-welcome" class="large-text" rows="4" name="ajth_agent[welcome_message]"><?php echo esc_textarea(wp_strip_all_tags($settings['welcome_message'])); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ajth-agent-email"><?php echo esc_html__('Email de contact', 'ajinsafro-traveler-home'); ?></label></th>
                        <td><input id="ajth-agent-email" type="email" class="regular-text" name="ajth_agent[contact_email]" value="<?php echo esc_attr($settings['contact_email']); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ajth-agent-phone"><?php echo esc_html__('Téléphone', 'ajinsafro-traveler-home'); ?></label></th>
                        <td><input id="ajth-agent-phone" type="text" class="regular-text" name="ajth_agent[contact_phone]" value="<?php echo esc_attr($settings['contact_phone']); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ajth-agent-whatsapp"><?php echo esc_html__('URL WhatsApp', 'ajinsafro-traveler-home'); ?></label></th>
                        <td>
                            <input id="ajth-agent-whatsapp" type="url" class="regular-text" name="ajth_agent[whatsapp_url]" value="<?php echo esc_attr($settings['whatsapp_url']); ?>">
                            <p class="description"><?php echo esc_html__('Exemple: https://wa.me/212539323874', 'ajinsafro-traveler-home'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Enregistrer', 'ajinsafro-traveler-home')); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_assets(): void
    {
        if (is_admin() || is_feed() || is_embed()) {
            return;
        }

        $settings = self::get_settings();
        if (empty($settings['enabled']) || empty($settings['show_on_public_pages'])) {
            return;
        }

        wp_enqueue_style(
            'ajth-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );

        wp_enqueue_style(
            'ajth-google-fonts',
            'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap',
            [],
            null
        );

        wp_enqueue_style(
            'ajth-agent-css',
            AJTH_URL . 'assets/css/ajinsafro-agent.css',
            ['ajth-fontawesome', 'ajth-google-fonts'],
            AJTH_VERSION
        );

        wp_enqueue_script(
            'ajth-agent-js',
            AJTH_URL . 'assets/js/ajinsafro-agent.js',
            [],
            AJTH_VERSION,
            true
        );

        $quick_replies = [
            ['label' => 'Voir les voyages', 'message' => 'Voir les voyages disponibles'],
            ['label' => 'Voir les hébergements', 'message' => 'Voir les hébergements disponibles'],
            ['label' => 'Offres Group Deals', 'message' => 'Montre-moi les offres group deals'],
            ['label' => 'Omra / Hajj', 'message' => 'Je cherche des offres Omra ou Hajj'],
            ['label' => 'Contacter Ajinsafro', 'message' => 'Comment contacter Ajinsafro ?'],
        ];

        wp_localize_script('ajth-agent-js', 'ajthAgentConfig', [
            'endpoint' => esc_url_raw(rest_url('ajth/v1/agent/chat')),
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'title' => $settings['title'],
            'subtitle' => $settings['subtitle'],
            'welcomeMessage' => wp_strip_all_tags($settings['welcome_message']),
            'quickReplies' => $quick_replies,
            'labels' => [
                'placeholder' => 'Posez votre question...',
                'send' => 'Envoyer',
                'close' => 'Fermer',
                'open' => 'Ouvrir Ajinsafro Agent',
                'loading' => 'Ajinsafro Agent écrit...',
            ],
        ]);
    }

    public function render_widget(): void
    {
        $settings = self::get_settings();
        if (is_admin() || empty($settings['enabled']) || empty($settings['show_on_public_pages'])) {
            return;
        }
        ?>
        <div id="ajth-agent-root" class="ajth-agent-root" aria-live="polite">
            <button type="button" class="ajth-agent-toggle" data-ajth-agent-open aria-label="<?php echo esc_attr__('Ouvrir Ajinsafro Agent', 'ajinsafro-traveler-home'); ?>">
                <span class="ajth-agent-toggle__icon"><i class="fas fa-comments" aria-hidden="true"></i></span>
                <span class="ajth-agent-toggle__text"><?php echo esc_html($settings['title']); ?></span>
            </button>

            <section class="ajth-agent-panel" data-ajth-agent-panel hidden>
                <header class="ajth-agent-panel__header">
                    <div>
                        <h3><?php echo esc_html($settings['title']); ?></h3>
                        <p><?php echo esc_html($settings['subtitle']); ?></p>
                    </div>
                    <button type="button" class="ajth-agent-panel__close" data-ajth-agent-close aria-label="<?php echo esc_attr__('Fermer', 'ajinsafro-traveler-home'); ?>">×</button>
                </header>

                <div class="ajth-agent-messages" data-ajth-agent-messages></div>
                <div class="ajth-agent-quick-replies" data-ajth-agent-quick-replies></div>
                <div class="ajth-agent-typing" data-ajth-agent-typing hidden>Ajinsafro Agent écrit...</div>

                <form class="ajth-agent-form" data-ajth-agent-form>
                    <input type="text" name="message" maxlength="400" autocomplete="off" placeholder="<?php echo esc_attr__('Posez votre question...', 'ajinsafro-traveler-home'); ?>">
                    <button type="submit"><?php echo esc_html__('Envoyer', 'ajinsafro-traveler-home'); ?></button>
                </form>
            </section>
        </div>
        <?php
    }

    public function register_rest_routes(): void
    {
        register_rest_route('ajth/v1', '/agent/chat', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_chat_request'],
            'permission_callback' => [$this, 'chat_permission'],
        ]);
    }

    public function chat_permission(WP_REST_Request $request)
    {
        $nonce = (string) $request->get_param('nonce');
        if ($nonce === '') {
            $nonce = (string) $request->get_header('X-Ajth-Nonce');
        }

        if ($nonce === '' || ! wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return new WP_Error('ajth_agent_forbidden', 'Invalid nonce.', ['status' => 403]);
        }

        return true;
    }

    public function handle_chat_request(WP_REST_Request $request): WP_REST_Response
    {
        $question = sanitize_text_field((string) $request->get_param('message'));
        $question = trim(preg_replace('/\s+/', ' ', $question));
        if ($question === '') {
            return new WP_REST_Response($this->build_response_payload(self::get_settings()['welcome_message']), 200);
        }

        $question = mb_substr($question, 0, 400);
        $payload = $this->build_response_for_question($question);

        return new WP_REST_Response($payload, 200);
    }

    private function build_response_for_question(string $question): array
    {
        $normalized = function_exists('mb_strtolower') ? mb_strtolower($question) : strtolower($question);
        $settings = self::get_settings();

        $external = apply_filters('ajth_agent_external_response', null, $question, $this);
        if (is_array($external) && ! empty($external['message'])) {
            return $this->build_response_payload(
                (string) $external['message'],
                isset($external['actions']) && is_array($external['actions']) ? $external['actions'] : [],
                isset($external['quick_replies']) && is_array($external['quick_replies']) ? $external['quick_replies'] : []
            );
        }

        if ($this->contains_any($normalized, ['contact', 'telephone', 'téléphone', 'email', 'whatsapp', 'appeler'])) {
            $message = sprintf(
                "Vous pouvez contacter l’équipe Ajinsafro par email à %s ou par téléphone au %s.",
                $settings['contact_email'],
                $settings['contact_phone']
            );

            return $this->build_response_payload($message, $this->contact_actions());
        }

        if ($this->contains_any($normalized, ['reservation', 'réservation', 'reserver', 'réserver', 'book'])) {
            $message = "Pour réserver, ouvrez la page du voyage ou de l’hébergement qui vous intéresse puis utilisez le bouton de réservation. Si vous voulez, je peux aussi vous orienter vers les offres disponibles.";

            return $this->build_response_payload($message, [
                $this->link_action('Voir les voyages', $this->page_url('voyages')),
                $this->link_action('Voir les hébergements', $this->page_url('hebergement')),
                $this->link_action('Contacter Ajinsafro', $this->page_url('contact', false)),
            ]);
        }

        if ($this->contains_any($normalized, ['paiement', 'payment', 'payer'])) {
            $message = "Le paiement dépend de l’offre choisie et du mode de réservation associé. Je n’ai pas toujours le détail exact en base pour chaque offre, donc je vous recommande de confirmer avec l’équipe Ajinsafro avant validation.";

            return $this->build_response_payload($message, $this->contact_actions());
        }

        if ($this->contains_any($normalized, ['group', 'groupe', 'group deal', 'group deals'])) {
            $message = "Ajinsafro propose des offres Group Deals. Vous pouvez consulter la page dédiée pour voir les offres disponibles et leur fonctionnement.";

            return $this->build_response_payload($message, [
                $this->link_action('Offres Group Deals', $this->page_url('group-deals')),
                $this->link_action('Contacter Ajinsafro', $this->page_url('contact', false)),
            ]);
        }

        if ($this->contains_any($normalized, ['omra', 'umrah', 'hajj'])) {
            $offers = $this->search_posts('st_tours', $question, 3, ['omra', 'hajj']);
            if (! empty($offers)) {
                $message = "Voici quelques offres liées à Omra / Hajj actuellement visibles sur le site :\n" . $this->format_post_list($offers);

                return $this->build_response_payload($message, array_merge(
                    $this->post_actions($offers),
                    $this->contact_actions()
                ));
            }

            $message = "Je n’ai pas trouvé d’offre Omra / Hajj clairement disponible dans les données publiques du site pour le moment. Vous pouvez contacter l’équipe Ajinsafro pour confirmation.";

            return $this->build_response_payload($message, $this->contact_actions());
        }

        if ($this->contains_any($normalized, ['hebergement', 'hébergement', 'hotel', 'hôtel', 'riad'])) {
            $hotels = $this->search_posts('st_hotel', $question, 3);
            if (! empty($hotels)) {
                $message = "Voici quelques hébergements visibles actuellement sur Ajinsafro :\n" . $this->format_post_list($hotels, false);

                return $this->build_response_payload($message, array_merge(
                    $this->post_actions($hotels),
                    [$this->link_action('Voir les hébergements', $this->page_url('hebergement'))]
                ));
            }

            return $this->build_response_payload(
                "Je n’ai pas trouvé d’hébergement correspondant dans les données publiques du site pour le moment.",
                [$this->link_action('Voir les hébergements', $this->page_url('hebergement'))]
            );
        }

        if ($this->contains_any($normalized, ['activite', 'activité', 'activities', 'excursion', 'visite'])) {
            $activities = $this->search_posts('st_activity', $question, 3);
            if (! empty($activities)) {
                $message = "Voici quelques activités visibles actuellement sur Ajinsafro :\n" . $this->format_post_list($activities, false);

                return $this->build_response_payload($message, array_merge(
                    $this->post_actions($activities),
                    [$this->link_action('Voir les activités', $this->page_url('activites'))]
                ));
            }

            return $this->build_response_payload(
                "Je n’ai pas trouvé d’activité correspondante dans les données publiques du site pour le moment.",
                [$this->link_action('Voir les activités', $this->page_url('activites'))]
            );
        }

        if ($this->contains_any($normalized, ['depart', 'départ', 'prochain', 'date', 'disponib', 'place'])) {
            $departures = $this->get_upcoming_departures($question, 3);
            if (! empty($departures)) {
                $message = "Voici les prochains départs que j’ai trouvés dans les données Ajinsafro :\n" . $this->format_departure_list($departures);

                return $this->build_response_payload($message, array_merge(
                    $this->departure_actions($departures),
                    [$this->link_action('Voir les voyages', $this->page_url('voyages'))]
                ));
            }

            return $this->build_response_payload(
                "Je n’ai pas trouvé de départ futur exploitable dans la base pour cette demande. Vous pouvez contacter l’équipe Ajinsafro pour confirmation.",
                $this->contact_actions()
            );
        }

        if ($this->contains_any($normalized, ['prix', 'tarif', 'budget', 'combien', 'cout', 'coût'])) {
            $tours = $this->search_tours_with_live_data($question, 3);
            if (! empty($tours)) {
                $message = "Voici quelques offres avec prix trouvées dans les données Ajinsafro :\n" . $this->format_tour_list($tours);

                return $this->build_response_payload($message, array_merge(
                    $this->tour_actions($tours),
                    [$this->link_action('Voir les voyages', $this->page_url('voyages'))]
                ));
            }

            return $this->build_response_payload(
                "Je n’ai pas encore cette information exacte. Vous pouvez contacter l’équipe Ajinsafro pour confirmation.",
                $this->contact_actions()
            );
        }

        if ($this->contains_any($normalized, ['voyage', 'circuit', 'sejour', 'séjour', 'disponible', 'offre'])) {
            $tours = $this->search_tours_with_live_data($question, 3);
            if (! empty($tours)) {
                $message = "Voici quelques voyages actuellement visibles sur Ajinsafro :\n" . $this->format_tour_list($tours);

                return $this->build_response_payload($message, array_merge(
                    $this->tour_actions($tours),
                    [$this->link_action('Voir les voyages', $this->page_url('voyages'))]
                ));
            }

            return $this->build_response_payload(
                "Je n’ai pas trouvé de voyage correspondant dans les données publiques du site pour le moment.",
                [$this->link_action('Voir les voyages', $this->page_url('voyages'))]
            );
        }

        $message = "Je peux vous aider pour les voyages, départs, prix, hébergements, activités, Group Deals, Omra/Hajj et réservations. Si vous préférez, choisissez un raccourci ci-dessous.";

        return $this->build_response_payload($message, [
            $this->link_action('Voir les voyages', $this->page_url('voyages')),
            $this->link_action('Voir les hébergements', $this->page_url('hebergement')),
            $this->link_action('Voir les activités', $this->page_url('activites')),
            $this->link_action('Offres Group Deals', $this->page_url('group-deals')),
        ]);
    }

    private function build_response_payload(string $message, array $actions = [], array $quick_replies = []): array
    {
        if ($quick_replies === []) {
            $quick_replies = [
                ['label' => 'Voir les voyages', 'message' => 'Voir les voyages disponibles'],
                ['label' => 'Voir les hébergements', 'message' => 'Voir les hébergements disponibles'],
                ['label' => 'Offres Group Deals', 'message' => 'Montre-moi les offres group deals'],
                ['label' => 'Omra / Hajj', 'message' => 'Je cherche des offres Omra ou Hajj'],
                ['label' => 'Contacter Ajinsafro', 'message' => 'Comment contacter Ajinsafro ?'],
            ];
        }

        return [
            'message' => $message,
            'actions' => array_values($actions),
            'quick_replies' => array_values($quick_replies),
        ];
    }

    private function search_tours_with_live_data(string $search, int $limit): array
    {
        $ids = get_posts([
            'post_type' => 'st_tours',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'fields' => 'ids',
            's' => $search,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if (empty($ids)) {
            $ids = get_posts([
                'post_type' => 'st_tours',
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'fields' => 'ids',
                'orderby' => 'date',
                'order' => 'DESC',
            ]);
        }

        $items = [];
        foreach ((array) $ids as $post_id) {
            $post_id = (int) $post_id;
            if ($post_id <= 0) {
                continue;
            }

            $price = $this->get_tour_price_value($post_id);
            $departure = $this->get_next_departure_for_tour($post_id);
            $items[] = [
                'id' => $post_id,
                'title' => get_the_title($post_id),
                'url' => get_permalink($post_id),
                'price' => $price,
                'departure' => $departure,
            ];
        }

        return $items;
    }

    private function search_posts(string $post_type, string $search, int $limit, array $fallback_keywords = []): array
    {
        $ids = get_posts([
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'fields' => 'ids',
            's' => $search,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if (empty($ids) && ! empty($fallback_keywords)) {
            foreach ($fallback_keywords as $keyword) {
                $ids = get_posts([
                    'post_type' => $post_type,
                    'post_status' => 'publish',
                    'posts_per_page' => $limit,
                    'fields' => 'ids',
                    's' => $keyword,
                    'orderby' => 'date',
                    'order' => 'DESC',
                ]);
                if (! empty($ids)) {
                    break;
                }
            }
        }

        if (empty($ids)) {
            return [];
        }

        $items = [];
        foreach ((array) $ids as $post_id) {
            $post_id = (int) $post_id;
            if ($post_id <= 0) {
                continue;
            }
            $items[] = [
                'id' => $post_id,
                'title' => get_the_title($post_id),
                'url' => get_permalink($post_id),
            ];
        }

        return $items;
    }

    private function get_upcoming_departures(string $search, int $limit): array
    {
        global $wpdb;

        $dates_table = $this->find_existing_table([
            $wpdb->prefix . 'aj_travel_dates',
            'aj_travel_dates',
        ]);

        if ($dates_table === '') {
            return [];
        }

        $columns = $this->get_table_columns($dates_table);
        $date_column = in_array('date', $columns, true) ? 'date' : (in_array('start_date', $columns, true) ? 'start_date' : '');
        $tour_column = in_array('travel_id', $columns, true) ? 'travel_id' : (in_array('tour_id', $columns, true) ? 'tour_id' : '');
        $active_column = in_array('is_active', $columns, true) ? 'is_active' : (in_array('active', $columns, true) ? 'active' : '');
        $seats_column = '';
        foreach (['seats', 'stock', 'places', 'available_seats', 'seats_available'] as $candidate_column) {
            if (in_array($candidate_column, $columns, true)) {
                $seats_column = $candidate_column;
                break;
            }
        }
        $price_column = '';
        foreach (['price_override', 'specific_price', 'adult_price', 'price'] as $candidate_column) {
            if (in_array($candidate_column, $columns, true)) {
                $price_column = $candidate_column;
                break;
            }
        }

        if ($date_column === '' || $tour_column === '') {
            return [];
        }

        $today = current_time('Y-m-d');
        $post_filter_sql = '';
        $post_filter_param = null;
        $search = trim($search);
        if ($search !== '') {
            $post_filter_sql = " AND p.post_title LIKE %s";
            $post_filter_param = '%' . $wpdb->esc_like($search) . '%';
        }

        $select = [
            "d.{$tour_column} AS tour_id",
            "d.{$date_column} AS departure_date",
            'p.post_title',
        ];
        if ($active_column !== '') {
            $select[] = "d.{$active_column} AS active_value";
        }
        if ($seats_column !== '') {
            $select[] = "d.{$seats_column} AS seats_value";
        }
        if ($price_column !== '') {
            $select[] = "d.{$price_column} AS price_value";
        }

        $active_sql = $active_column !== '' ? " AND (d.{$active_column} = 1 OR d.{$active_column} IS NULL)" : '';

        $sql = "
            SELECT " . implode(', ', $select) . "
            FROM {$dates_table} d
            INNER JOIN {$wpdb->posts} p ON p.ID = d.{$tour_column}
            WHERE p.post_type = 'st_tours'
              AND p.post_status = 'publish'
              AND d.{$date_column} >= %s
              {$active_sql}
              {$post_filter_sql}
            ORDER BY d.{$date_column} ASC
            LIMIT %d
        ";

        $params = [$today];
        if ($post_filter_param !== null) {
            $params[] = $post_filter_param;
        }
        $params[] = $limit;

        $rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        if (! $rows) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $tour_id = isset($row['tour_id']) ? (int) $row['tour_id'] : 0;
            if ($tour_id <= 0) {
                continue;
            }
            $items[] = [
                'tour_id' => $tour_id,
                'title' => get_the_title($tour_id),
                'url' => get_permalink($tour_id),
                'date' => isset($row['departure_date']) ? (string) $row['departure_date'] : '',
                'seats' => isset($row['seats_value']) && $row['seats_value'] !== '' && $row['seats_value'] !== null ? (int) $row['seats_value'] : null,
                'price' => isset($row['price_value']) && $row['price_value'] !== '' && $row['price_value'] !== null ? (float) $row['price_value'] : null,
            ];
        }

        return $items;
    }

    private function get_next_departure_for_tour(int $post_id): ?array
    {
        $items = $this->get_upcoming_departures(get_the_title($post_id), 5);
        foreach ($items as $item) {
            if ((int) ($item['tour_id'] ?? 0) === $post_id) {
                return $item;
            }
        }

        return null;
    }

    private function get_tour_price_value(int $post_id): ?float
    {
        $departure = $this->get_next_departure_for_tour($post_id);
        if (is_array($departure) && isset($departure['price']) && (float) $departure['price'] > 0) {
            return (float) $departure['price'];
        }

        foreach (['sale_price', 'adult_price', 'min_price', 'price', 'base_price'] as $meta_key) {
            $raw = get_post_meta($post_id, $meta_key, true);
            if ($raw !== '' && $raw !== null && (float) $raw > 0) {
                return (float) $raw;
            }
        }

        return null;
    }

    private function format_tour_list(array $tours): string
    {
        $lines = [];
        foreach ($tours as $tour) {
            $line = '• ' . (string) ($tour['title'] ?? '');
            if (! empty($tour['departure']['date'])) {
                $line .= ' — départ ' . $this->format_date((string) $tour['departure']['date']);
            }
            if (isset($tour['price']) && $tour['price'] !== null && (float) $tour['price'] > 0) {
                $line .= ' — à partir de ' . $this->format_price((float) $tour['price']) . ' DH';
            }
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    private function format_post_list(array $items, bool $include_price = false): string
    {
        $lines = [];
        foreach ($items as $item) {
            $line = '• ' . (string) ($item['title'] ?? '');
            if ($include_price && isset($item['price']) && (float) $item['price'] > 0) {
                $line .= ' — ' . $this->format_price((float) $item['price']) . ' DH';
            }
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    private function format_departure_list(array $departures): string
    {
        $lines = [];
        foreach ($departures as $item) {
            $line = '• ' . (string) ($item['title'] ?? '') . ' — ' . $this->format_date((string) ($item['date'] ?? ''));
            if (isset($item['seats']) && $item['seats'] !== null) {
                $line .= ' — ' . max(0, (int) $item['seats']) . ' place(s)';
            }
            if (isset($item['price']) && $item['price'] !== null && (float) $item['price'] > 0) {
                $line .= ' — ' . $this->format_price((float) $item['price']) . ' DH';
            }
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    private function post_actions(array $items): array
    {
        $actions = [];
        foreach (array_slice($items, 0, 3) as $item) {
            $actions[] = $this->link_action((string) ($item['title'] ?? 'Voir'), (string) ($item['url'] ?? '#'));
        }

        return $actions;
    }

    private function tour_actions(array $items): array
    {
        return $this->post_actions($items);
    }

    private function departure_actions(array $items): array
    {
        return $this->post_actions($items);
    }

    private function contact_actions(): array
    {
        $settings = self::get_settings();
        $actions = [
            $this->link_action('Email Ajinsafro', 'mailto:' . $settings['contact_email']),
            $this->link_action('Appeler Ajinsafro', 'tel:' . preg_replace('/[^0-9+]/', '', $settings['contact_phone'])),
        ];

        if (! empty($settings['whatsapp_url'])) {
            $actions[] = $this->link_action('WhatsApp', $settings['whatsapp_url']);
        }

        return $actions;
    }

    private function link_action(string $label, string $url): array
    {
        return [
            'label' => $label,
            'url' => $url,
        ];
    }

    private function page_url(string $slug, bool $fallback_to_slug = true): string
    {
        $page = get_page_by_path($slug);
        if ($page instanceof WP_Post) {
            $url = get_permalink($page);
            if ($url) {
                return $url;
            }
        }

        if ($fallback_to_slug) {
            return home_url('/' . trim($slug, '/') . '/');
        }

        return home_url('/');
    }

    private function format_price(float $price): string
    {
        return number_format($price, 0, ',', ' ');
    }

    private function format_date(string $date): string
    {
        $timestamp = strtotime($date);
        if (! $timestamp) {
            return $date;
        }

        return date_i18n('d M Y', $timestamp);
    }

    private function contains_any(string $haystack, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            $keyword = function_exists('mb_strtolower') ? mb_strtolower($keyword) : strtolower($keyword);
            if ($keyword !== '' && strpos($haystack, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private function find_existing_table(array $candidates): string
    {
        global $wpdb;

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            $exists = (bool) $wpdb->get_var($wpdb->prepare(
                'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s',
                $candidate
            ));

            if ($exists) {
                return $candidate;
            }
        }

        return '';
    }

    private function get_table_columns(string $table_name): array
    {
        global $wpdb;

        if ($table_name === '') {
            return [];
        }

        $rows = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}", ARRAY_A);
        if (! is_array($rows)) {
            return [];
        }

        $columns = [];
        foreach ($rows as $row) {
            if (! empty($row['Field'])) {
                $columns[] = (string) $row['Field'];
            }
        }

        return $columns;
    }
}
