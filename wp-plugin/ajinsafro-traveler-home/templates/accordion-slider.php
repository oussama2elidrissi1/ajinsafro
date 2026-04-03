<?php
/**
 * Accordion Slider Template
 * Shortcode: [ajinsafro_slider]
 *
 * Faithfully reproduces the Slide(3).html reference design.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$aji_img_base = AJINSAFRO_HOME_URL . 'assets/img/';

$slides = array(
	array(
		'title'       => 'PROGRAMME DE FIDÉLITÉ',
		'image'       => $aji_img_base . 'slide-1.png',
		'tab_from'    => '#00a3e0',
		'tab_to'      => '#0081bc',
		'overlay_from' => 'rgba(0,163,224,0.10)',
		'overlay_to'   => 'rgba(0,129,188,0.10)',
		'cta_text'    => "S'inscrire !",
		'cta_url'     => 'https://www.ajinsafro.ma/fidelite',
		'cta_style'   => 'orange',
	),
	array(
		'title'       => 'GROUP DEALS TRAVEL',
		'image'       => $aji_img_base . 'slide-2.png',
		'tab_from'    => '#4ade80',
		'tab_to'      => '#16a34a',
		'overlay_from' => 'rgba(34,197,94,0.05)',
		'overlay_to'   => 'rgba(22,163,74,0.05)',
		'cta_text'    => '',
		'cta_url'     => '',
		'cta_style'   => '',
	),
	array(
		'title'       => "L'7AJZ BKRI B'DHAB MCHRI",
		'image'       => $aji_img_base . 'slide-3.png',
		'tab_from'    => '#1b5c8c',
		'tab_to'      => '#0E3A5A',
		'overlay_from' => 'rgba(27,92,140,0.05)',
		'overlay_to'   => 'rgba(14,58,90,0.05)',
		'cta_text'    => 'احجز الآن',
		'cta_url'     => '#',
		'cta_style'   => 'arabic',
	),
	array(
		'title'       => 'Programme BZTAM eSFAR',
		'image'       => $aji_img_base . 'slide-4.png',
		'tab_from'    => '#facc15',
		'tab_to'      => '#f97316',
		'overlay_from' => 'rgba(250,204,21,0.10)',
		'overlay_to'   => 'rgba(249,115,22,0.10)',
		'cta_text'    => '',
		'cta_url'     => '',
		'cta_style'   => '',
	),
	array(
		'title'       => 'IMPORTANT UPDATES',
		'image'       => '',
		'tab_from'    => '#ef4444',
		'tab_to'      => '#b91c1c',
		'overlay_from' => '',
		'overlay_to'   => '',
		'cta_text'    => '',
		'cta_url'     => '',
		'cta_style'   => '',
	),
);

$slides = apply_filters( 'ajinsafro_slider_slides', $slides );

if ( empty( $slides ) || ! is_array( $slides ) ) {
	return;
}
?>
<div id="aji-accordion" class="aji-accordion" role="region" aria-label="Promotions AjiNsafro">
<?php foreach ( array_values( $slides ) as $idx => $slide ) :
	$title       = esc_attr( $slide['title'] );
	$image       = $slide['image'];
	$tab_bg      = "linear-gradient(to bottom,{$slide['tab_from']},{$slide['tab_to']})";
	$has_overlay = ! empty( $slide['overlay_from'] );
	$overlay_bg  = $has_overlay ? "linear-gradient(to bottom,{$slide['overlay_from']},{$slide['overlay_to']})" : '';
	$cta_text    = $slide['cta_text'];
	$cta_url     = $slide['cta_url'];
	$cta_style   = $slide['cta_style'];
?>
	<div class="aji-panel" data-index="<?php echo $idx; ?>" role="button" tabindex="0" aria-label="<?php echo $title; ?>">
		<div class="aji-tab" style="background:<?php echo esc_attr( $tab_bg ); ?>">
			<span class="aji-tab-text"><?php echo esc_html( $slide['title'] ); ?></span>
		</div>
		<div class="aji-pane">
			<?php if ( ! empty( $image ) ) : ?>
				<img class="aji-pane-img"
				     src="<?php echo esc_url( $image ); ?>"
				     alt="<?php echo $title; ?>"
				     loading="<?php echo 0 === $idx ? 'eager' : 'lazy'; ?>"
				     <?php echo 0 === $idx ? 'fetchpriority="high"' : ''; ?>>
			<?php else : ?>
				<div class="aji-placeholder">800&times;800</div>
			<?php endif; ?>
			<?php if ( $has_overlay ) : ?>
				<div class="aji-pane-overlay" style="background:<?php echo esc_attr( $overlay_bg ); ?>"></div>
			<?php endif; ?>
			<?php if ( ! empty( $cta_text ) ) :
				$href = ( '#' === $cta_url ) ? '#' : esc_url( $cta_url );
				$cta_class = 'aji-cta' . ( 'arabic' === $cta_style ? ' aji-cta--ar' : '' );
			?>
				<div class="aji-cta-wrap">
					<a class="<?php echo esc_attr( $cta_class ); ?>"
					   href="<?php echo $href; ?>"
					   target="_blank"
					   rel="noopener noreferrer"
					   onclick="event.stopPropagation()">
						<?php echo esc_html( $cta_text ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
	</div>
<?php endforeach; ?>
</div>
