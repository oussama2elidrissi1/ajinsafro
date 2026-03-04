<?php
/**
 * Part: Destinations par région (source: aj_destinations_by_region from Laravel admin)
 * Grille 2×4 — label, image_url, link_url, order
 *
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$dbr = ajth_get_destinations_by_region();
if ( empty( $dbr['enabled'] ) || empty( $dbr['items'] ) ) {
    return;
}

$title = ! empty( $dbr['title'] ) ? $dbr['title'] : __( 'Destinations par région', 'ajinsafro-traveler-home' );
$items = $dbr['items'];
?>

<section class="aj-regions" id="aj-regions">
    <div class="aj-container">
        <h2 class="aj-section-title aj-section-title--green"><?php echo esc_html( $title ); ?></h2>
        <div class="aj-regions__grid">
            <?php foreach ( $items as $r ) :
                $img = ! empty( $r['image_url'] ) ? $r['image_url'] : '';
                $label = ! empty( $r['label'] ) ? $r['label'] : '';
                $url  = ! empty( $r['link_url'] ) ? $r['link_url'] : '#';
            ?>
            <a href="<?php echo esc_url( $url ); ?>" class="aj-reg">
                <?php if ( $img ) : ?>
                    <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $label ); ?>" loading="lazy" class="aj-reg__img">
                <?php else : ?>
                    <div class="aj-reg__ph"></div>
                <?php endif; ?>
                <span class="aj-reg__label"><?php echo esc_html( $label ); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
