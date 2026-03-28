<?php
/**
 * Part: Voyages par theme (left promo + right slider cards)
 *
 * IMPORTANT: This section should ALWAYS display if there are valid cards,
 * even if images are missing. Cards with missing images show a gradient placeholder.
 *
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$theme = isset( $settings['holiday_theme'] ) ? $settings['holiday_theme'] : array();
if ( is_string( $theme ) ) {
    $decoded_theme = json_decode( $theme, true );
    $theme = is_array( $decoded_theme ) ? $decoded_theme : array();
}
if ( ! is_array( $theme ) ) {
    $theme = array();
}

if ( function_exists( 'ajth_truthy' ) ) {
    $theme_enabled = ajth_truthy( $theme['enabled'] ?? true );
} else {
    $theme_enabled = isset( $theme['enabled'] ) ? ! empty( $theme['enabled'] ) : true;
}
if ( ! $theme_enabled ) {
    return;
}

$items = isset( $theme['items'] ) ? $theme['items'] : array();
if ( ( ! is_array( $items ) || empty( $items ) ) && isset( $theme['cards'] ) && is_array( $theme['cards'] ) ) {
    $items = $theme['cards'];
}
if ( is_string( $items ) ) {
    $decoded_items = json_decode( $items, true );
    $items = is_array( $decoded_items ) ? $decoded_items : array();
}
$items = is_array( $items ) ? $items : array();

$items = array_values( array_filter( $items, function( $it ) {
    if ( ! is_array( $it ) ) {
        return false;
    }
    $title = trim( (string) ( $it['title'] ?? '' ) );
    if ( $title === '' ) {
        return false;
    }
    $active = $it['active'] ?? true;
    if ( function_exists( 'ajth_truthy' ) ) {
        return ajth_truthy( $active );
    }
    if ( is_bool( $active ) ) {
        return $active;
    }
    if ( $active === 1 || $active === '1' || $active === 'true' || $active === 'on' ) {
        return true;
    }
    if ( $active === 0 || $active === '0' || $active === 'false' || $active === 'off' ) {
        return false;
    }
    return true;
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
$left_img = ! empty( $theme['left_image_url'] ) ? $theme['left_image_url'] : ( ! empty( $theme['left_image'] ) ? $theme['left_image'] : '' );
$deco_img = ! empty( $theme['deco_image_url'] ) ? $theme['deco_image_url'] : ( ! empty( $theme['deco_image'] ) ? $theme['deco_image'] : '' );

if ( function_exists( 'ajth_normalize_storage_url' ) ) {
    $left_img = ajth_normalize_storage_url( $left_img );
    $deco_img = ajth_normalize_storage_url( $deco_img );
}
?>

<section class="aj-theme" id="aj-theme">
    <div class="aj-container">
        <div class="aj-theme__grid">
            <aside class="aj-theme__left">
                <?php if ( $left_img ) : ?>
                    <div class="aj-theme__left-media">
                        <img src="<?php echo esc_url( $left_img ); ?>" alt="<?php echo esc_attr( $eyebrow ); ?>" loading="lazy" onerror="this.closest('.aj-theme__left-media').classList.add('is-missing');this.remove();">
                    </div>
                <?php else : ?>
                    <div class="aj-theme__left-media is-missing"></div>
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
                    <img class="aj-theme__deco" src="<?php echo esc_url( $deco_img ); ?>" alt="" loading="lazy" aria-hidden="true" onerror="this.style.display='none';">
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
                    <?php 
                    $placeholder_gradients = array(
                        'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                        'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                        'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                        'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                        'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                        'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
                    );
                    $card_index = 0;
                    foreach ( $items as $item ) :
                        $img = ! empty( $item['image_url'] ) ? $item['image_url'] : ( ! empty( $item['image'] ) ? $item['image'] : '' );
                        if ( function_exists( 'ajth_normalize_storage_url' ) ) {
                            $img = ajth_normalize_storage_url( $img );
                        }
                        $title = ! empty( $item['title'] ) ? $item['title'] : '';
                        $badge = ! empty( $item['badge'] ) ? $item['badge'] : '';
                        $description = ! empty( $item['description'] ) ? $item['description'] : '';
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
                        $fallback_gradient = $placeholder_gradients[ $card_index % count( $placeholder_gradients ) ];
                        $card_index++;
                    ?>
                    <article class="aj-slider-v2__item aj-theme-card">
                        <div class="aj-theme-card__media">
                            <?php if ( $img ) : ?>
                                <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" onerror="this.style.display='none';var p=this.nextElementSibling;if(p){p.classList.remove('aj-theme-card__placeholder--hidden');p.style.background='<?php echo esc_attr( $fallback_gradient ); ?>';}">
                                <div class="aj-theme-card__placeholder aj-theme-card__placeholder--hidden" style="background:<?php echo esc_attr( $fallback_gradient ); ?>;">
                                    <i class="fas fa-image aj-theme-card__placeholder-icon"></i>
                                </div>
                            <?php else : ?>
                                <div class="aj-theme-card__placeholder" style="background:<?php echo esc_attr( $fallback_gradient ); ?>;">
                                    <i class="fas fa-image aj-theme-card__placeholder-icon"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="aj-theme-card__body">
                            <?php if ( $badge !== '' ) : ?>
                                <span class="aj-theme-card__badge"><?php echo esc_html( $badge ); ?></span>
                            <?php endif; ?>
                            <h4 class="aj-theme-card__title"><?php echo esc_html( $title ); ?></h4>
                            <?php if ( $description !== '' ) : ?>
                                <p class="aj-theme-card__desc"><?php echo esc_html( $description ); ?></p>
                            <?php endif; ?>
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
