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

/*
 * Les logos etaient charges depuis upload.wikimedia.org : quatre des cinq
 * adresses ne repondent plus (400 sur les largeurs de vignette non
 * standard, 404 sur le chemin Western Union) et le navigateur n'affichait
 * plus que le texte alternatif. Les fichiers sont desormais servis par le
 * plugin. Un moyen de paiement sans fichier tombe sur une pastille texte.
 */
$payment_images = array(
    array( 'name' => 'Mastercard', 'file' => 'assets/img/payments/mastercard.svg', 'h' => '24px' ),
    array( 'name' => 'Visa', 'file' => 'assets/img/payments/visa.svg', 'h' => '20px' ),
    array( 'name' => 'PayPal', 'file' => 'assets/img/payments/paypal.svg', 'h' => '20px' ),
    array( 'name' => 'Western Union', 'file' => 'assets/img/payments/western-union.svg', 'h' => '20px' ),
    array( 'name' => 'Wafacash', 'file' => '', 'h' => '16px' ),
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
                <?php $pm_exists = $pm['file'] !== '' && file_exists( AJTH_DIR . $pm['file'] ); ?>
                <?php if ( $pm_exists ) : ?>
                    <?php // Filet de securite si le fichier disparait d'un deploiement a l'autre. ?>
                    <?php $pm_fallback = '<span class="aj-payments-v2__fallback">' . esc_html( $pm['name'] ) . '</span>'; ?>
                    <img src="<?php echo esc_url( AJTH_URL . $pm['file'] ); ?>"
                         alt="<?php echo esc_attr( $pm['name'] ); ?>"
                         style="height:<?php echo esc_attr( $pm['h'] ); ?>;"
                         loading="lazy"
                         onerror="this.onerror=null;this.insertAdjacentHTML('afterend', <?php echo esc_attr( wp_json_encode( $pm_fallback ) ); ?>);this.remove();">
                <?php else : ?>
                    <span class="aj-payments-v2__fallback"><?php echo esc_html( $pm['name'] ); ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
            <span class="aj-payments-v2__text-badge">CASH PLUS</span>
        </div>
    </div>

    <div style="height:128px;"></div>
</footer>
