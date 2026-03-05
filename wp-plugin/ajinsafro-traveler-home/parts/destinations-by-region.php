<?php
/**
 * Part: Nos destinations — Featured tile + 2-line scrollable carousel
 * Source: aj_destinations_by_region from Laravel admin
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$dbr = ajth_get_destinations_by_region();
if ( empty( $dbr['enabled'] ) || empty( $dbr['items'] ) ) {
    return;
}

$title = ! empty( $dbr['title'] ) ? $dbr['title'] : __( 'Nos destinations', 'ajinsafro-traveler-home' );
$items = $dbr['items'];

$featured = array_shift( $items );
$featured_label = ! empty( $featured['label'] ) ? $featured['label'] : 'Maroc';
$featured_img   = ! empty( $featured['image_url'] ) ? $featured['image_url'] : 'https://images.unsplash.com/photo-1489749798305-4fea3ae63d43?auto=format&fit=crop&w=800&q=80';
$featured_url   = ! empty( $featured['link_url'] ) ? $featured['link_url'] : '#';
$featured_flag  = ! empty( $featured['flag_url'] ) ? $featured['flag_url'] : 'https://flagcdn.com/w40/ma.png';

$country_flags = array(
    'maroc' => 'https://flagcdn.com/w40/ma.png',
    'portugal' => 'https://flagcdn.com/w40/pt.png',
    'turquie' => 'https://flagcdn.com/w40/tr.png',
    'espagne' => 'https://flagcdn.com/w40/es.png',
    'thailande' => 'https://flagcdn.com/w40/th.png',
    'france' => 'https://flagcdn.com/w40/fr.png',
    'italie' => 'https://flagcdn.com/w40/it.png',
    'egypte' => 'https://flagcdn.com/w40/eg.png',
    'maldives' => 'https://flagcdn.com/w40/mv.png',
    'tunisie' => 'https://flagcdn.com/w40/tn.png',
    'grèce' => 'https://flagcdn.com/w40/gr.png',
    'grece' => 'https://flagcdn.com/w40/gr.png',
);
?>

<section class="aj-regions" id="aj-regions">
    <div class="aj-container">
        <div class="aj-section-head">
            <h2 class="aj-section-title"><?php echo esc_html( $title ); ?></h2>
            <div class="aj-section-arrows">
                <button type="button" class="aj-section-arrow" aria-label="Précédent"><i class="fas fa-angle-left"></i></button>
                <button type="button" class="aj-section-arrow" aria-label="Suivant"><i class="fas fa-angle-right"></i></button>
            </div>
        </div>

        <div class="aj-dest-new">
            <!-- Featured destination -->
            <a href="<?php echo esc_url( $featured_url ); ?>" class="aj-dest-featured">
                <img src="<?php echo esc_url( $featured_img ); ?>" alt="<?php echo esc_attr( $featured_label ); ?>" loading="lazy">
                <div class="aj-dest-featured__overlay">
                    <div class="aj-dest-featured__info">
                        <img src="<?php echo esc_url( $featured_flag ); ?>" alt="" class="aj-dest-featured__flag">
                        <span class="aj-dest-featured__name"><?php echo esc_html( $featured_label ); ?></span>
                    </div>
                </div>
            </a>

            <!-- 2-line carousel -->
            <div class="aj-dest-carousel">
                <div class="aj-dest-grid">
                    <?php foreach ( $items as $r ) :
                        $img   = ! empty( $r['image_url'] ) ? $r['image_url'] : '';
                        $label = ! empty( $r['label'] ) ? $r['label'] : '';
                        $url   = ! empty( $r['link_url'] ) ? $r['link_url'] : '#';
                        $flag  = ! empty( $r['flag_url'] ) ? $r['flag_url'] : '';
                        if ( empty( $flag ) && $label ) {
                            $key = mb_strtolower( trim( $label ), 'UTF-8' );
                            $flag = isset( $country_flags[ $key ] ) ? $country_flags[ $key ] : '';
                        }
                    ?>
                    <a href="<?php echo esc_url( $url ); ?>" class="aj-dest-tile">
                        <?php if ( $img ) : ?>
                            <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $label ); ?>" loading="lazy">
                        <?php else : ?>
                            <div style="width:100%;height:100%;background:linear-gradient(135deg,#b0c4de,#7a9cc6);"></div>
                        <?php endif; ?>
                        <div class="aj-dest-tile__overlay">
                            <div class="aj-dest-tile__info">
                                <?php if ( $flag ) : ?>
                                    <img src="<?php echo esc_url( $flag ); ?>" alt="" class="aj-dest-tile__flag">
                                <?php endif; ?>
                                <span class="aj-dest-tile__name"><?php echo esc_html( $label ); ?></span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
