<?php
/**
 * Part: Destinations de ce mois — Promotional banners
 * 3 colorful gradient promo cards
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! isset( $settings ) || ! is_array( $settings ) ) {
    $settings = array();
}
$promo_settings = isset( $settings['promotions'] ) && is_array( $settings['promotions'] )
    ? $settings['promotions']
    : array();

$section_title = ! empty( $promo_settings['title'] ) ? $promo_settings['title'] : 'Destinations de ce mois';

$default_promos = array(
    array(
        'badge_text'  => 'Profitez',
        'badge_bg'    => '#ef4444',
        'badge_color' => '#fff',
        'title'       => "Cartes de\nfidélités",
        'text'        => "Plus d'espace de voyages pour vous et nos fidèles et plus pour vos nouveaux clients grâce à ce comportement plein d'avantages.",
        'style'       => 'blue',
        'url'         => '#',
        'display_type' => 'css',
        'background_color' => '',
        'background_gradient' => '',
        'image_url' => '',
        'overlay_enabled' => false,
        'overlay_opacity' => 0.35,
        'text_color' => '#ffffff',
        'button_label' => '',
    ),
    array(
        'badge_text'  => 'Profitez',
        'badge_bg'    => '#fff',
        'badge_color' => '#f37a1f',
        'title'       => "Programme\nBztam e-Sfar",
        'text'        => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat.',
        'style'       => 'orange',
        'url'         => '#',
        'display_type' => 'css',
        'background_color' => '',
        'background_gradient' => '',
        'image_url' => '',
        'overlay_enabled' => false,
        'overlay_opacity' => 0.35,
        'text_color' => '#ffffff',
        'button_label' => '',
    ),
    array(
        'badge_text'  => 'احجز الآن',
        'badge_bg'    => '#ffb300',
        'badge_color' => '#0e3a5a',
        'title'       => 'الحجز بكري',
        'text'        => 'تجمع الان الودائع للمسافرين إلى وجهاتك و تمتع بخصم إضافي، فكلما قمت بالحجز مبكرا كلما زاد الخصم الذي ستحصل عليه.',
        'style'       => 'dark-blue',
        'url'         => '#',
        'rtl'         => true,
        'display_type' => 'css',
        'background_color' => '',
        'background_gradient' => '',
        'image_url' => '',
        'overlay_enabled' => false,
        'overlay_opacity' => 0.35,
        'text_color' => '#ffffff',
        'button_label' => '',
    ),
);

$promos = ! empty( $promo_settings['items'] ) && is_array( $promo_settings['items'] )
    ? $promo_settings['items']
    : $default_promos;
if ( empty( $promos ) ) {
    $promos = $default_promos;
}
?>

<section class="aj-promos" id="aj-promos">
    <div class="aj-container">
        <h2 class="aj-section-title"><?php echo esc_html( $section_title ); ?></h2>

        <div class="aj-promos__grid">
            <?php foreach ( $promos as $i => $promo ) :
                $fallback = $default_promos[ $i ] ?? $default_promos[0];
                if ( ! is_array( $promo ) ) {
                    $promo = array();
                }
                $promo = array_merge( $fallback, $promo );

                $allowed_styles = array( 'blue', 'orange', 'dark-blue' );
                $style_raw = isset( $promo['style'] ) ? trim( (string) $promo['style'] ) : '';
                $style = in_array( $style_raw, $allowed_styles, true ) ? $style_raw : (string) ( $fallback['style'] ?? 'blue' );
                if ( ! in_array( $style, $allowed_styles, true ) ) {
                    $style = 'blue';
                }

                $is_rtl = ! empty( $promo['rtl'] );
                $url = ! empty( $promo['url'] ) ? $promo['url'] : '#';
                $display_type = isset( $promo['display_type'] ) && in_array( $promo['display_type'], array( 'css', 'image' ), true )
                    ? $promo['display_type']
                    : 'css';
                $image_url = ! empty( $promo['image_url'] ) ? $promo['image_url'] : '';
                if ( function_exists( 'ajth_normalize_storage_url' ) ) {
                    $image_url = ajth_normalize_storage_url( $image_url );
                }
                $is_image_mode = $display_type === 'image' && $image_url !== '';
                $overlay_enabled = ! empty( $promo['overlay_enabled'] );
                $overlay_opacity = isset( $promo['overlay_opacity'] ) ? (float) $promo['overlay_opacity'] : 0.35;
                $overlay_opacity = max( 0, min( 1, $overlay_opacity ) );
                $text_color = ! empty( $promo['text_color'] ) ? $promo['text_color'] : '#ffffff';
                $bg_color = isset( $promo['background_color'] ) ? trim( (string) $promo['background_color'] ) : '';
                $bg_gradient = isset( $promo['background_gradient'] ) ? trim( (string) $promo['background_gradient'] ) : '';

                $style_attr = '';
                if ( $is_image_mode ) {
                    $overlay_alpha = $overlay_enabled ? $overlay_opacity : 0;
                    $style_attr = sprintf(
                        'background-image:linear-gradient(rgba(0,0,0,%.2F),rgba(0,0,0,%.2F)),url(%s);background-size:cover;background-position:center;color:%s;',
                        $overlay_alpha,
                        $overlay_alpha,
                        esc_url( $image_url ),
                        esc_attr( $text_color )
                    );
                } else {
                    $style_parts = array();
                    if ( $bg_gradient !== '' ) {
                        $style_parts[] = 'background:' . esc_attr( $bg_gradient );
                    } elseif ( $bg_color !== '' ) {
                        $style_parts[] = 'background:' . esc_attr( $bg_color );
                    }
                    $style_parts[] = 'color:' . esc_attr( $text_color );
                    $style_attr = implode( ';', $style_parts ) . ';';
                }
            ?>
            <a href="<?php echo esc_url( $url ); ?>" class="aj-promo-card aj-promo-card--<?php echo esc_attr( $style ); ?><?php echo $is_rtl ? ' aj-promo-card--rtl' : ''; ?><?php echo $is_image_mode ? ' aj-promo-card--image' : ''; ?>" style="<?php echo esc_attr( $style_attr ); ?>"<?php echo $is_rtl ? ' dir="rtl" lang="ar"' : ''; ?>>
                <?php if ( ! empty( $promo['badge_text'] ) ) : ?>
                <span class="aj-promo-card__badge" style="background:<?php echo esc_attr( $promo['badge_bg'] ?? '#ef4444' ); ?>;color:<?php echo esc_attr( $promo['badge_color'] ?? '#fff' ); ?>;">
                    <?php echo esc_html( $promo['badge_text'] ); ?>
                </span>
                <?php endif; ?>
                <h3 class="aj-promo-card__title"><?php echo nl2br( esc_html( $promo['title'] ?? '' ) ); ?></h3>
                <?php if ( ! empty( $promo['text'] ) ) : ?>
                <p class="aj-promo-card__text"><?php echo esc_html( $promo['text'] ); ?></p>
                <?php endif; ?>
                <?php if ( ! empty( $promo['button_label'] ) ) : ?>
                <span class="aj-promo-card__button"><?php echo esc_html( $promo['button_label'] ); ?></span>
                <?php endif; ?>

                <?php if ( ! $is_image_mode && $i === 0 ) : ?>
                <div class="aj-promo-card__deco aj-promo-card__deco--cards">
                    <div class="aj-deco-card aj-deco-card--green">25%</div>
                    <div class="aj-deco-card aj-deco-card--orange">50%</div>
                    <div class="aj-deco-card aj-deco-card--blue">75%</div>
                </div>
                <?php elseif ( ! $is_image_mode && $i === 1 ) : ?>
                <div class="aj-promo-card__deco aj-promo-card__deco--wallet">
                    <i class="fas fa-wallet"></i>
                </div>
                <?php elseif ( ! $is_image_mode && $i === 2 ) : ?>
                <div class="aj-promo-card__deco aj-promo-card__deco--box">
                    <i class="fas fa-box-open"></i>
                </div>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
