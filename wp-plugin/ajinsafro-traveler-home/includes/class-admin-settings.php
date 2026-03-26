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

        // Holiday theme section (left promo + right slider cards)
        $theme_raw = isset( $_POST['ajth']['holiday_theme'] ) && is_array( $_POST['ajth']['holiday_theme'] )
            ? $_POST['ajth']['holiday_theme']
            : array();
        $data['holiday_theme'] = $this->sanitize_holiday_theme( $theme_raw );

        // Legacy option remains as fallback only.
        update_option( self::OPT, $data );

        // Source of truth for front rendering.
        $modern = $this->load_home_settings_option();
        if ( ! isset( $modern['sections'] ) || ! is_array( $modern['sections'] ) ) {
            $modern['sections'] = array();
        }
        if ( ! isset( $modern['section_order'] ) || ! is_array( $modern['section_order'] ) ) {
            $modern['section_order'] = array();
        }
        $modern['holiday_theme'] = $data['holiday_theme'];
        $modern['sections']['holiday_theme'] = ! empty( $data['holiday_theme']['enabled'] );
        $modern['section_order'] = $this->normalize_section_order(
            $modern['section_order'],
            ! empty( $data['holiday_theme']['enabled'] )
        );
        update_option( 'aj_home_settings', $modern );

        // Redirect with success notice
        add_settings_error( 'ajth_messages', 'ajth_saved', __( 'Paramètres enregistrés.', 'ajinsafro-traveler-home' ), 'updated' );

        wp_safe_redirect( admin_url( 'admin.php?page=ajth-home-settings&saved=1' ) );
        exit;
    }

    private function sanitize_holiday_theme( $raw ) {
        $items = array();
        if ( ! empty( $raw['items'] ) && is_array( $raw['items'] ) ) {
            foreach ( $raw['items'] as $item ) {
                $title = sanitize_text_field( $item['title'] ?? '' );
                if ( $title === '' ) {
                    continue;
                }
                $tags_raw = $item['tags'] ?? array();
                if ( is_string( $tags_raw ) ) {
                    $parts = preg_split( '/[\r\n,]+/', $tags_raw );
                    $parts = is_array( $parts ) ? $parts : array();
                    $tags = array_values( array_filter( array_map( 'sanitize_text_field', array_map( 'trim', $parts ) ) ) );
                } elseif ( is_array( $tags_raw ) ) {
                    $tags = array_values( array_filter( array_map( function( $t ) {
                        return sanitize_text_field( trim( (string) $t ) );
                    }, $tags_raw ) ) );
                } else {
                    $tags = array();
                }
                $items[] = array(
                    'title' => $title,
                    'image_url' => esc_url_raw( $item['image_url'] ?? '' ),
                    'tags' => $tags,
                    'button_text' => sanitize_text_field( $item['button_text'] ?? '' ),
                    'button_url' => esc_url_raw( $item['button_url'] ?? '' ),
                    'order' => intval( $item['order'] ?? 0 ),
                    'active' => ajth_truthy( $item['active'] ?? false ),
                );
            }
        }

        return array(
            'enabled' => ! empty( $raw['enabled'] ) ? 1 : 0,
            'eyebrow' => sanitize_text_field( $raw['eyebrow'] ?? '' ),
            'title_line_1' => sanitize_text_field( $raw['title_line_1'] ?? '' ),
            'title_line_2' => sanitize_text_field( $raw['title_line_2'] ?? '' ),
            'title_line_3' => sanitize_text_field( $raw['title_line_3'] ?? '' ),
            'subtitle' => sanitize_text_field( $raw['subtitle'] ?? '' ),
            'left_image_url' => esc_url_raw( $raw['left_image_url'] ?? '' ),
            'deco_image_url' => esc_url_raw( $raw['deco_image_url'] ?? '' ),
            'button_text' => sanitize_text_field( $raw['button_text'] ?? '' ),
            'button_url' => esc_url_raw( $raw['button_url'] ?? '' ),
            'items' => $items,
        );
    }

    private function load_home_settings_option() {
        $raw = get_option( 'aj_home_settings', array() );
        if ( is_array( $raw ) ) {
            return $raw;
        }
        if ( is_string( $raw ) && $raw !== '' ) {
            $decoded = json_decode( $raw, true );
            if ( is_array( $decoded ) ) {
                return $decoded;
            }
        }
        return array();
    }

    private function normalize_section_order( array $order, bool $holidayEnabled ) {
        $allowed = array(
            'search',
            'last_minute',
            'accommodations',
            'holiday_theme',
            'regions',
            'good_spots',
            'promotions',
            'whatsapp_banner',
            'cruises',
            'newsletter',
        );

        $normalized = array();
        foreach ( $order as $key ) {
            $key = is_string( $key ) ? trim( $key ) : '';
            if ( $key === '' ) {
                continue;
            }
            if ( strpos( $key, 'custom_' ) === 0 ) {
                if ( ! in_array( $key, $normalized, true ) ) {
                    $normalized[] = $key;
                }
                continue;
            }
            if ( in_array( $key, $allowed, true ) && ! in_array( $key, $normalized, true ) ) {
                $normalized[] = $key;
            }
        }

        if ( $holidayEnabled ) {
            if ( ! in_array( 'holiday_theme', $normalized, true ) ) {
                $after = array_search( 'accommodations', $normalized, true );
                if ( $after === false ) {
                    $normalized[] = 'holiday_theme';
                } else {
                    array_splice( $normalized, $after + 1, 0, array( 'holiday_theme' ) );
                }
            }
        } else {
            $normalized = array_values( array_filter( $normalized, function ( $k ) {
                return $k !== 'holiday_theme';
            } ) );
        }

        return $normalized;
    }

    private function parse_holiday_theme( $s ) {
        $theme = isset( $s['holiday_theme'] ) && is_array( $s['holiday_theme'] ) ? $s['holiday_theme'] : array();
        return array(
            'enabled' => ! empty( $theme['enabled'] ) ? 1 : 0,
            'eyebrow' => isset( $theme['eyebrow'] ) ? (string) $theme['eyebrow'] : '',
            'title_line_1' => isset( $theme['title_line_1'] ) ? (string) $theme['title_line_1'] : '',
            'title_line_2' => isset( $theme['title_line_2'] ) ? (string) $theme['title_line_2'] : '',
            'title_line_3' => isset( $theme['title_line_3'] ) ? (string) $theme['title_line_3'] : '',
            'subtitle' => isset( $theme['subtitle'] ) ? (string) $theme['subtitle'] : '',
            'left_image_url' => isset( $theme['left_image_url'] ) ? (string) $theme['left_image_url'] : '',
            'deco_image_url' => isset( $theme['deco_image_url'] ) ? (string) $theme['deco_image_url'] : '',
            'button_text' => isset( $theme['button_text'] ) ? (string) $theme['button_text'] : '',
            'button_url' => isset( $theme['button_url'] ) ? (string) $theme['button_url'] : '',
            'items' => isset( $theme['items'] ) && is_array( $theme['items'] ) ? $theme['items'] : array(),
        );
    }

    /* ──────────────────────────────────────────
     * Render page
     * ────────────────────────────────────────── */
    public function render_page() {
        $s = ajth_get_settings();
        $theme = $this->parse_holiday_theme( $s );
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

                <h2><?php esc_html_e( 'Voyages par thème (Holidays by Theme)', 'ajinsafro-traveler-home' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="ajth-theme-enabled"><?php esc_html_e( 'Activer la section', 'ajinsafro-traveler-home' ); ?></label></th>
                        <td><label><input type="checkbox" id="ajth-theme-enabled" name="ajth[holiday_theme][enabled]" value="1" <?php checked( ! empty( $theme['enabled'] ) ); ?>> <?php esc_html_e( 'Afficher sur la home', 'ajinsafro-traveler-home' ); ?></label></td>
                    </tr>
                    <tr>
                        <th><label for="ajth-theme-eyebrow"><?php esc_html_e( 'Petit titre', 'ajinsafro-traveler-home' ); ?></label></th>
                        <td><input type="text" id="ajth-theme-eyebrow" name="ajth[holiday_theme][eyebrow]" value="<?php echo esc_attr( $theme['eyebrow'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="ajth-theme-line1"><?php esc_html_e( 'Grand titre ligne 1', 'ajinsafro-traveler-home' ); ?></label></th>
                        <td><input type="text" id="ajth-theme-line1" name="ajth[holiday_theme][title_line_1]" value="<?php echo esc_attr( $theme['title_line_1'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="ajth-theme-line2"><?php esc_html_e( 'Grand titre ligne 2', 'ajinsafro-traveler-home' ); ?></label></th>
                        <td><input type="text" id="ajth-theme-line2" name="ajth[holiday_theme][title_line_2]" value="<?php echo esc_attr( $theme['title_line_2'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="ajth-theme-line3"><?php esc_html_e( 'Grand titre ligne 3', 'ajinsafro-traveler-home' ); ?></label></th>
                        <td><input type="text" id="ajth-theme-line3" name="ajth[holiday_theme][title_line_3]" value="<?php echo esc_attr( $theme['title_line_3'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="ajth-theme-subtitle"><?php esc_html_e( 'Sous-titre', 'ajinsafro-traveler-home' ); ?></label></th>
                        <td><input type="text" id="ajth-theme-subtitle" name="ajth[holiday_theme][subtitle]" value="<?php echo esc_attr( $theme['subtitle'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="ajth-theme-left-image"><?php esc_html_e( 'Image bloc gauche', 'ajinsafro-traveler-home' ); ?></label></th>
                        <td><input type="url" id="ajth-theme-left-image" name="ajth[holiday_theme][left_image_url]" value="<?php echo esc_url( $theme['left_image_url'] ); ?>" class="regular-text" placeholder="https://..."></td>
                    </tr>
                    <tr>
                        <th><label for="ajth-theme-deco-image"><?php esc_html_e( 'Image décorative (optionnel)', 'ajinsafro-traveler-home' ); ?></label></th>
                        <td><input type="url" id="ajth-theme-deco-image" name="ajth[holiday_theme][deco_image_url]" value="<?php echo esc_url( $theme['deco_image_url'] ); ?>" class="regular-text" placeholder="https://..."></td>
                    </tr>
                    <tr>
                        <th><label for="ajth-theme-btn-text"><?php esc_html_e( 'Texte bouton général', 'ajinsafro-traveler-home' ); ?></label></th>
                        <td><input type="text" id="ajth-theme-btn-text" name="ajth[holiday_theme][button_text]" value="<?php echo esc_attr( $theme['button_text'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="ajth-theme-btn-url"><?php esc_html_e( 'Lien bouton général', 'ajinsafro-traveler-home' ); ?></label></th>
                        <td><input type="url" id="ajth-theme-btn-url" name="ajth[holiday_theme][button_url]" value="<?php echo esc_url( $theme['button_url'] ); ?>" class="regular-text" placeholder="https://..."></td>
                    </tr>
                </table>

                <h3><?php esc_html_e( 'Cartes du slider', 'ajinsafro-traveler-home' ); ?></h3>
                <div id="ajth-theme-items-wrap">
                    <?php if ( ! empty( $theme['items'] ) ) : ?>
                        <?php foreach ( $theme['items'] as $i => $item ) : ?>
                            <?php $this->render_holiday_item_row( $i, $item ); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="button" id="ajth-add-theme-item"><?php esc_html_e( '+ Ajouter une carte', 'ajinsafro-traveler-home' ); ?></button>

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
        <script type="text/html" id="tmpl-ajth-theme-item-row">
            <div class="ajth-theme-item-row" data-index="{{data.index}}">
                <h4><?php esc_html_e( 'Carte', 'ajinsafro-traveler-home' ); ?> #<span class="ajth-theme-item-num">{{data.index+1}}</span>
                    <span>
                        <button type="button" class="button button-small ajth-theme-move-up">&#8593;</button>
                        <button type="button" class="button button-small ajth-theme-move-down">&#8595;</button>
                        <button type="button" class="button button-link-delete ajth-remove-theme-item"><?php esc_html_e( 'Supprimer', 'ajinsafro-traveler-home' ); ?></button>
                    </span>
                </h4>
                <p><label><?php esc_html_e( 'Titre', 'ajinsafro-traveler-home' ); ?></label><br><input type="text" class="regular-text" name="ajth[holiday_theme][items][{{data.index}}][title]"></p>
                <p><label><?php esc_html_e( 'Image URL', 'ajinsafro-traveler-home' ); ?></label><br><input type="url" class="regular-text" placeholder="https://..." name="ajth[holiday_theme][items][{{data.index}}][image_url]"></p>
                <p><label><?php esc_html_e( 'Tags (séparés par virgule)', 'ajinsafro-traveler-home' ); ?></label><br><input type="text" class="regular-text" name="ajth[holiday_theme][items][{{data.index}}][tags]"></p>
                <p><label><?php esc_html_e( 'Texte bouton', 'ajinsafro-traveler-home' ); ?></label><br><input type="text" class="regular-text" name="ajth[holiday_theme][items][{{data.index}}][button_text]"></p>
                <p><label><?php esc_html_e( 'Lien bouton', 'ajinsafro-traveler-home' ); ?></label><br><input type="url" class="regular-text" placeholder="https://..." name="ajth[holiday_theme][items][{{data.index}}][button_url]"></p>
                <p><label><?php esc_html_e( 'Ordre', 'ajinsafro-traveler-home' ); ?></label><br><input type="number" value="{{data.index}}" name="ajth[holiday_theme][items][{{data.index}}][order]"></p>
                <p><label><input type="checkbox" value="1" checked name="ajth[holiday_theme][items][{{data.index}}][active]"> <?php esc_html_e( 'Actif', 'ajinsafro-traveler-home' ); ?></label></p>
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

    private function render_holiday_item_row( $index, $item ) {
        ?>
        <div class="ajth-theme-item-row" data-index="<?php echo esc_attr( $index ); ?>">
            <h4><?php esc_html_e( 'Carte', 'ajinsafro-traveler-home' ); ?> #<span class="ajth-theme-item-num"><?php echo intval( $index ) + 1; ?></span>
                <span>
                    <button type="button" class="button button-small ajth-theme-move-up">&#8593;</button>
                    <button type="button" class="button button-small ajth-theme-move-down">&#8595;</button>
                    <button type="button" class="button button-link-delete ajth-remove-theme-item"><?php esc_html_e( 'Supprimer', 'ajinsafro-traveler-home' ); ?></button>
                </span>
            </h4>
            <p><label><?php esc_html_e( 'Titre', 'ajinsafro-traveler-home' ); ?></label><br><input type="text" class="regular-text" name="ajth[holiday_theme][items][<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $item['title'] ?? '' ); ?>"></p>
            <p><label><?php esc_html_e( 'Image URL', 'ajinsafro-traveler-home' ); ?></label><br><input type="url" class="regular-text" placeholder="https://..." name="ajth[holiday_theme][items][<?php echo esc_attr( $index ); ?>][image_url]" value="<?php echo esc_url( $item['image_url'] ?? '' ); ?>"></p>
            <?php $tags_value = isset( $item['tags'] ) && is_array( $item['tags'] ) ? implode( ', ', $item['tags'] ) : (string) ( $item['tags'] ?? '' ); ?>
            <p><label><?php esc_html_e( 'Tags (séparés par virgule)', 'ajinsafro-traveler-home' ); ?></label><br><input type="text" class="regular-text" name="ajth[holiday_theme][items][<?php echo esc_attr( $index ); ?>][tags]" value="<?php echo esc_attr( $tags_value ); ?>"></p>
            <p><label><?php esc_html_e( 'Texte bouton', 'ajinsafro-traveler-home' ); ?></label><br><input type="text" class="regular-text" name="ajth[holiday_theme][items][<?php echo esc_attr( $index ); ?>][button_text]" value="<?php echo esc_attr( $item['button_text'] ?? '' ); ?>"></p>
            <p><label><?php esc_html_e( 'Lien bouton', 'ajinsafro-traveler-home' ); ?></label><br><input type="url" class="regular-text" placeholder="https://..." name="ajth[holiday_theme][items][<?php echo esc_attr( $index ); ?>][button_url]" value="<?php echo esc_url( $item['button_url'] ?? '' ); ?>"></p>
            <p><label><?php esc_html_e( 'Ordre', 'ajinsafro-traveler-home' ); ?></label><br><input type="number" name="ajth[holiday_theme][items][<?php echo esc_attr( $index ); ?>][order]" value="<?php echo esc_attr( intval( $item['order'] ?? $index ) ); ?>"></p>
            <p><label><input type="checkbox" name="ajth[holiday_theme][items][<?php echo esc_attr( $index ); ?>][active]" value="1" <?php checked( ! empty( $item['active'] ) ); ?>> <?php esc_html_e( 'Actif', 'ajinsafro-traveler-home' ); ?></label></p>
        </div>
        <?php
    }
}
