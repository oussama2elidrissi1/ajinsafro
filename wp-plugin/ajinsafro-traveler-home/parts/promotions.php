<?php
/**
 * Part: promotional visuals
 *
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $settings ) || ! is_array( $settings ) ) {
	$settings = array();
}

$promo_settings = isset( $settings['promotions'] ) && is_array( $settings['promotions'] )
	? $settings['promotions']
	: array();

$section_title = ! empty( $promo_settings['title'] ) ? (string) $promo_settings['title'] : 'Explorez plus, voyagez mieux avec AjinSafro';

$images = array();
if ( ! empty( $promo_settings['images'] ) && is_array( $promo_settings['images'] ) ) {
	foreach ( array_slice( $promo_settings['images'], 0, 3 ) as $u ) {
		$u = trim( (string) $u );
		if ( $u !== '' ) {
			$images[] = $u;
		}
	}
}

if ( empty( $images ) && ! empty( $promo_settings['items'] ) && is_array( $promo_settings['items'] ) ) {
	foreach ( $promo_settings['items'] as $item ) {
		if ( count( $images ) >= 3 ) {
			break;
		}
		if ( ! is_array( $item ) ) {
			continue;
		}
		$u = trim( (string) ( $item['image_url'] ?? '' ) );
		if ( $u !== '' ) {
			$images[] = $u;
		}
	}
}

if ( empty( $images ) ) {
	return;
}
?>
<section class="aj-promos" id="aj-promos">
	<div class="aj-container">
		<h2 class="aj-section-title"><?php echo esc_html( $section_title ); ?></h2>

		<div class="aj-promos__grid">
			<?php foreach ( $images as $img_url ) : ?>
				<?php
				if ( function_exists( 'ajth_normalize_storage_url' ) ) {
					$img_url = ajth_normalize_storage_url( $img_url );
				}
				if ( $img_url === '' ) {
					continue;
				}
				?>
			<div class="aj-promo-card aj-promo-card--visual" style="background-image:url('<?php echo esc_url( $img_url ); ?>')"></div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
