<?php
/**
 * Part: Footer — Link columns + newsletter + payment methods
 * Design matches the AjinSafro mockup with background decoration
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$footer_settings = isset( $settings['footer'] ) && is_array( $settings['footer'] )
    ? $settings['footer']
    : array();

$footer_cols = array(
    array(
        'heading' => ! empty( $footer_settings['col1_heading'] ) ? $footer_settings['col1_heading'] : __( 'En savoir plus', 'ajinsafro-traveler-home' ),
        'links' => array(
            array( 'label' => __( 'À propos', 'ajinsafro-traveler-home' ), 'url' => '#' ),
            array( 'label' => __( 'FAQ', 'ajinsafro-traveler-home' ), 'url' => '#' ),
            array( 'label' => __( "Conditions d'utilisation", 'ajinsafro-traveler-home' ), 'url' => '#' ),
            array( 'label' => __( 'Blog', 'ajinsafro-traveler-home' ), 'url' => '#' ),
        ),
    ),
    array(
        'heading' => ! empty( $footer_settings['col2_heading'] ) ? $footer_settings['col2_heading'] : __( 'Société', 'ajinsafro-traveler-home' ),
        'links' => array(
            array( 'label' => __( 'Emplois', 'ajinsafro-traveler-home' ), 'url' => '#' ),
            array( 'label' => __( 'Forum', 'ajinsafro-traveler-home' ), 'url' => '#' ),
            array( 'label' => __( 'Devenez-Partenaire', 'ajinsafro-traveler-home' ), 'url' => '#' ),
            array( 'label' => __( 'Laissez-nous un message', 'ajinsafro-traveler-home' ), 'url' => '#' ),
            array( 'label' => __( 'Contact', 'ajinsafro-traveler-home' ), 'url' => '#' ),
        ),
    ),
);

$legal_lines = ! empty( $footer_settings['legal_text'] )
    ? $footer_settings['legal_text']
    : "Licence N° 489117 | RC: 18989\nPatente: 50411316 | I.C.E: 001585417000035\nAjinSafro Recreation SARL AU";

$payment_images = array(
    array( 'name' => 'Mastercard', 'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Mastercard-logo.svg/1280px-Mastercard-logo.svg.png', 'h' => '24px' ),
    array( 'name' => 'Visa', 'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/2560px-Visa_Inc._logo.svg.png', 'h' => '20px' ),
    array( 'name' => 'PayPal', 'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b5/PayPal.svg/2560px-PayPal.svg.png', 'h' => '20px' ),
    array( 'name' => 'Western Union', 'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/ba/Western_Union_logo.svg/1280px-Western_Union_logo.svg.png', 'h' => '20px' ),
    array( 'name' => 'Wafacash', 'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1b/Wafacash_logo.svg/2560px-Wafacash_logo.svg.png', 'h' => '16px' ),
);
?>

<footer class="aj-footer-v2">
    <div class="aj-container">
        <div class="aj-footer-v2__cols" style="position:relative;z-index:10;">
            <?php foreach ( $footer_cols as $col ) : ?>
            <div>
                <h4 class="aj-footer-v2__heading"><?php echo esc_html( $col['heading'] ); ?></h4>
                <ul class="aj-footer-v2__list">
                    <?php foreach ( $col['links'] as $link ) : ?>
                    <li><a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>

            <!-- Legal -->
            <div>
                <h4 class="aj-footer-v2__heading"><?php esc_html_e( 'Mentions Légales', 'ajinsafro-traveler-home' ); ?></h4>
                <div class="aj-footer-v2__legal">
                    <?php foreach ( explode( "\n", $legal_lines ) as $line ) : ?>
                        <p style="margin:0 0 8px;"><?php echo esc_html( trim( $line ) ); ?></p>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Newsletter -->
            <div>
                <div class="aj-footer-v2__nl-header">
                    <i class="far fa-envelope"></i>
                    <div>
                        <h4 class="aj-footer-v2__nl-title"><?php esc_html_e( 'Recevez en avant-première :', 'ajinsafro-traveler-home' ); ?></h4>
                        <p class="aj-footer-v2__nl-desc"><?php esc_html_e( 'Réductions, codes promo, offres exclusives ...', 'ajinsafro-traveler-home' ); ?></p>
                    </div>
                </div>
                <form class="aj-footer-v2__nl-form" method="post" action="#">
                    <input type="email" name="ajth_nl_email" placeholder="<?php esc_attr_e( 'Saisissez votre email', 'ajinsafro-traveler-home' ); ?>" required>
                    <button type="submit"><?php esc_html_e( "S'INSCRIRE", 'ajinsafro-traveler-home' ); ?></button>
                </form>
            </div>
        </div>
    </div>

    <!-- Payment Methods -->
    <div class="aj-payments-v2">
        <p class="aj-payments-v2__label"><?php esc_html_e( 'Moyens de paiement acceptés', 'ajinsafro-traveler-home' ); ?></p>
        <div class="aj-payments-v2__icons">
            <?php foreach ( $payment_images as $pm ) : ?>
                <img src="<?php echo esc_url( $pm['url'] ); ?>" alt="<?php echo esc_attr( $pm['name'] ); ?>" style="height:<?php echo esc_attr( $pm['h'] ); ?>;" loading="lazy">
            <?php endforeach; ?>
            <span class="aj-payments-v2__text-badge">CASH PLUS</span>
        </div>
    </div>

    <div style="height:128px;"></div>
</footer>
