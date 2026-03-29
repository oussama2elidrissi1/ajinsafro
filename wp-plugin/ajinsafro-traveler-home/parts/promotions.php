<?php
/**
 * Part: Explorez plus / promotional slider
 *
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $settings ) || ! is_array( $settings ) ) {
	$settings = array();
}

$promo_settings = isset( $settings['promotions'] ) ? $settings['promotions'] : array();
if ( is_string( $promo_settings ) ) {
	$decoded = json_decode( $promo_settings, true );
	$promo_settings = is_array( $decoded ) ? $decoded : array();
}
if ( ! is_array( $promo_settings ) ) {
	$promo_settings = array();
}

$section_title = ! empty( $promo_settings['title'] ) ? trim( (string) $promo_settings['title'] ) : 'Explorez plus, voyagez mieux avec AjinSafro';

$raw_items = isset( $promo_settings['items'] ) ? $promo_settings['items'] : array();
if ( is_string( $raw_items ) ) {
	$decoded = json_decode( $raw_items, true );
	$raw_items = is_array( $decoded ) ? $decoded : array();
}
if ( ! is_array( $raw_items ) ) {
	$raw_items = array();
}

$items = array();
foreach ( $raw_items as $idx => $item ) {
	if ( ! is_array( $item ) ) {
		continue;
	}

	$image_url = trim( (string) ( $item['image_url'] ?? $item['image'] ?? '' ) );
	$title = trim( (string) ( $item['title'] ?? '' ) );
	$subtitle = trim( (string) ( $item['subtitle'] ?? $item['description'] ?? '' ) );
	$button_text = trim( (string) ( $item['button_text'] ?? '' ) );
	$button_url = trim( (string) ( $item['button_url'] ?? '' ) );
	$is_active = $item['is_active'] ?? $item['active'] ?? true;

	if ( function_exists( 'ajth_truthy' ) ) {
		$is_active = ajth_truthy( $is_active );
	} else {
		$is_active = ! empty( $is_active );
	}

	if ( ! $is_active ) {
		continue;
	}

	if ( '' === $image_url && '' === $title && '' === $subtitle && '' === $button_text && '' === $button_url ) {
		continue;
	}

	$items[] = array(
		'image_url' => $image_url,
		'title' => $title,
		'subtitle' => $subtitle,
		'button_text' => $button_text,
		'button_url' => $button_url,
		'sort_order' => isset( $item['sort_order'] ) ? (int) $item['sort_order'] : ( isset( $item['order'] ) ? (int) $item['order'] : (int) $idx ),
	);
}

if ( empty( $items ) && ! empty( $promo_settings['images'] ) && is_array( $promo_settings['images'] ) ) {
	foreach ( array_values( $promo_settings['images'] ) as $idx => $image_url ) {
		$image_url = trim( (string) $image_url );
		if ( '' === $image_url ) {
			continue;
		}

		$items[] = array(
			'image_url' => $image_url,
			'title' => '',
			'subtitle' => '',
			'button_text' => '',
			'button_url' => '',
			'sort_order' => (int) $idx,
		);
	}
}

if ( empty( $items ) ) {
	return;
}

usort( $items, static function( $a, $b ) {
	return ( (int) ( $a['sort_order'] ?? 0 ) ) <=> ( (int) ( $b['sort_order'] ?? 0 ) );
} );
?>
<section class="aj-promos" id="aj-promos">
	<div class="aj-container">
		<div class="aj-promos__frame">
			<div class="aj-section-head">
				<h2 class="aj-section-title"><?php echo esc_html( $section_title ); ?></h2>
				<div class="aj-section-arrows">
					<button type="button" class="aj-section-arrow aj-promos-prev" aria-label="<?php esc_attr_e( 'Précédent', 'ajinsafro-traveler-home' ); ?>">
						<i class="fas fa-angle-left"></i>
					</button>
					<button type="button" class="aj-section-arrow aj-promos-next" aria-label="<?php esc_attr_e( 'Suivant', 'ajinsafro-traveler-home' ); ?>">
						<i class="fas fa-angle-right"></i>
					</button>
				</div>
			</div>

			<div class="aj-slider-v2 aj-promos__track" id="aj-promos-track">
				<?php foreach ( $items as $item ) : ?>
					<?php
					$image_url = trim( (string) ( $item['image_url'] ?? '' ) );
					$title = trim( (string) ( $item['title'] ?? '' ) );
					$subtitle = trim( (string) ( $item['subtitle'] ?? '' ) );
					$button_text = trim( (string) ( $item['button_text'] ?? '' ) );
					$button_url = trim( (string) ( $item['button_url'] ?? '' ) );
					$has_copy = '' !== $title || '' !== $subtitle || ( '' !== $button_text && '' !== $button_url );

					if ( function_exists( 'ajth_normalize_storage_url' ) ) {
						$image_url = ajth_normalize_storage_url( $image_url );
					}
					?>
					<article class="aj-slider-v2__item aj-promo-slide">
						<div class="aj-promo-banner<?php echo $has_copy ? '' : ' aj-promo-banner--visual-only'; ?>">
							<div class="aj-promo-banner__media">
								<?php if ( '' !== $image_url ) : ?>
									<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title !== '' ? $title : $section_title ); ?>" loading="lazy">
								<?php else : ?>
									<div class="aj-promo-banner__placeholder" aria-hidden="true"></div>
								<?php endif; ?>
							</div>

							<?php if ( $has_copy ) : ?>
								<div class="aj-promo-banner__content">
									<?php if ( '' !== $title ) : ?>
										<h3 class="aj-promo-banner__title"><?php echo esc_html( $title ); ?></h3>
									<?php endif; ?>

									<?php if ( '' !== $subtitle ) : ?>
										<p class="aj-promo-banner__subtitle"><?php echo esc_html( $subtitle ); ?></p>
									<?php endif; ?>

									<?php if ( '' !== $button_text && '' !== $button_url ) : ?>
										<a class="aj-promo-banner__cta" href="<?php echo esc_url( $button_url ); ?>">
											<?php echo esc_html( $button_text ); ?>
										</a>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
