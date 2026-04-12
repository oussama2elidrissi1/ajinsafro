<?php
/**
 * Admin Settings Page — "Ajinsafro Home"
 *
 * Stores all homepage configuration in a single option: ajth_home_settings
 *
 * @package AjinsafroTravelerHome
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class AJTH_Admin_Settings {

    /** Option key */
    const OPT = 'ajth_home_settings';

    /** Nonce action */
    const NONCE = 'ajth_save_home_settings';

    /** Option key — shared with Laravel (WP_CATALOG_INVALIDATE_SECRET) and REST debug / invalidate */
    const OPT_LARAVEL_SECRET = 'ajth_laravel_invalidate_secret';

    /** Nonce for Laravel secret page */
    const NONCE_LARAVEL = 'ajth_save_laravel_secret';

    public function __construct() {
        add_action( 'admin_menu',            array( $this, 'add_menu_page' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'admin_init',            array( $this, 'handle_save' ) );
        add_action( 'admin_init',            array( $this, 'handle_laravel_secret_save' ) );
    }

    /* ──────────────────────────────────────────
     * Menu
     * ────────────────────────────────────────── */
    public function add_menu_page() {
        add_menu_page(
            __( 'Ajinsafro Home', 'ajinsafro-traveler-home' ),
            __( 'Ajinsafro Home', 'ajinsafro-traveler-home' ),
            'manage_options',
            'ajth-home-settings',
            array( $this, 'render_page' ),
            'dashicons-admin-home',
            30
        );

        add_submenu_page(
            'ajth-home-settings',
            __( 'Laravel & debug API', 'ajinsafro-traveler-home' ),
            __( 'Laravel & debug API', 'ajinsafro-traveler-home' ),
            'manage_options',
            'ajth-laravel-secret',
            array( $this, 'render_laravel_secret_page' )
        );
    }

    /* ──────────────────────────────────────────
     * Admin assets
     * ────────────────────────────────────────── */
    public function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_ajth-home-settings' !== $hook && 'ajth-home-settings_page_ajth-laravel-secret' !== $hook ) {
            return;
        }

        // WordPress media uploader
        wp_enqueue_media();

        wp_enqueue_style(
            'ajth-admin-css',
            AJTH_URL . 'assets/css/admin.css',
            array(),
            AJTH_VERSION
        );

        wp_enqueue_script(
            'ajth-admin-js',
            AJTH_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            AJTH_VERSION,
            true
        );
    }

    /* ──────────────────────────────────────────
     * Save handler
     * ────────────────────────────────────────── */
    public function handle_save() {
        if ( ! isset( $_POST['ajth_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['ajth_nonce'], self::NONCE ) ) {
            wp_die( __( 'Nonce invalide.', 'ajinsafro-traveler-home' ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Permissions insuffisantes.', 'ajinsafro-traveler-home' ) );
        }

        $data = array();

        // Hero
        $data['hero_image']    = isset( $_POST['ajth']['hero_image'] )    ? absint( $_POST['ajth']['hero_image'] )                     : '';
        $data['hero_title']    = isset( $_POST['ajth']['hero_title'] )    ? sanitize_text_field( $_POST['ajth']['hero_title'] )         : '';
        $data['hero_subtitle'] = isset( $_POST['ajth']['hero_subtitle'] ) ? sanitize_text_field( $_POST['ajth']['hero_subtitle'] )      : '';

        // Last minute title
        $data['last_minute_title'] = isset( $_POST['ajth']['last_minute_title'] ) ? sanitize_text_field( $_POST['ajth']['last_minute_title'] ) : '';

        // Regions repeater
        $data['regions'] = array();
        if ( ! empty( $_POST['ajth']['regions'] ) && is_array( $_POST['ajth']['regions'] ) ) {
            foreach ( $_POST['ajth']['regions'] as $region ) {
                $data['regions'][] = array(
                    'title' => sanitize_text_field( $region['title'] ?? '' ),
                    'image' => absint( $region['image'] ?? 0 ),
                    'url'   => esc_url_raw( $region['url'] ?? '' ),
                );
            }
        }

        // Good spots (4 fixed items)
        $data['good_spots'] = array();
        $labels = array( 'Restaurants', 'Loisirs', 'Que faire ?', 'Shopping' );
        if ( ! empty( $_POST['ajth']['good_spots'] ) && is_array( $_POST['ajth']['good_spots'] ) ) {
            foreach ( $_POST['ajth']['good_spots'] as $i => $spot ) {
                $data['good_spots'][] = array(
                    'title' => sanitize_text_field( $spot['title'] ?? ( $labels[ $i ] ?? '' ) ),
                    'image' => absint( $spot['image'] ?? 0 ),
                    'url'   => esc_url_raw( $spot['url'] ?? '' ),
                );
            }
        }

        update_option( self::OPT, $data );

        // Redirect with success notice
        add_settings_error( 'ajth_messages', 'ajth_saved', __( 'Paramètres enregistrés.', 'ajinsafro-traveler-home' ), 'updated' );

        wp_safe_redirect( admin_url( 'admin.php?page=ajth-home-settings&saved=1' ) );
        exit;
    }

    /**
     * Save shared secret for Laravel catalog invalidate + REST debug endpoints.
     */
    public function handle_laravel_secret_save() {
        if ( empty( $_POST['ajth_laravel_secret_form'] ) || ! isset( $_POST['ajth_laravel_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ajth_laravel_nonce'] ) ), self::NONCE_LARAVEL ) ) {
            wp_die( esc_html__( 'Nonce invalide.', 'ajinsafro-traveler-home' ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permissions insuffisantes.', 'ajinsafro-traveler-home' ) );
        }

        if ( ! empty( $_POST['ajth_clear_laravel_secret'] ) ) {
            delete_option( self::OPT_LARAVEL_SECRET );
            add_settings_error( 'ajth_laravel', 'ajth_cleared', __( 'Secret supprimé. Les endpoints REST exigeront wp-config ou un nouveau secret.', 'ajinsafro-traveler-home' ), 'updated' );
            wp_safe_redirect( admin_url( 'admin.php?page=ajth-laravel-secret&saved=1' ) );
            exit;
        }

        if ( isset( $_POST['ajth_laravel_invalidate_secret'] ) ) {
            $raw = trim( wp_unslash( $_POST['ajth_laravel_invalidate_secret'] ) );
            if ( $raw !== '' ) {
                update_option( self::OPT_LARAVEL_SECRET, $raw );
                add_settings_error( 'ajth_laravel', 'ajth_saved_secret', __( 'Secret enregistré. Même valeur que WP_CATALOG_INVALIDATE_SECRET côté Laravel.', 'ajinsafro-traveler-home' ), 'updated' );
            }
        }

        wp_safe_redirect( admin_url( 'admin.php?page=ajth-laravel-secret&saved=1' ) );
        exit;
    }

    /* ──────────────────────────────────────────
     * Render page
     * ────────────────────────────────────────── */
    public function render_page() {
        $s = ajth_get_settings();
        ?>
        <div class="wrap ajth-admin-wrap">
            <h1><?php esc_html_e( 'Ajinsafro Home — Paramètres', 'ajinsafro-traveler-home' ); ?></h1>

            <?php if ( isset( $_GET['saved'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Paramètres enregistrés.', 'ajinsafro-traveler-home' ); ?></p></div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field( self::NONCE, 'ajth_nonce' ); ?>

                <!-- ════════════════════════════════════════
                     HERO
                     ════════════════════════════════════════ -->
                <h2><?php esc_html_e( 'Hero', 'ajinsafro-traveler-home' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label><?php esc_html_e( 'Image de fond', 'ajinsafro-traveler-home' ); ?></label></th>
                        <td>
                            <?php $hero_img_url = $s['hero_image'] ? wp_get_attachment_image_url( $s['hero_image'], 'large' ) : ''; ?>
                            <input type="hidden" name="ajth[hero_image]" id="ajth-hero-image" value="<?php echo esc_attr( $s['hero_image'] ); ?>">
                            <div class="ajth-img-preview" id="ajth-hero-preview">
                                <?php if ( $hero_img_url ) : ?>
                                    <img src="<?php echo esc_url( $hero_img_url ); ?>" style="max-width:400px;height:auto;">
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button ajth-upload-btn" data-target="#ajth-hero-image" data-preview="#ajth-hero-preview"><?php esc_html_e( 'Choisir une image', 'ajinsafro-traveler-home' ); ?></button>
                            <button type="button" class="button ajth-remove-btn" data-target="#ajth-hero-image" data-preview="#ajth-hero-preview"><?php esc_html_e( 'Supprimer', 'ajinsafro-traveler-home' ); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="ajth-hero-title"><?php esc_html_e( 'Titre', 'ajinsafro-traveler-home' ); ?></label></th>
                        <td><input type="text" id="ajth-hero-title" name="ajth[hero_title]" value="<?php echo esc_attr( $s['hero_title'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="ajth-hero-subtitle"><?php esc_html_e( 'Sous-titre', 'ajinsafro-traveler-home' ); ?></label></th>
                        <td><input type="text" id="ajth-hero-subtitle" name="ajth[hero_subtitle]" value="<?php echo esc_attr( $s['hero_subtitle'] ); ?>" class="regular-text"></td>
                    </tr>
                </table>

                <!-- ════════════════════════════════════════
                     OFFRES DERNIÈRE MINUTE
                     ════════════════════════════════════════ -->
                <h2><?php esc_html_e( 'Offres de dernière minute', 'ajinsafro-traveler-home' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="ajth-lm-title"><?php esc_html_e( 'Titre de la section', 'ajinsafro-traveler-home' ); ?></label></th>
                        <td><input type="text" id="ajth-lm-title" name="ajth[last_minute_title]" value="<?php echo esc_attr( $s['last_minute_title'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th></th>
                        <td><p class="description"><?php esc_html_e( 'Les tours sont chargés automatiquement depuis le post type st_tours (6 derniers).', 'ajinsafro-traveler-home' ); ?></p></td>
                    </tr>
                </table>

                <!-- ════════════════════════════════════════
                     DESTINATIONS PAR RÉGION
                     ════════════════════════════════════════ -->
                <h2><?php esc_html_e( 'Destinations par région', 'ajinsafro-traveler-home' ); ?></h2>
                <div id="ajth-regions-wrap">
                    <?php if ( ! empty( $s['regions'] ) ) : ?>
                        <?php foreach ( $s['regions'] as $i => $region ) : ?>
                            <?php $this->render_region_row( $i, $region ); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="button" id="ajth-add-region"><?php esc_html_e( '+ Ajouter une destination', 'ajinsafro-traveler-home' ); ?></button>

                <!-- ════════════════════════════════════════
                     LES BONS COINS
                     ════════════════════════════════════════ -->
                <h2><?php esc_html_e( 'Les bons coins sur votre destination', 'ajinsafro-traveler-home' ); ?></h2>
                <table class="form-table">
                    <?php
                    $default_labels = array( 'Restaurants', 'Loisirs', 'Que faire ?', 'Shopping' );
                    for ( $i = 0; $i < 4; $i++ ) :
                        $spot = $s['good_spots'][ $i ] ?? array( 'title' => $default_labels[ $i ], 'image' => '', 'url' => '' );
                        $spot_img_url = ! empty( $spot['image'] ) ? wp_get_attachment_image_url( $spot['image'], 'medium' ) : '';
                    ?>
                    <tr>
                        <th><?php echo esc_html( $default_labels[ $i ] ); ?></th>
                        <td>
                            <p>
                                <label><?php esc_html_e( 'Titre', 'ajinsafro-traveler-home' ); ?></label><br>
                                <input type="text" name="ajth[good_spots][<?php echo $i; ?>][title]" value="<?php echo esc_attr( $spot['title'] ); ?>" class="regular-text">
                            </p>
                            <p>
                                <label><?php esc_html_e( 'URL', 'ajinsafro-traveler-home' ); ?></label><br>
                                <input type="url" name="ajth[good_spots][<?php echo $i; ?>][url]" value="<?php echo esc_url( $spot['url'] ); ?>" class="regular-text">
                            </p>
                            <p>
                                <label><?php esc_html_e( 'Image', 'ajinsafro-traveler-home' ); ?></label><br>
                                <input type="hidden" name="ajth[good_spots][<?php echo $i; ?>][image]" id="ajth-spot-image-<?php echo $i; ?>" value="<?php echo esc_attr( $spot['image'] ); ?>">
                                <div class="ajth-img-preview" id="ajth-spot-preview-<?php echo $i; ?>">
                                    <?php if ( $spot_img_url ) : ?>
                                        <img src="<?php echo esc_url( $spot_img_url ); ?>" style="max-width:200px;height:auto;">
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="button ajth-upload-btn" data-target="#ajth-spot-image-<?php echo $i; ?>" data-preview="#ajth-spot-preview-<?php echo $i; ?>"><?php esc_html_e( 'Choisir', 'ajinsafro-traveler-home' ); ?></button>
                                <button type="button" class="button ajth-remove-btn" data-target="#ajth-spot-image-<?php echo $i; ?>" data-preview="#ajth-spot-preview-<?php echo $i; ?>"><?php esc_html_e( 'Supprimer', 'ajinsafro-traveler-home' ); ?></button>
                            </p>
                        </td>
                    </tr>
                    <?php endfor; ?>
                </table>

                <?php submit_button( __( 'Enregistrer les paramètres', 'ajinsafro-traveler-home' ) ); ?>
            </form>
        </div>

        <!-- Hidden template for region repeater row -->
        <script type="text/html" id="tmpl-ajth-region-row">
            <div class="ajth-region-row" data-index="{{data.index}}">
                <h4><?php esc_html_e( 'Destination', 'ajinsafro-traveler-home' ); ?> #<span class="ajth-region-num">{{data.index+1}}</span>
                    <button type="button" class="button button-link-delete ajth-remove-region"><?php esc_html_e( 'Supprimer', 'ajinsafro-traveler-home' ); ?></button>
                </h4>
                <p>
                    <label><?php esc_html_e( 'Titre', 'ajinsafro-traveler-home' ); ?></label><br>
                    <input type="text" name="ajth[regions][{{data.index}}][title]" value="" class="regular-text">
                </p>
                <p>
                    <label><?php esc_html_e( 'URL', 'ajinsafro-traveler-home' ); ?></label><br>
                    <input type="url" name="ajth[regions][{{data.index}}][url]" value="" class="regular-text">
                </p>
                <p>
                    <label><?php esc_html_e( 'Image', 'ajinsafro-traveler-home' ); ?></label><br>
                    <input type="hidden" name="ajth[regions][{{data.index}}][image]" id="ajth-region-image-{{data.index}}" value="">
                    <div class="ajth-img-preview" id="ajth-region-preview-{{data.index}}"></div>
                    <button type="button" class="button ajth-upload-btn" data-target="#ajth-region-image-{{data.index}}" data-preview="#ajth-region-preview-{{data.index}}"><?php esc_html_e( 'Choisir', 'ajinsafro-traveler-home' ); ?></button>
                    <button type="button" class="button ajth-remove-btn" data-target="#ajth-region-image-{{data.index}}" data-preview="#ajth-region-preview-{{data.index}}"><?php esc_html_e( 'Supprimer', 'ajinsafro-traveler-home' ); ?></button>
                </p>
            </div>
        </script>
        <?php
    }

    /**
     * Page: shared secret with Laravel (invalidate + debug REST).
     */
    public function render_laravel_secret_page() {
        $has_constant = defined( 'AJTH_LARAVEL_INVALIDATE_SECRET' ) && AJTH_LARAVEL_INVALIDATE_SECRET !== '';
        $has_option   = (string) get_option( self::OPT_LARAVEL_SECRET, '' ) !== '';
        $active       = $has_constant || $has_option;
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Laravel & debug API', 'ajinsafro-traveler-home' ); ?></h1>
            <p class="description">
                <?php esc_html_e( 'Même secret que dans le .env Laravel : WP_CATALOG_INVALIDATE_SECRET. Il déverrouille l’endpoint REST invalidate-posts (cache WordPress après sync). Le diagnostic se fait côté Laravel : php artisan wp:catalog-inspect.', 'ajinsafro-traveler-home' ); ?>
            </p>

            <?php settings_errors( 'ajth_laravel' ); ?>
            <?php if ( isset( $_GET['saved'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Enregistré.', 'ajinsafro-traveler-home' ); ?></p></div>
            <?php endif; ?>

            <?php if ( $has_constant ) : ?>
                <div class="notice notice-info">
                    <p>
                        <?php esc_html_e( 'Le secret est défini dans wp-config.php (AJTH_LARAVEL_INVALIDATE_SECRET). Il a priorité sur l’option ci-dessous.', 'ajinsafro-traveler-home' ); ?>
                    </p>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field( self::NONCE_LARAVEL, 'ajth_laravel_nonce' ); ?>
                <input type="hidden" name="ajth_laravel_secret_form" value="1" />

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="ajth_laravel_invalidate_secret"><?php esc_html_e( 'Secret partagé (X-Ajth-Secret)', 'ajinsafro-traveler-home' ); ?></label>
                        </th>
                        <td>
                            <input type="password" name="ajth_laravel_invalidate_secret" id="ajth_laravel_invalidate_secret" class="regular-text" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Coller la même valeur que WP_CATALOG_INVALIDATE_SECRET', 'ajinsafro-traveler-home' ); ?>" />
                            <p class="description">
                                <?php
                                if ( $has_option && ! $has_constant ) {
                                    esc_html_e( 'Un secret est déjà enregistré. Saisissez une nouvelle valeur pour le remplacer.', 'ajinsafro-traveler-home' );
                                } else {
                                    esc_html_e( 'Laissez vide si vous ne souhaitez pas modifier (après première sauvegarde).', 'ajinsafro-traveler-home' );
                                }
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Enregistrer le secret', 'ajinsafro-traveler-home' ) ); ?>

                <p>
                    <label>
                        <input type="checkbox" name="ajth_clear_laravel_secret" value="1" />
                        <?php esc_html_e( 'Supprimer le secret stocké en base (option WordPress uniquement ; ne supprime pas wp-config).', 'ajinsafro-traveler-home' ); ?>
                    </label>
                </p>
            </form>

            <hr />
            <h2><?php esc_html_e( 'État', 'ajinsafro-traveler-home' ); ?></h2>
            <ul style="list-style:disc;margin-left:1.5em;">
                <li>
                    <?php echo $active ? esc_html__( 'Endpoints REST : configurés (constante ou option).', 'ajinsafro-traveler-home' ) : esc_html__( 'Endpoints REST : non configurés — 403 ajth_debug_forbidden.', 'ajinsafro-traveler-home' ); ?>
                </li>
                <li><?php esc_html_e( 'Option WordPress :', 'ajinsafro-traveler-home' ); ?> <code>ajth_laravel_invalidate_secret</code> — <?php echo $has_option ? esc_html__( 'définie', 'ajinsafro-traveler-home' ) : esc_html__( 'vide', 'ajinsafro-traveler-home' ); ?></li>
            </ul>

            <h2><?php esc_html_e( 'Diagnostic interne (Laravel, pas d’API publique)', 'ajinsafro-traveler-home' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Sur le serveur Laravel : lecture directe MySQL (connexion wp). Voir config/wordpress_catalog_sources.php et la commande :', 'ajinsafro-traveler-home' ); ?></p>
            <pre style="background:#f6f7f7;padding:12px;overflow:auto;">php artisan wp:catalog-inspect activity 1483
php artisan wp:catalog-inspect transfer 14353
php artisan wp:catalog-inspect hotel ID_WP
php artisan wp:catalog-inspect voyage ID_WP
php artisan wp:catalog-inspect transfer 14353 --json</pre>

            <h2><?php esc_html_e( 'Invalidation cache WordPress (après sync Laravel)', 'ajinsafro-traveler-home' ); ?></h2>
            <pre style="background:#f6f7f7;padding:12px;overflow:auto;">curl -sS -X POST -H "X-Ajth-Secret: YOUR_SECRET" -H "Content-Type: application/json" -d "{\"post_ids\":[14353]}" "<?php echo esc_url( home_url( '/wp-json/ajth/v1/invalidate-posts' ) ); ?>"</pre>
        </div>
        <?php
    }

    /**
     * Render a single region repeater row (used for saved data).
     */
    private function render_region_row( $index, $region ) {
        $img_url = ! empty( $region['image'] ) ? wp_get_attachment_image_url( $region['image'], 'medium' ) : '';
        ?>
        <div class="ajth-region-row" data-index="<?php echo esc_attr( $index ); ?>">
            <h4><?php esc_html_e( 'Destination', 'ajinsafro-traveler-home' ); ?> #<?php echo intval( $index ) + 1; ?>
                <button type="button" class="button button-link-delete ajth-remove-region"><?php esc_html_e( 'Supprimer', 'ajinsafro-traveler-home' ); ?></button>
            </h4>
            <p>
                <label><?php esc_html_e( 'Titre', 'ajinsafro-traveler-home' ); ?></label><br>
                <input type="text" name="ajth[regions][<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $region['title'] ); ?>" class="regular-text">
            </p>
            <p>
                <label><?php esc_html_e( 'URL', 'ajinsafro-traveler-home' ); ?></label><br>
                <input type="url" name="ajth[regions][<?php echo esc_attr( $index ); ?>][url]" value="<?php echo esc_url( $region['url'] ); ?>" class="regular-text">
            </p>
            <p>
                <label><?php esc_html_e( 'Image', 'ajinsafro-traveler-home' ); ?></label><br>
                <input type="hidden" name="ajth[regions][<?php echo esc_attr( $index ); ?>][image]" id="ajth-region-image-<?php echo esc_attr( $index ); ?>" value="<?php echo esc_attr( $region['image'] ); ?>">
                <div class="ajth-img-preview" id="ajth-region-preview-<?php echo esc_attr( $index ); ?>">
                    <?php if ( $img_url ) : ?>
                        <img src="<?php echo esc_url( $img_url ); ?>" style="max-width:200px;height:auto;">
                    <?php endif; ?>
                </div>
                <button type="button" class="button ajth-upload-btn" data-target="#ajth-region-image-<?php echo esc_attr( $index ); ?>" data-preview="#ajth-region-preview-<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Choisir', 'ajinsafro-traveler-home' ); ?></button>
                <button type="button" class="button ajth-remove-btn" data-target="#ajth-region-image-<?php echo esc_attr( $index ); ?>" data-preview="#ajth-region-preview-<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Supprimer', 'ajinsafro-traveler-home' ); ?></button>
            </p>
        </div>
        <?php
    }
}
