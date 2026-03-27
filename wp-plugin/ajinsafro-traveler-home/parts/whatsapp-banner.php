<?php
/**
 * Part: WhatsApp — bloc compact (badge, titre, texte, ligne meta, CTA, QR)
 *
 * Champs admin : title, subtitle, features[] (affichés en une ligne « a • b • c »), button_text, button_url, qr_code_url.
 * Clé optionnelle : badge (sinon défaut « WhatsApp »).
 *
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $settings ) || ! is_array( $settings ) ) {
	$settings = array();
}
$whatsapp = isset( $settings['whatsapp_banner'] ) && is_array( $settings['whatsapp_banner'] )
	? $settings['whatsapp_banner']
	: array();

$badge = isset( $whatsapp['badge'] ) ? trim( (string) $whatsapp['badge'] ) : '';
if ( $badge === '' ) {
	$badge = 'WhatsApp';
}
$badge = apply_filters( 'ajth_whatsapp_banner_badge', $badge, $whatsapp );

$title = ! empty( $whatsapp['title'] ) ? (string) $whatsapp['title'] : 'Rejoignez notre chaîne WhatsApp';
$description = ! empty( $whatsapp['subtitle'] ) ? (string) $whatsapp['subtitle'] : 'Recevez nos offres, actus et inspirations voyage.';

$features_raw = ! empty( $whatsapp['features'] ) && is_array( $whatsapp['features'] ) ? $whatsapp['features'] : array(
	'Promos',
	'Nouveautés',
	'Conseils',
);
$meta_parts = array();
foreach ( $features_raw as $f ) {
	$t = trim( (string) $f );
	if ( $t !== '' ) {
		$meta_parts[] = $t;
	}
}
$meta_line = ! empty( $meta_parts ) ? implode( ' • ', $meta_parts ) : '';
$meta_line = apply_filters( 'ajth_whatsapp_banner_meta_line', $meta_line, $whatsapp );

$button_text = ! empty( $whatsapp['button_text'] ) ? (string) $whatsapp['button_text'] : 'Rejoindre';
$button_url  = ! empty( $whatsapp['button_url'] ) ? (string) $whatsapp['button_url'] : '#';
$qr_code_url = ! empty( $whatsapp['qr_code_url'] ) ? (string) $whatsapp['qr_code_url'] : '';
if ( function_exists( 'ajth_normalize_storage_url' ) ) {
	$qr_code_url = ajth_normalize_storage_url( $qr_code_url );
}

$qr_hint = apply_filters( 'ajth_whatsapp_banner_qr_hint', 'Scanner pour rejoindre', $whatsapp );
?>

<section class="aj-whatsapp-banner">
	<div class="aj-container">
		<div class="aj-whatsapp-banner__wrap">
			<div class="aj-whatsapp-banner__inner">
				<div class="aj-whatsapp-banner__text-col">
					<div class="aj-whatsapp-banner__content">
						<span class="aj-whatsapp-banner__badge"><?php echo esc_html( $badge ); ?></span>
						<h2 class="aj-whatsapp-banner__title"><?php echo esc_html( $title ); ?></h2>
						<p class="aj-whatsapp-banner__desc"><?php echo esc_html( $description ); ?></p>
						<?php if ( $meta_line !== '' ) : ?>
						<p class="aj-whatsapp-banner__meta"><?php echo esc_html( $meta_line ); ?></p>
						<?php endif; ?>
						<a href="<?php echo esc_url( $button_url ); ?>" class="aj-whatsapp-banner__cta" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $button_text ); ?>
						</a>
					</div>
				</div>

				<?php if ( $qr_code_url ) : ?>
				<div class="aj-whatsapp-banner__qr-aside">
					<div class="aj-whatsapp-banner__qr-card">
						<img src="<?php echo esc_url( $qr_code_url ); ?>" alt="<?php echo esc_attr( 'QR code WhatsApp' ); ?>" class="aj-whatsapp-banner__qr-img" width="96" height="96" loading="lazy" decoding="async">
					</div>
					<?php if ( $qr_hint !== '' ) : ?>
					<p class="aj-whatsapp-banner__qr-hint"><?php echo esc_html( $qr_hint ); ?></p>
					<?php endif; ?>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
