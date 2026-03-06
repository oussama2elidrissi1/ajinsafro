<?php
/**
 * Part: WhatsApp Banner
 * Join our WhatsApp channel banner with QR code
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! isset( $settings ) || ! is_array( $settings ) ) {
    $settings = array();
}
$whatsapp = isset( $settings['whatsapp_banner'] ) && is_array( $settings['whatsapp_banner'] )
    ? $settings['whatsapp_banner']
    : array();

if ( empty( $whatsapp['enabled'] ) ) {
    return;
}

$title = ! empty( $whatsapp['title'] ) ? $whatsapp['title'] : 'JOIN OUR WHATSAPP CHANNEL FOR THE LATEST TRAVEL UPDATES';
$subtitle = ! empty( $whatsapp['subtitle'] ) ? $whatsapp['subtitle'] : 'Stay informed with satguru travel';
$features = ! empty( $whatsapp['features'] ) && is_array( $whatsapp['features'] ) ? $whatsapp['features'] : array(
    'Exclusive travel packages',
    'Latest news and updates',
    'Special offers and promotions'
);
$button_text = ! empty( $whatsapp['button_text'] ) ? $whatsapp['button_text'] : 'JOIN NOW';
$button_url = ! empty( $whatsapp['button_url'] ) ? $whatsapp['button_url'] : '#';
$qr_code_url = ! empty( $whatsapp['qr_code_url'] ) ? $whatsapp['qr_code_url'] : '';
?>

<section class="aj-whatsapp-banner">
    <div class="aj-container">
        <div class="aj-whatsapp-banner__inner">
            <div class="aj-whatsapp-banner__content">
                <h2 class="aj-whatsapp-banner__title"><?php echo esc_html( $title ); ?></h2>
                <p class="aj-whatsapp-banner__subtitle"><?php echo esc_html( $subtitle ); ?></p>
                <?php if ( ! empty( $features ) ) : ?>
                <ul class="aj-whatsapp-banner__features">
                    <?php foreach ( $features as $feature ) : ?>
                    <li><i class="fas fa-chevron-right"></i> <?php echo esc_html( $feature ); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                <a href="<?php echo esc_url( $button_url ); ?>" class="aj-whatsapp-banner__button" target="_blank" rel="noopener">
                    <?php echo esc_html( $button_text ); ?>
                </a>
            </div>
            <?php if ( $qr_code_url ) : ?>
            <div class="aj-whatsapp-banner__qr">
                <img src="<?php echo esc_url( $qr_code_url ); ?>" alt="WhatsApp QR Code" class="aj-whatsapp-banner__qr-img">
                <p class="aj-whatsapp-banner__qr-text">SCAN NOW</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
