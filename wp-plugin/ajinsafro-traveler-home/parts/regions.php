<?php
/**
 * Part: Destinations par région  — 2×4 grid
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$regions = ! empty( $settings['regions'] ) ? $settings['regions'] : array();
if ( empty( $regions ) ) return;
?>

<section class="aj-regions" id="aj-regions">
    <div class="aj-container">
        <h2 class="aj-section-title aj-section-title--green"><?php esc_html_e( 'Destinations par région', 'ajinsafro-traveler-home' ); ?></h2>
        <div class="aj-regions__grid">
            <?php foreach ( $regions as $r ) :
                $img = ! empty($r['image_url']) ? $r['image_url'] : '';
                if ( function_exists( 'ajth_normalize_storage_url' ) ) {
                    $img = ajth_normalize_storage_url( $img );
                }
                $t   = ! empty($r['title']) ? $r['title'] : '';
                $u   = ! empty($r['link_url'])   ? $r['link_url']   : '#';
            ?>
            <a href="<?php echo esc_url($u); ?>" class="aj-reg">
                <?php if ($img) : ?><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($t); ?>" loading="lazy" class="aj-reg__img"><?php else : ?><div class="aj-reg__ph"></div><?php endif; ?>
                <span class="aj-reg__label"><?php echo esc_html( mb_strtoupper($t) ); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
