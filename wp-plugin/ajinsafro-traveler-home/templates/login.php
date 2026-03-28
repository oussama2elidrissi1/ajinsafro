<?php
/**
 * Template: Unified login page (/login)
 *
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$endpoint = function_exists( 'ajth_public_login_endpoint' )
    ? ajth_public_login_endpoint()
    : 'https://booking.ajinsafro.net/auth/public-login';
$login_prefill = isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : '';
$show_error = isset( $_GET['login_error'] ) && $_GET['login_error'] === '1';
$show_expired = isset( $_GET['session_expired'] ) && $_GET['session_expired'] === '1';

get_header();
?>
<main class="aj-login-page" style="min-height:70vh;display:flex;align-items:center;justify-content:center;padding:40px 16px;background:linear-gradient(180deg,#f7fbff 0%,#eef6ff 100%);">
    <section style="width:100%;max-width:420px;background:#fff;border:1px solid #e5edf6;border-radius:14px;padding:24px;box-shadow:0 12px 34px rgba(15,33,55,.08);">
        <h1 style="margin:0 0 6px;color:#0e3a5a;font-size:1.35rem;font-weight:700;"><?php esc_html_e( 'Connexion', 'ajinsafro-traveler-home' ); ?></h1>
        <p style="margin:0 0 18px;color:#6b7280;font-size:.92rem;"><?php esc_html_e( 'Connectez-vous avec votre email ou votre identifiant.', 'ajinsafro-traveler-home' ); ?></p>

        <?php if ( $show_error ) : ?>
            <div role="alert" style="margin:0 0 14px;padding:10px 12px;border:1px solid rgba(220,38,38,.25);background:rgba(220,38,38,.08);color:#991b1b;border-radius:10px;font-size:.85rem;font-weight:600;">
                <?php esc_html_e( 'Identifiants invalides. Veuillez verifier vos informations.', 'ajinsafro-traveler-home' ); ?>
            </div>
        <?php endif; ?>

        <?php if ( $show_expired ) : ?>
            <div role="alert" style="margin:0 0 14px;padding:10px 12px;border:1px solid rgba(245,158,11,.28);background:rgba(245,158,11,.12);color:#92400e;border-radius:10px;font-size:.85rem;font-weight:600;">
                <?php esc_html_e( 'Votre session a expire. Veuillez vous reconnecter.', 'ajinsafro-traveler-home' ); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( $endpoint ); ?>" novalidate>
            <label style="display:block;margin-bottom:6px;color:#334155;font-size:.85rem;font-weight:600;"><?php esc_html_e( 'Email ou identifiant', 'ajinsafro-traveler-home' ); ?></label>
            <input type="text" name="login" value="<?php echo esc_attr( $login_prefill ); ?>" required style="width:100%;height:44px;border:1px solid #dbe4ef;border-radius:10px;padding:0 12px;font-size:.92rem;margin-bottom:12px;">

            <label style="display:block;margin-bottom:6px;color:#334155;font-size:.85rem;font-weight:600;"><?php esc_html_e( 'Mot de passe', 'ajinsafro-traveler-home' ); ?></label>
            <input type="password" name="password" required style="width:100%;height:44px;border:1px solid #dbe4ef;border-radius:10px;padding:0 12px;font-size:.92rem;margin-bottom:12px;">

            <label style="display:flex;align-items:center;gap:8px;margin-bottom:14px;color:#475569;font-size:.84rem;">
                <input type="checkbox" name="remember" value="1">
                <?php esc_html_e( 'Rester connecte', 'ajinsafro-traveler-home' ); ?>
            </label>

            <button type="submit" style="width:100%;height:44px;border:none;border-radius:10px;background:#0083c4;color:#fff;font-weight:700;font-size:.92rem;cursor:pointer;">
                <?php esc_html_e( 'Se connecter', 'ajinsafro-traveler-home' ); ?>
            </button>
        </form>
    </section>
</main>
<?php get_footer(); ?>

