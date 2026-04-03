<?php
/**
 * Part: Explorez plus — accordéon prototype (onglets verticaux + contenu, flex-1 / ping-pong autoplay)
 *
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $settings ) || ! is_array( $settings ) ) {
	$settings = array();
}

$promo = isset( $settings['promotions'] ) && is_array( $settings['promotions'] ) ? $settings['promotions'] : array();

$promo_section_on = ! isset( $promo['enabled'] );
if ( isset( $promo['enabled'] ) ) {
	$promo_section_on = function_exists( 'ajth_truthy' ) ? ajth_truthy( $promo['enabled'] ) : ! empty( $promo['enabled'] );
}
if ( ! $promo_section_on ) {
	return;
}

$section_title = ! empty( $promo['title'] ) ? trim( (string) $promo['title'] ) : 'Explorez plus, voyagez mieux avec AjiNsafro';
$raw_items = isset( $promo['items'] ) && is_array( $promo['items'] ) ? $promo['items'] : array();
$max_slides = isset( $promo['max_slides'] ) ? max( 1, min( 20, (int) $promo['max_slides'] ) ) : 8;

usort(
	$raw_items,
	static function ( $a, $b ) {
		$a_order = is_array( $a ) ? (int) ( $a['sort_order'] ?? $a['order'] ?? 0 ) : 0;
		$b_order = is_array( $b ) ? (int) ( $b['sort_order'] ?? $b['order'] ?? 0 ) : 0;
		return $a_order <=> $b_order;
	}
);

$items = array();
foreach ( $raw_items as $row ) {
	if ( ! is_array( $row ) ) {
		continue;
	}
	$active = isset( $row['is_active'] ) ? ( function_exists( 'ajth_truthy' ) ? ajth_truthy( $row['is_active'] ) : ! empty( $row['is_active'] ) ) : true;
	if ( ! $active ) {
		continue;
	}
	$items[] = $row;
	if ( count( $items ) >= $max_slides ) {
		break;
	}
}

if ( empty( $items ) ) {
	return;
}

$autoplay = ! isset( $promo['autoplay'] ) || ( function_exists( 'ajth_truthy' ) ? ajth_truthy( $promo['autoplay'] ) : ! empty( $promo['autoplay'] ) );
$delay_ms = isset( $promo['autoplay_delay_ms'] ) ? max( 2000, min( 60000, (int) $promo['autoplay_delay_ms'] ) ) : 5000;
$arrows_enabled = isset( $promo['arrows_enabled'] ) && ( function_exists( 'ajth_truthy' ) ? ajth_truthy( $promo['arrows_enabled'] ) : ! empty( $promo['arrows_enabled'] ) );
$def_idx = isset( $promo['default_active_index'] ) ? max( 0, (int) $promo['default_active_index'] ) : 0;
if ( count( $items ) > 0 ) {
	$def_idx = min( $def_idx, count( $items ) - 1 );
} else {
	$def_idx = 0;
}

$uid = 'aj-accordion-slider';
?>
<section class="aj-promos aj-promos--proto-accordion" id="aj-promos" aria-labelledby="<?php echo esc_attr( $uid ); ?>-heading">
	<div class="aj-container">
		<h2 class="aj-promos__proto-title" id="<?php echo esc_attr( $uid ); ?>-heading"><?php echo esc_html( $section_title ); ?></h2>
		<div
			class="aj-accordion-slider"
			id="<?php echo esc_attr( $uid ); ?>"
			data-autoplay="<?php echo $autoplay ? '1' : '0'; ?>"
			data-delay="<?php echo esc_attr( (string) $delay_ms ); ?>"
			data-default-index="<?php echo esc_attr( (string) $def_idx ); ?>"
			data-arrows-enabled="<?php echo $arrows_enabled ? '1' : '0'; ?>"
		>
			<?php
			foreach ( $items as $i => $item ) :
				if ( ! is_array( $item ) ) {
					continue;
				}
				$image_url = trim( (string) ( $item['image_url'] ?? $item['image'] ?? '' ) );
				if ( function_exists( 'ajth_normalize_storage_url' ) ) {
					$image_url = ajth_normalize_storage_url( $image_url );
				}
				$title = trim( (string) ( $item['title'] ?? '' ) );
				$placeholder_text = trim( (string) ( $item['placeholder_text'] ?? '' ) );
				$theme = min( 4, max( 0, (int) $i ) );

				$link_target = ( isset( $item['link_target'] ) && (string) $item['link_target'] === '_blank' ) ? '_blank' : '_self';
				$link_url = function_exists( 'ajth_sanitize_promo_url' ) ? ajth_sanitize_promo_url( (string) ( $item['link_url'] ?? '' ) ) : trim( (string) ( $item['link_url'] ?? '' ) );
				$button_text = trim( (string) ( $item['button_text'] ?? '' ) );
				$button_url = function_exists( 'ajth_sanitize_promo_url' ) ? ajth_sanitize_promo_url( (string) ( $item['button_url'] ?? '' ) ) : trim( (string) ( $item['button_url'] ?? '' ) );
				$btn_on = ! isset( $item['button_enabled'] ) || ( function_exists( 'ajth_truthy' ) ? ajth_truthy( $item['button_enabled'] ) : ! empty( $item['button_enabled'] ) );
				$show_btn_link = $btn_on && $button_text !== '' && $button_url !== '';
				$show_btn_span = $btn_on && $button_text !== '' && $button_url === '';

				$is_active = (int) $i === (int) $def_idx;
				$tclass = 'aj-accordion-slide--t' . $theme;
				?>
			<div
				class="aj-accordion-slide <?php echo esc_attr( $tclass ); ?><?php echo $is_active ? ' is-active' : ''; ?>"
				data-index="<?php echo esc_attr( (string) $i ); ?>"
				data-link-url="<?php echo esc_url( $link_url ); ?>"
				data-link-target="<?php echo esc_attr( $link_target ); ?>"
				role="button"
				tabindex="0"
				aria-expanded="<?php echo $is_active ? 'true' : 'false'; ?>"
			>
				<div class="aj-accordion-slide__tab" aria-hidden="true">
					<span class="aj-accordion-slide__tab-text"><?php echo esc_html( $title ); ?></span>
				</div>
				<div class="aj-accordion-slide__content<?php echo $is_active ? ' is-visible' : ' is-obscured'; ?>">
					<?php if ( $image_url !== '' ) : ?>
						<img class="aj-accordion-slide__img" src="<?php echo esc_url( $image_url ); ?>" alt="" loading="<?php echo $is_active ? 'eager' : 'lazy'; ?>" decoding="async" width="1200" height="800">
					<?php elseif ( $placeholder_text !== '' ) : ?>
						<div class="aj-accordion-slide__placeholder">
							<span class="aj-accordion-slide__placeholder-text"><?php echo esc_html( $placeholder_text ); ?></span>
						</div>
					<?php else : ?>
						<div class="aj-accordion-slide__placeholder aj-accordion-slide__placeholder--empty"></div>
					<?php endif; ?>
					<div class="aj-accordion-slide__overlay" aria-hidden="true"></div>
					<?php if ( $show_btn_link && 0 === $theme ) : ?>
						<div class="aj-accordion-slide__cta-wrap aj-accordion-slide__cta-wrap--orange">
							<a class="aj-accordion-slide__cta aj-accordion-slide__cta--orange" href="<?php echo esc_url( $button_url ); ?>" target="_top" rel="noopener" onclick="event.stopPropagation();"><?php echo esc_html( $button_text ); ?></a>
						</div>
					<?php elseif ( $show_btn_link ) : ?>
						<div class="aj-accordion-slide__cta-wrap aj-accordion-slide__cta-wrap--orange">
							<a class="aj-accordion-slide__cta aj-accordion-slide__cta--pill" href="<?php echo esc_url( $button_url ); ?>"<?php echo '_blank' === $link_target ? ' target="_blank" rel="noopener noreferrer"' : ''; ?> onclick="event.stopPropagation();"><?php echo esc_html( $button_text ); ?></a>
						</div>
					<?php elseif ( $show_btn_span && 2 === $theme ) : ?>
						<div class="aj-accordion-slide__cta-wrap aj-accordion-slide__cta-wrap--arabic">
							<span class="aj-accordion-slide__cta aj-accordion-slide__cta--arabic" onclick="event.stopPropagation();"><?php echo esc_html( $button_text ); ?></span>
						</div>
					<?php elseif ( $show_btn_span ) : ?>
						<div class="aj-accordion-slide__cta-wrap">
							<span class="aj-accordion-slide__cta aj-accordion-slide__cta--muted" onclick="event.stopPropagation();"><?php echo esc_html( $button_text ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			</div>
				<?php endforeach; ?>
			<?php if ( $arrows_enabled && count( $items ) > 1 ) : ?>
				<button type="button" class="aj-accordion-arrow aj-accordion-arrow--prev" aria-label="Slide précédente" data-accordion-prev="1">
					<span aria-hidden="true">&#8249;</span>
				</button>
				<button type="button" class="aj-accordion-arrow aj-accordion-arrow--next" aria-label="Slide suivante" data-accordion-next="1">
					<span aria-hidden="true">&#8250;</span>
				</button>
			<?php endif; ?>
		</div>
	</div>
</section>
