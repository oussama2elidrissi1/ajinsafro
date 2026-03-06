<?php
/**
 * Part: Cruises Section
 * Croisières banner with image
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! isset( $settings ) || ! is_array( $settings ) ) {
    $settings = array();
}
$cruises = isset( $settings['cruises'] ) && is_array( $settings['cruises'] )
    ? $settings['cruises']
    : array();

if ( empty( $cruises['enabled'] ) ) {
    return;
}

$title = ! empty( $cruises['title'] ) ? $cruises['title'] : 'CROISIÈRES';
$image_url = ! empty( $cruises['image_url'] ) ? $cruises['image_url'] : '';
$button_text = ! empty( $cruises['button_text'] ) ? $cruises['button_text'] : 'Découvrir';
$button_url = ! empty( $cruises['button_url'] ) ? $cruises['button_url'] : '#';
?>

<section class="aj-cruises">
    <div class="aj-container">
        <div class="aj-cruises__inner">
            <?php if ( $image_url ) : ?>
            <div class="aj-cruises__image">
                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>">
            </div>
            <?php endif; ?>
            <div class="aj-cruises__content">
                <h2 class="aj-cruises__title"><?php echo esc_html( $title ); ?></h2>
                <a href="<?php echo esc_url( $button_url ); ?>" class="aj-cruises__button">
                    <?php echo esc_html( $button_text ); ?>
                </a>
            </div>
        </div>
    </div>
</section>
