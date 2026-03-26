<?php
/**
 * Template: Maintenance page (/maintenance)
 *
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$hdr = function_exists( 'ajth_get_header_settings' ) ? ajth_get_header_settings() : array();
$logo_url = ! empty( $hdr['logo_url'] ) ? (string) $hdr['logo_url'] : '';
$brand_name = get_bloginfo( 'name' );

get_header();
?>

<main class="aj-maintenance" style="min-height:70vh;display:flex;align-items:center;justify-content:center;padding:48px 16px;background:linear-gradient(180deg,#f7fbff 0%,#eef6ff 100%);">
    <section style="max-width:760px;width:100%;background:#fff;border:1px solid #e5eef7;border-radius:20px;box-shadow:0 12px 32px rgba(14,58,90,.08);padding:36px 28px;text-align:center;">
        <?php if ( $logo_url ) : ?>
            <div style="margin-bottom:18px;">
                <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $brand_name ); ?>" style="max-height:52px;width:auto;">
            </div>
        <?php endif; ?>

        <h1 style="margin:0 0 10px;font-size:30px;line-height:1.25;color:#0e3a5a;font-weight:800;">
            <?php esc_html_e( 'Page en cours de construction', 'ajinsafro-traveler-home' ); ?>
        </h1>
        <p style="margin:0 0 8px;color:#3f4d5e;font-size:16px;">
            <?php esc_html_e( 'Cette rubrique sera bientot disponible.', 'ajinsafro-traveler-home' ); ?>
        </p>
        <p style="margin:0 0 26px;color:#5a6b7f;font-size:15px;">
            <?php esc_html_e( 'Nous preparons cette section pour vous offrir une meilleure experience.', 'ajinsafro-traveler-home' ); ?>
        </p>

        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="display:inline-flex;align-items:center;justify-content:center;padding:12px 22px;border-radius:999px;background:#0083c4;color:#fff;text-decoration:none;font-weight:700;">
            <?php esc_html_e( "Retour a l'accueil", 'ajinsafro-traveler-home' ); ?>
        </a>
    </section>
</main>

<?php
get_footer();
