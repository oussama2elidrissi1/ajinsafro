<?php
/**
 * Part: Voyages par theme (left promo + right slider cards)
 *
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$theme = isset( $settings['holiday_theme'] ) && is_array( $settings['holiday_theme'] )
    ? $settings['holiday_theme']
    : array();

if ( empty( $theme['enabled'] ) ) {
    return;
}

$items = isset( $theme['items'] ) && is_array( $theme['items'] ) ? $theme['items'] : array();
$items = array_values( array_filter( $items, function( $it ) {
    return ! empty( $it['active'] ) && ! empty( $it['title'] );
} ) );

if ( empty( $items ) ) {
    return;
}

usort( $items, function( $a, $b ) {
    $ao = isset( $a['order'] ) ? intval( $a['order'] ) : 999;
    $bo = isset( $b['order'] ) ? intval( $b['order'] ) : 999;
    return $ao <=> $bo;
} );

$eyebrow = ! empty( $theme['eyebrow'] ) ? $theme['eyebrow'] : 'Voyages par theme';
$subtitle = ! empty( $theme['subtitle'] ) ? $theme['subtitle'] : '';
$button_text = ! empty( $theme['button_text'] ) ? $theme['button_text'] : '';
$button_url = ! empty( $theme['button_url'] ) ? $theme['button_url'] : '';
$left_img = ! empty( $theme['left_image_url'] ) ? $theme['left_image_url'] : '';
$deco_img = ! empty( $theme['deco_image_url'] ) ? $theme['deco_image_url'] : '';
?>

<section class="aj-theme" id="aj-theme">
    <div class="aj-container">
        <div class="aj-theme__grid">
            <aside class="aj-theme__left">
                <?php if ( $left_img ) : ?>
                    <div class="aj-theme__left-media">
                        <img src="<?php echo esc_url( $left_img ); ?>" alt="<?php echo esc_attr( $eyebrow ); ?>" loading="lazy">
                    </div>
                <?php endif; ?>
                <div class="aj-theme__left-content">
                    <p class="aj-theme__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
                    <h2 class="aj-theme__title">
                        <?php if ( ! empty( $theme['title_line_1'] ) ) : ?><span><?php echo esc_html( $theme['title_line_1'] ); ?></span><?php endif; ?>
                        <?php if ( ! empty( $theme['title_line_2'] ) ) : ?><span><?php echo esc_html( $theme['title_line_2'] ); ?></span><?php endif; ?>
                        <?php if ( ! empty( $theme['title_line_3'] ) ) : ?><span><?php echo esc_html( $theme['title_line_3'] ); ?></span><?php endif; ?>
                    </h2>
                    <?php if ( $subtitle !== '' ) : ?>
                        <p class="aj-theme__subtitle"><?php echo esc_html( $subtitle ); ?></p>
                    <?php endif; ?>
                    <?php if ( $button_text !== '' && $button_url !== '' ) : ?>
                        <a class="aj-theme__cta" href="<?php echo esc_url( $button_url ); ?>">
                            <?php echo esc_html( $button_text ); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php if ( $deco_img ) : ?>
                    <img class="aj-theme__deco" src="<?php echo esc_url( $deco_img ); ?>" alt="" loading="lazy" aria-hidden="true">
                <?php endif; ?>
            </aside>

            <div class="aj-theme__right">
                <div class="aj-section-head">
                    <h3 class="aj-section-title"><?php echo esc_html__( 'Holidays by Theme', 'ajinsafro-traveler-home' ); ?></h3>
                    <div class="aj-section-arrows">
                        <button type="button" class="aj-section-arrow aj-theme-prev" aria-label="<?php esc_attr_e( 'Precedent', 'ajinsafro-traveler-home' ); ?>"><i class="fas fa-angle-left"></i></button>
                        <button type="button" class="aj-section-arrow aj-theme-next" aria-label="<?php esc_attr_e( 'Suivant', 'ajinsafro-traveler-home' ); ?>"><i class="fas fa-angle-right"></i></button>
                    </div>
                </div>

                <div class="aj-slider-v2 aj-theme-track" id="aj-theme-track">
                    <?php foreach ( $items as $item ) :
                        $img = ! empty( $item['image_url'] ) ? $item['image_url'] : '';
                        $title = ! empty( $item['title'] ) ? $item['title'] : '';
                        $btn_text = ! empty( $item['button_text'] ) ? $item['button_text'] : esc_html__( 'Voir plus', 'ajinsafro-traveler-home' );
                        $btn_url = ! empty( $item['button_url'] ) ? $item['button_url'] : '#';
                        $raw_tags = $item['tags'] ?? array();
                        if ( is_array( $raw_tags ) ) {
                            $tags = array_values( array_filter( array_map( function ( $tag ) {
                                return trim( (string) $tag );
                            }, $raw_tags ) ) );
                        } else {
                            $raw_tags = ! empty( $raw_tags ) ? (string) $raw_tags : '';
                            $tags = preg_split( '/[\r\n,]+/', $raw_tags );
                            $tags = array_values( array_filter( array_map( 'trim', (array) $tags ) ) );
                        }
                    ?>
                    <article class="aj-slider-v2__item aj-theme-card">
                        <div class="aj-theme-card__media">
                            <?php if ( $img ) : ?>
                                <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
                            <?php else : ?>
                                <div class="aj-theme-card__placeholder"></div>
                            <?php endif; ?>
                        </div>
                        <div class="aj-theme-card__body">
                            <h4 class="aj-theme-card__title"><?php echo esc_html( $title ); ?></h4>
                            <?php if ( ! empty( $tags ) ) : ?>
                                <div class="aj-theme-card__tags">
                                    <?php foreach ( array_slice( $tags, 0, 4 ) as $tag ) : ?>
                                        <span class="aj-theme-card__tag"><?php echo esc_html( $tag ); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <a class="aj-theme-card__btn" href="<?php echo esc_url( $btn_url ); ?>">
                                <?php echo esc_html( $btn_text ); ?>
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
