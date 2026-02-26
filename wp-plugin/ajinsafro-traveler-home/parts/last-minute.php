<?php
/**
 * Part: Offres de dernière minute — horizontal slider
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$last_minute_settings = isset( $settings['last_minute'] ) && is_array( $settings['last_minute'] )
    ? $settings['last_minute']
    : array();

$section_title = ! empty( $last_minute_settings['title'] ) ? $last_minute_settings['title'] : 'Offres de dernière minute';
$items_count = ! empty( $last_minute_settings['count'] ) ? max( 1, intval( $last_minute_settings['count'] ) ) : 6;
$featured_only = ! empty( $last_minute_settings['featured_only'] );

$query_args = array(
    'post_type'      => 'st_tours',
    'posts_per_page' => $items_count,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'post_status'    => 'publish',
);

if ( $featured_only ) {
    $query_args['meta_query'] = array(
        'relation' => 'OR',
        array(
            'key' => 'is_featured',
            'value' => '1',
            'compare' => '=',
        ),
        array(
            'key' => 'st_is_featured',
            'value' => 'on',
            'compare' => '=',
        ),
    );
}

$q = new WP_Query( $query_args );
if ( ! $q->have_posts() ) return;
?>

<section class="aj-lm" id="aj-offres">
    <div class="aj-container">

        <div class="aj-lm__head">
            <h2 class="aj-section-title"><?php echo esc_html( $section_title ); ?></h2>
            <span class="aj-lm__badge">VOYAGES ›</span>
            <div class="aj-lm__nav">
                <button type="button" class="aj-arrow aj-arrow--prev" aria-label="Prev"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg></button>
                <button type="button" class="aj-arrow aj-arrow--next" aria-label="Next"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></button>
            </div>
        </div>

        <div class="aj-lm__slider" id="aj-lm-slider">
            <div class="aj-lm__track" id="aj-lm-track">

                <?php while ( $q->have_posts() ) : $q->the_post();
                    $price       = get_post_meta( get_the_ID(), 'price', true );
                    $sale_price  = get_post_meta( get_the_ID(), 'sale_price', true );
                    $avg_rating  = get_post_meta( get_the_ID(), 'avg_rating', true );
                    $location    = get_post_meta( get_the_ID(), 'address', true );
                    $excerpt     = get_the_excerpt() ? wp_trim_words( get_the_excerpt(), 12, '…' ) : wp_trim_words( get_the_content(), 12, '…' );
                    $dp = $sale_price ?: $price;
                ?>
                <div class="aj-card">
                    <a href="<?php the_permalink(); ?>" class="aj-card__a">
                        <div class="aj-card__top">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail( 'medium_large', array( 'class' => 'aj-card__img' ) ); ?>
                            <?php else : ?>
                                <div class="aj-card__ph"></div>
                            <?php endif; ?>
                            <span class="aj-card__badge">Featured</span>
                        </div>
                        <div class="aj-card__bot">
                            <?php if ( $location ) : ?>
                                <span class="aj-card__loc"><?php echo esc_html( $location ); ?></span>
                            <?php endif; ?>
                            <h3 class="aj-card__title"><?php the_title(); ?></h3>
                            <p class="aj-card__desc"><?php echo esc_html( $excerpt ); ?></p>
                            <div class="aj-card__foot">
                                <?php if ( $avg_rating ) : ?>
                                    <span class="aj-card__stars">
                                        <?php
                                        $full = floor(floatval($avg_rating));
                                        $half = (floatval($avg_rating) - $full) >= 0.5 ? 1 : 0;
                                        $empty_s = 5 - $full - $half;
                                        for ($s=0;$s<$full;$s++) echo '<i class="aj-s aj-s--on">★</i>';
                                        if ($half) echo '<i class="aj-s aj-s--half">★</i>';
                                        for ($s=0;$s<$empty_s;$s++) echo '<i class="aj-s aj-s--off">☆</i>';
                                        ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ( $dp ) : ?>
                                    <span class="aj-card__price">~<?php echo esc_html( number_format( floatval( $dp ), 0, ',', ' ' ) ); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endwhile; ?>

            </div>
        </div>

    </div>
</section>
<?php wp_reset_postdata(); ?>
