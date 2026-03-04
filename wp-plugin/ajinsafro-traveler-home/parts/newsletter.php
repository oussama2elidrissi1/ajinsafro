<?php
/**
 * Part: Footer newsletter + link columns + payment badges
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$footer_cols = array(
    array(
        'heading' => __( 'En savoir plus', 'ajinsafro-traveler-home' ),
        'links'   => array(
            array( 'label' => __( 'À propos', 'ajinsafro-traveler-home' ),              'url' => '#' ),
            array( 'label' => __( 'FAQ', 'ajinsafro-traveler-home' ),                   'url' => '#' ),
            array( 'label' => __( "Conditions d'utilisation", 'ajinsafro-traveler-home' ), 'url' => '#' ),
            array( 'label' => __( 'Blog', 'ajinsafro-traveler-home' ),                  'url' => '#' ),
        ),
    ),
    array(
        'heading' => __( 'Société', 'ajinsafro-traveler-home' ),
        'links'   => array(
            array( 'label' => __( 'Emplois', 'ajinsafro-traveler-home' ),                'url' => '#' ),
            array( 'label' => __( 'Presse', 'ajinsafro-traveler-home' ),                 'url' => '#' ),
            array( 'label' => __( 'Documents Financiers', 'ajinsafro-traveler-home' ),   'url' => '#' ),
            array( 'label' => __( 'Laisser nous un Message', 'ajinsafro-traveler-home' ),'url' => '#' ),
        ),
    ),
    array(
        'heading' => __( 'Mentions Légales', 'ajinsafro-traveler-home' ),
        'links'   => array(
            array( 'label' => __( "Licence des voyages et le moitié", 'ajinsafro-traveler-home' ), 'url' => '#' ),
            array( 'label' => __( "Adhésion est securité par l'agence de voyage professionnelle", 'ajinsafro-traveler-home' ), 'url' => '#' ),
            array( 'label' => __( 'Licencié N°800FR', 'ajinsafro-traveler-home' ),       'url' => '#' ),
        ),
    ),
);

$payment_icons = array( 'Visa', 'Western Union', 'Mastercard', 'Discover', 'PayPal' );
?>

<section class="aj-footer-nl">
    <div class="aj-container">
        <div class="aj-footer-nl__grid">

            <?php foreach ( $footer_cols as $col ) : ?>
            <div class="aj-fnl-col">
                <h4 class="aj-fnl-col__h"><?php echo esc_html( $col['heading'] ); ?></h4>
                <ul class="aj-fnl-col__list">
                    <?php foreach ( $col['links'] as $link ) : ?>
                    <li><a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>

            <div class="aj-fnl-col aj-fnl-col--nl">
                <div class="aj-nl-box">
                    <div class="aj-nl-box__ico">
                        <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#1a73a7" stroke-width="1.5"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="22,4 12,13 2,4"/></svg>
                    </div>
                    <h4 class="aj-nl-box__h"><?php esc_html_e( 'Recevez en avant-première :', 'ajinsafro-traveler-home' ); ?></h4>
                    <p class="aj-nl-box__p"><?php esc_html_e( 'Nos nouveautés, nos voyages, des codes promos, des offres exclusives.', 'ajinsafro-traveler-home' ); ?></p>
                    <form class="aj-nl-box__form" method="post" action="#">
                        <input type="email" name="ajth_nl_email" placeholder="<?php esc_attr_e( 'Saisissez votre e-mail', 'ajinsafro-traveler-home' ); ?>" required>
                        <button type="submit"><?php esc_html_e( "S'INSCRIRE", 'ajinsafro-traveler-home' ); ?></button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</section>

<section class="aj-payments">
    <div class="aj-container">
        <p class="aj-payments__txt"><?php esc_html_e( 'Vous pouvez payer votre prochain voyage par différentes méthodes de paiement', 'ajinsafro-traveler-home' ); ?></p>
        <div class="aj-payments__icons">
            <?php foreach ( $payment_icons as $icon ) : ?>
                <span class="aj-pay-badge"><?php echo esc_html( $icon ); ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</section>
