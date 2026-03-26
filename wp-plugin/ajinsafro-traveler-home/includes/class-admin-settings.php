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

    public function __construct() {
        add_action( 'admin_menu',            array( $this, 'add_menu_page' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'admin_init',            array( $this, 'handle_save' ) );
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
    }

    /* ──────────────────────────────────────────
     * Admin assets
     * ────────────────────────────────────────── */
    public function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_ajth-home-settings' !== $hook ) {
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
