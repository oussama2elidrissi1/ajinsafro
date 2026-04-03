<?php
/**
 * Accordion Slider Template
 * Shortcode: [ajinsafro_slider]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$slides = array(
    array(
        'title' => 'PROGRAMME DE FIDELITE',
        'image' => AJINSAFRO_HOME_URL . 'assets/img/slide-1.jpg',
        'cta_text' => "S'inscrire !",
        'cta_url' => 'https://www.ajinsafro.ma/fidelite',
        'cta_target' => '_top',
        'theme' => 'theme-0',
        'overlay' => 'overlay-0',
        'lang' => 'fr',
    ),
    array(
        'title' => 'GROUP DEALS TRAVEL',
        'image' => AJINSAFRO_HOME_URL . 'assets/img/slide-2.jpg',
        'cta_text' => '',
        'cta_url' => '',
        'cta_target' => '_self',
        'theme' => 'theme-1',
        'overlay' => 'overlay-1',
        'lang' => 'fr',
    ),
    array(
        'title' => "L'7AJZ BKRI B'DHAB MCHRI",
        'image' => AJINSAFRO_HOME_URL . 'assets/img/slide-3.jpg',
        'cta_text' => 'احجز الآن',
        'cta_url' => 'https://www.ajinsafro.ma/voyages',
        'cta_target' => '_top',
        'theme' => 'theme-2',
        'overlay' => 'overlay-2',
        'lang' => 'ar',
    ),
    array(
        'title' => 'PROGRAMME BZTAM eSFAR',
        'image' => AJINSAFRO_HOME_URL . 'assets/img/slide-4.jpg',
        'cta_text' => '',
        'cta_url' => '',
        'cta_target' => '_self',
        'theme' => 'theme-3',
        'overlay' => 'overlay-3',
        'lang' => 'fr',
    ),
    array(
        'title' => 'IMPORTANT UPDATES',
        'image' => 'https://i.ibb.co/4n5PwWsV/vecteezy-islamic-geometric-pattern-art-illustration-vector-14002298.jpg',
        'cta_text' => '',
        'cta_url' => '',
        'cta_target' => '_self',
        'theme' => 'theme-4',
        'overlay' => 'overlay-4',
        'lang' => 'fr',
    ),
);

$slides = apply_filters( 'ajinsafro_slider_slides', $slides );

if ( empty( $slides ) || ! is_array( $slides ) ) {
    return;
}
?>
<div style="width:100%; padding:0; margin:0;">
<section class="aji-accordion-slider" id="aji-accordion-slider" role="region" aria-label="Promotions AjiNsafro">
    <?php foreach ( array_values( $slides ) as $index => $slide ) :
        $title = isset( $slide['title'] ) ? (string) $slide['title'] : '';
        $image = isset( $slide['image'] ) ? (string) $slide['image'] : '';
        $cta_text = isset( $slide['cta_text'] ) ? (string) $slide['cta_text'] : '';
        $cta_url = isset( $slide['cta_url'] ) ? (string) $slide['cta_url'] : '';
        $cta_target = isset( $slide['cta_target'] ) ? (string) $slide['cta_target'] : '_self';
        $theme = isset( $slide['theme'] ) ? (string) $slide['theme'] : 'theme-0';
        $overlay = isset( $slide['overlay'] ) ? (string) $slide['overlay'] : 'overlay-0';
        $lang = isset( $slide['lang'] ) ? (string) $slide['lang'] : 'fr';
    ?>
    <article class="aji-slide" data-index="<?php echo esc_attr( $index ); ?>">
        <button class="aji-tab-bar <?php echo esc_attr( $theme ); ?>" type="button" aria-label="<?php echo esc_attr( $title ); ?>" aria-pressed="false">
            <span class="aji-tab-label"><?php echo esc_html( $title ); ?></span>
        </button>

        <div class="aji-slide-content" role="group" aria-label="<?php echo esc_attr( $title ); ?>">
            <?php if ( '' !== $image ) : ?>
                <img class="aji-image" src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="<?php echo 0 === $index ? 'eager' : 'lazy'; ?>" <?php echo 0 === $index ? 'fetchpriority="high"' : ''; ?>>
            <?php endif; ?>

            <div class="aji-overlay <?php echo esc_attr( $overlay ); ?>"></div>

            <?php if ( '' !== $cta_text && '' !== $cta_url ) : ?>
                <div class="aji-cta-wrap">
                    <a class="aji-cta<?php echo 'ar' === $lang ? ' aji-cta-ar' : ' aji-cta-orange'; ?>" href="<?php echo esc_url( $cta_url ); ?>" target="<?php echo esc_attr( $cta_target ); ?>" rel="noopener noreferrer">
                        <?php echo esc_html( $cta_text ); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </article>
    <?php endforeach; ?>
</section>
</div>
