<?php
/**
 * Part: Cap sur les tendances du moment — horizontal card slider
 * Modern card design with ribbon, DHS price, VOIR L'OFFRE button
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$last_minute_settings = isset( $settings['last_minute'] ) && is_array( $settings['last_minute'] )
    ? $settings['last_minute']
    : array();

$section_title = ! empty( $last_minute_settings['title'] ) ? $last_minute_settings['title'] : 'Cap sur les tendances du moment';
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
        array( 'key' => 'is_featured', 'value' => '1', 'compare' => '=' ),
        array( 'key' => 'st_is_featured', 'value' => 'on', 'compare' => '=' ),
    );
}

$q = new WP_Query( $query_args );
if ( ! $q->have_posts() ) return;
?>

<section class="aj-lm" id="aj-offres">
    <div class="aj-container">
        <div class="aj-section-head">
            <h2 class="aj-section-title"><?php echo esc_html( $section_title ); ?></h2>
            <div class="aj-section-arrows">
                <button type="button" class="aj-section-arrow aj-arrow--prev" aria-label="Précédent"><i class="fas fa-angle-left"></i></button>
                <button type="button" class="aj-section-arrow aj-arrow--next" aria-label="Suivant"><i class="fas fa-angle-right"></i></button>
            </div>
        </div>

        <div class="aj-slider-v2" id="aj-lm-track">
            <?php while ( $q->have_posts() ) : $q->the_post();
                $price      = get_post_meta( get_the_ID(), 'price', true );
                $sale_price = get_post_meta( get_the_ID(), 'sale_price', true );
                $avg_rating = get_post_meta( get_the_ID(), 'avg_rating', true );
                $location   = get_post_meta( get_the_ID(), 'address', true );
                $duration   = get_post_meta( get_the_ID(), 'duration', true );
                $excerpt    = get_the_excerpt() ? wp_trim_words( get_the_excerpt(), 18, '…' ) : wp_trim_words( get_the_content(), 18, '…' );
                $dp = $sale_price ?: $price;
            ?>
            <div class="aj-slider-v2__item">
                <a href="<?php the_permalink(); ?>" class="aj-card2 aj-hover-glass" style="text-decoration:none;">
                    <div class="aj-ribbon"><span>Featured</span></div>
                    <div class="aj-card2__image">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php
                            // Sans `sizes`, WordPress annonce 768px et le navigateur telecharge une
                            // declinaison 2 a 4 fois trop grande. Les cartes suivent .aj-slider-v2__item :
                            // 80vw jusqu'a 768px, 50vw jusqu'a 1024px, puis 25% d'un conteneur de 1280px.
                            the_post_thumbnail( 'medium_large', array(
                                'loading' => 'lazy',
                                'sizes'   => '(max-width: 768px) 80vw, (max-width: 1024px) 50vw, 300px',
                            ) );
                            ?>
                        <?php else : ?>
                            <div style="width:100%;height:100%;background:linear-gradient(135deg,#ccc,#aaa);"></div>
                        <?php endif; ?>
                        <?php if ( $duration ) : ?>
                        <span class="aj-card2__badge aj-card2__badge--info">
                            <i class="far fa-clock"></i> <?php echo esc_html( $duration ); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="aj-card2__body">
                        <h3 class="aj-card2__title"><?php the_title(); ?></h3>
                        <p class="aj-card2__desc"><?php echo esc_html( $excerpt ); ?></p>
                        <div class="aj-card2__footer">
                            <div>
                                <?php if ( $dp ) : ?>
                                <span class="aj-card2__price-label">à partir de</span>
                                <div class="aj-card2__price">
                                    <?php echo esc_html( number_format( floatval( $dp ), 0, ',', ' ' ) ); ?>
                                    <span class="aj-card2__price-currency">DHS</span>
                                </div>
                                <span class="aj-card2__price-note">prix par personne</span>
                                <?php endif; ?>
                            </div>
                            <span class="aj-card2__cta">VOIR L'OFFRE</span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>
<?php wp_reset_postdata(); ?>
