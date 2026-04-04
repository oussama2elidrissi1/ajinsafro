<?php
/**
 * Part: standalone homepage accordion slider driven by home settings.
 *
 * @package AjinsafroTravelerHome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = ajth_get_settings();
$accordion = isset( $settings['accordion_slider'] ) && is_array( $settings['accordion_slider'] )
	? $settings['accordion_slider']
	: array();

$raw_slides = isset( $accordion['slides'] ) && is_array( $accordion['slides'] )
	? $accordion['slides']
	: array();

if ( count( $raw_slides ) < 1 ) {
	return;
}

$tab_gradients = array(
	'linear-gradient(to bottom, #00a3e0, #0081bc)',
	'linear-gradient(to bottom, #4ade80, #16a34a)',
	'linear-gradient(to bottom, #1b5c8c, #0e3a5a)',
	'linear-gradient(to bottom, #facc15, #f97316)',
	'linear-gradient(to bottom, #ef4444, #b91c1c)',
);

$pane_backgrounds = array( '#e5e7eb', '#d1d5db', '#e5e7eb', '#d1d5db', '#e5e7eb' );
$slides = array();

foreach ( array_values( $raw_slides ) as $index => $slide ) {
	if ( ! is_array( $slide ) ) {
		continue;
	}

	$title = trim( (string) ( $slide['title'] ?? '' ) );
	if ( '' === $title ) {
		continue;
	}

	$button_style = trim( (string) ( $slide['button_style'] ?? 'orange' ) );
	if ( '' === $button_style ) {
		$button_style = 'orange';
	}
	if ( 'white' === $button_style ) {
		$button_style = 'arabic';
	}
	if ( 'white-arabic' === $button_style ) {
		$button_style = 'arabic';
	}

	$button_text = trim( (string) ( $slide['button_text'] ?? '' ) );
	$link = trim( (string) ( $slide['link'] ?? '#' ) );
	$target = 'white-arabic' === $button_style ? '_self' : '_top';
	if ( '#' === $link || '' === $link ) {
		$target = '_self';
	}

	$slides[] = array(
		'title' => $title,
		'subtitle' => trim( (string) ( $slide['subtitle'] ?? '' ) ),
		'image' => trim( (string) ( $slide['image'] ?? '' ) ),
		'pane_bg' => $pane_backgrounds[ $index % count( $pane_backgrounds ) ],
		'tab_bg' => $tab_gradients[ $index % count( $tab_gradients ) ],
		'overlay' => trim( (string) ( $slide['overlay_color'] ?? '' ) ),
		'cta' => '' !== $button_text ? array(
			'text' => $button_text,
			'url' => '' !== $link ? $link : '#',
			'target' => $target,
			'kind' => $button_style,
			'wrap' => 'arabic' === $button_style ? 'arabic' : 'default',
		) : null,
	);
}

$slides = apply_filters( 'ajth_reference_accordion_slides', $slides, $accordion, $settings );

if ( empty( $slides ) || ! is_array( $slides ) ) {
	return;
}

$delay = isset( $accordion['autoplay_speed'] ) ? (int) $accordion['autoplay_speed'] : 5000;
$delay = max( 2000, min( 30000, $delay ) );
$autoplay = ! empty( $accordion['autoplay'] );
?>
<section class="aj-section ajha-ref-section" id="aj-reference-accordion">
	<div class="aj-container">
		<div
			class="ajha-ref-accordion"
			data-ajha-ref-accordion="1"
			data-ajha-ref-autoplay="<?php echo $autoplay ? '1' : '0'; ?>"
			data-delay="<?php echo esc_attr( (string) $delay ); ?>"
			data-start-index="0"
			role="region"
			aria-label="<?php echo esc_attr__( 'Promotions AjiNsafro', 'ajinsafro-traveler-home' ); ?>"
		>
			<?php foreach ( array_values( $slides ) as $index => $slide ) : ?>
				<?php
				$title = isset( $slide['title'] ) ? (string) $slide['title'] : '';
				$image = isset( $slide['image'] ) ? (string) $slide['image'] : '';
				$pane_bg = isset( $slide['pane_bg'] ) ? (string) $slide['pane_bg'] : '#e5e7eb';
				$tab_bg = isset( $slide['tab_bg'] ) ? (string) $slide['tab_bg'] : '';
				$overlay = isset( $slide['overlay'] ) ? (string) $slide['overlay'] : '';
				$cta = isset( $slide['cta'] ) && is_array( $slide['cta'] ) ? $slide['cta'] : null;
				?>
				<div
					class="ajha-ref-accordion__slide"
					data-index="<?php echo esc_attr( (string) $index ); ?>"
					role="button"
					tabindex="0"
					aria-expanded="<?php echo 0 === $index ? 'true' : 'false'; ?>"
					aria-label="<?php echo esc_attr( $title ); ?>"
				>
					<div class="ajha-ref-accordion__tab" style="<?php echo $tab_bg ? 'background:' . esc_attr( $tab_bg ) . ';' : ''; ?>">
						<span class="ajha-ref-accordion__tabtext"><?php echo esc_html( $title ); ?></span>
					</div>
					<div class="ajha-ref-accordion__panel" style="<?php echo 'background-color:' . esc_attr( $pane_bg ) . ';'; ?>">
						<?php if ( '' !== $image ) : ?>
							<img
								class="ajha-ref-accordion__img"
								src="<?php echo esc_url( $image ); ?>"
								alt="<?php echo esc_attr( $title ); ?>"
								loading="<?php echo 0 === $index ? 'eager' : 'lazy'; ?>"
								decoding="async"
								<?php echo 0 === $index ? 'fetchpriority="high"' : ''; ?>
							>
						<?php else : ?>
							<div class="ajha-ref-accordion__placeholder" aria-hidden="true">800x800</div>
						<?php endif; ?>

						<?php if ( '' !== $overlay ) : ?>
							<div class="ajha-ref-accordion__overlay" style="background:<?php echo esc_attr( $overlay ); ?>;"></div>
						<?php endif; ?>

						<?php if ( $cta && ! empty( $cta['text'] ) ) : ?>
							<?php
							$cta_url = isset( $cta['url'] ) ? (string) $cta['url'] : '#';
							$cta_target = isset( $cta['target'] ) ? (string) $cta['target'] : '_self';
							$cta_kind = isset( $cta['kind'] ) ? (string) $cta['kind'] : 'orange';
							$cta_wrap = isset( $cta['wrap'] ) ? (string) $cta['wrap'] : 'default';
							?>
							<div class="ajha-ref-accordion__cta-wrap ajha-ref-accordion__cta-wrap--<?php echo esc_attr( $cta_wrap ); ?>">
								<a
									class="ajha-ref-accordion__cta ajha-ref-accordion__cta--<?php echo esc_attr( $cta_kind ); ?>"
									href="<?php echo '#' === $cta_url ? '#' : esc_url( $cta_url ); ?>"
									target="<?php echo esc_attr( $cta_target ); ?>"
									<?php echo '_blank' === $cta_target ? 'rel="noopener noreferrer"' : ''; ?>
									onclick="event.stopPropagation();"
								>
									<?php echo esc_html( (string) $cta['text'] ); ?>
								</a>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
