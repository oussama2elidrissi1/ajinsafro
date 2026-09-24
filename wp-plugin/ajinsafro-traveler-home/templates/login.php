<?php
/**
 * Template: Unified login page (/login)
 *
 * Le formulaire poste vers l'application Laravel (auth/public-login), qui
 * renvoie ici avec ?login_error=1 ou ?session_expired=1. Ce contrat est
 * inchange : cette page n'est qu'une presentation.
 *
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'body_class',
	static function ( $classes ) {
		$classes[] = 'page-connexion-ajinsafro';

		return $classes;
	}
);

$endpoint = function_exists( 'ajth_public_login_endpoint' )
	? ajth_public_login_endpoint()
	: ajth_booking_base_url() . '/auth/public-login';

$header_settings = function_exists( 'ajth_get_header_settings' ) ? ajth_get_header_settings() : array();
$signup_url = ! empty( $header_settings['signup_url'] ) ? (string) $header_settings['signup_url'] : home_url( '/register/' );
$forgot_url = wp_lostpassword_url();

/**
 * Portail partenaire : hote dedie, deduit de l'endpoint d'authentification.
 * Filtrable pour les installations qui l'hebergent ailleurs.
 */
$partner_url = apply_filters(
	'ajth_partner_portal_url',
	( ( $origin = wp_parse_url( $endpoint ) ) && ! empty( $origin['host'] )
		? ( $origin['scheme'] ?? 'https' ) . '://' . $origin['host'] . '/login'
		: '' )
);

$login_prefill = isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : '';
$show_error    = isset( $_GET['login_error'] ) && '1' === $_GET['login_error'];
$show_expired  = isset( $_GET['session_expired'] ) && '1' === $_GET['session_expired'];

$perks = array(
	array( 'Vos réservations', 'Statut, dates et voyageurs de chaque dossier, à jour en temps réel.' ),
	array( 'Vos documents', 'Vouchers, billets et factures téléchargeables à tout moment.' ),
	array( 'Vos paiements', 'Acomptes versés, solde restant et échéances à venir.' ),
	array( 'Vos offres', 'Promotions réservées aux clients Ajinsafro avant leur publication.' ),
);

get_header();
?>

<main class="aj-login">
	<div class="aj-login__grid">

		<section class="aj-login__card">
			<h1>Connexion</h1>
			<p class="aj-login__lead">Connectez-vous avec votre email ou votre identifiant Ajinsafro.</p>

			<?php if ( $show_error ) : ?>
				<p class="aj-login__alert aj-login__alert--error" role="alert">
					Identifiants invalides. Vérifiez votre email ou identifiant, puis votre mot de passe.
				</p>
			<?php endif; ?>

			<?php if ( $show_expired ) : ?>
				<p class="aj-login__alert aj-login__alert--warn" role="alert">
					Votre session a expiré. Merci de vous reconnecter.
				</p>
			<?php endif; ?>

			<form class="aj-login__form" method="post" action="<?php echo esc_url( $endpoint ); ?>" data-aj-login>
				<label class="aj-login__field">
					<span class="aj-login__label">Email ou identifiant <b aria-hidden="true">*</b></span>
					<input type="text" name="login" value="<?php echo esc_attr( $login_prefill ); ?>"
					       placeholder="vous@exemple.ma" autocomplete="username"
					       required autocapitalize="none" spellcheck="false" data-aj-login-field>
				</label>

				<div class="aj-login__field">
					<span class="aj-login__labelrow">
						<span class="aj-login__label">Mot de passe <b aria-hidden="true">*</b></span>
						<a class="aj-login__forgot" href="<?php echo esc_url( $forgot_url ); ?>">Mot de passe oublié ?</a>
					</span>
					<span class="aj-login__password">
						<input type="password" name="password" placeholder="Votre mot de passe"
						       autocomplete="current-password" required data-aj-password>
						<button type="button" class="aj-login__reveal" data-aj-reveal
						        data-show="Afficher" data-hide="Masquer" aria-pressed="false">Afficher</button>
					</span>
				</div>

				<label class="aj-login__remember">
					<input type="checkbox" name="remember" value="1">
					<span>Rester connecté sur cet appareil</span>
				</label>

				<button type="submit" class="aj-login__submit">Se connecter</button>
			</form>

			<div class="aj-login__signup">
				<span>Pas encore de compte ?</span>
				<a class="aj-login__ghost" href="<?php echo esc_url( $signup_url ); ?>">Créer un compte</a>
			</div>

			<?php if ( '' !== $partner_url ) : ?>
				<a class="aj-login__partner" href="<?php echo esc_url( $partner_url ); ?>">
					Vous êtes une agence partenaire ? Accéder au portail
				</a>
			<?php endif; ?>
		</section>

		<aside class="aj-login__aside">
			<span class="aj-login__kicker">Votre compte Ajinsafro</span>
			<h2>Tout votre voyage au même endroit</h2>

			<ul class="aj-login__perks">
				<?php foreach ( $perks as $perk ) : ?>
					<li>
						<span class="aj-login__dot" aria-hidden="true"></span>
						<span class="aj-login__perk">
							<strong><?php echo esc_html( $perk[0] ); ?></strong>
							<span><?php echo esc_html( $perk[1] ); ?></span>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="aj-login__help">
				<strong>Un problème pour vous connecter ?</strong>
				<span>
					Écrivez à <a href="mailto:contact@ajinsafro.ma">contact@ajinsafro.ma</a>
					ou appelez le <a href="tel:+212539323874">+212 539 323 874</a>,
					du lundi au samedi de 9 h à 19 h.
				</span>
			</div>
		</aside>
	</div>
</main>

<script>
(function () {
    'use strict';

    var form = document.querySelector('[data-aj-login]');
    if (!form) { return; }

    // Afficher / masquer le mot de passe.
    var champ = form.querySelector('[data-aj-password]');
    var bouton = form.querySelector('[data-aj-reveal]');

    if (champ && bouton) {
        bouton.addEventListener('click', function () {
            var visible = champ.type === 'text';
            champ.type = visible ? 'password' : 'text';
            bouton.textContent = visible ? bouton.dataset.show : bouton.dataset.hide;
            bouton.setAttribute('aria-pressed', visible ? 'false' : 'true');
            champ.focus();
        });
    }

    // Le bouton s'attenue tant que le formulaire est vide, mais n'est jamais
    // desactive : un remplissage automatique qui n'emet pas d'evenement ne
    // doit pas empecher la connexion.
    var identifiant = form.querySelector('[data-aj-login-field]');
    var soumettre = form.querySelector('.aj-login__submit');

    function refletEtat() {
        if (!identifiant || !champ || !soumettre) { return; }
        var vide = identifiant.value.trim() === '' && champ.value === '';
        soumettre.classList.toggle('is-idle', vide);
    }

    form.addEventListener('input', refletEtat);
    refletEtat();
})();
</script>

<?php get_footer(); ?>
