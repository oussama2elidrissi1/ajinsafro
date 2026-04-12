<?php
/**
 * Part: Découvrez des séjours uniques — Accommodation cards
 * Displays hotels from st_hotel post type
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$accom_settings = isset( $settings['accommodations'] ) && is_array( $settings['accommodations'] )
    ? $settings['accommodations']
    : array();

$section_title = ! empty( $accom_settings['title'] ) ? $accom_settings['title'] : 'Découvrez des séjours uniques';
$items_count   = ! empty( $accom_settings['count'] ) ? max( 1, intval( $accom_settings['count'] ) ) : 4;

$query_args = array(
    'post_type'      => array( 'st_hotel', 'st_rental' ),
    'posts_per_page' => $items_count,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'post_status'    => 'publish',
);

$q = new WP_Query( $query_args );
if ( ! $q->have_posts() ) return;
?>

<section class="aj-accom" id="aj-sejours">
    <div class="aj-container">
        <div class="aj-section-head">
            <h2 class="aj-section-title"><?php echo esc_html( $section_title ); ?></h2>
            <div class="aj-section-arrows">
                <button type="button" class="aj-section-arrow aj-accom-prev" aria-label="Précédent"><i class="fas fa-angle-left"></i></button>
                <button type="button" class="aj-section-arrow aj-accom-next" aria-label="Suivant"><i class="fas fa-angle-right"></i></button>
            </div>
        </div>

        <div class="aj-slider-v2" id="aj-accom-track">
            <?php while ( $q->have_posts() ) : $q->the_post();
                $price      = get_post_meta( get_the_ID(), 'price', true );
                $sale_price = get_post_meta( get_the_ID(), 'sale_price', true );
                $avg_rating = get_post_meta( get_the_ID(), 'avg_rating', true );
                $location   = get_post_meta( get_the_ID(), 'address', true );
                $stars      = get_post_meta( get_the_ID(), 'hotel_star', true );
                // Fallback: Laravel / Traveler often store canonical data in st_hotel while this slider reads metas.
                if ( ( $location === '' || $location === false ) && get_post_type() === 'st_hotel' ) {
                    global $wpdb;
                    $row = $wpdb->get_row( $wpdb->prepare( "SELECT address, min_price, hotel_star FROM {$wpdb->prefix}st_hotel WHERE post_id = %d", get_the_ID() ), ARRAY_A );
                    if ( is_array( $row ) ) {
                        if ( ( $location === '' || $location === false ) && ! empty( $row['address'] ) ) {
                            $location = $row['address'];
                        }
                        if ( ( $price === '' || $price === false ) && ! empty( $row['min_price'] ) ) {
                            $price = $row['min_price'];
                        }
                        if ( ( $stars === '' || $stars === false ) && isset( $row['hotel_star'] ) && $row['hotel_star'] !== '' ) {
                            $stars = $row['hotel_star'];
                        }
                    }
                }
                $category   = '';
                $terms = get_the_terms( get_the_ID(), 'hotel_type' );
                if ( $terms && ! is_wp_error( $terms ) ) {
                    $category = $terms[0]->name;
                }
                if ( empty( $category ) ) {
                    $category = ( get_post_type() === 'st_rental' ) ? 'Appartement' : 'Hôtel';
                }
                $dp = $sale_price ?: $price;
                $star_count = $stars ? intval( $stars ) : 0;
            ?>
            <div class="aj-slider-v2__item">
                <a href="<?php the_permalink(); ?>" class="aj-card2 aj-hover-glass" style="text-decoration:none;">
                    <div class="aj-card2__image">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?>
                        <?php else : ?>
                            <div style="width:100%;height:100%;background:linear-gradient(135deg,#ccc,#aaa);"></div>
                        <?php endif; ?>
                    </div>
                    <div class="aj-card2__body">
                        <h3 class="aj-card2__title"><?php the_title(); ?></h3>
                        <?php if ( $location ) : ?>
                        <div class="aj-card2__location"><i class="fas fa-map-marker-alt"></i> <?php echo esc_html( $location ); ?></div>
                        <?php endif; ?>
                        <div class="aj-card2__meta">
                            <span class="aj-card2__category"><?php echo esc_html( $category ); ?></span>
                            <?php if ( $star_count > 0 ) : ?>
                            <div class="aj-card2__stars">
                                <?php for ( $s = 0; $s < 5; $s++ ) : ?>
                                    <i class="<?php echo $s < $star_count ? 'fas' : 'far'; ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="aj-card2__footer">
                            <div>
                                <?php if ( $dp ) : ?>
                                <span class="aj-card2__price-label">à partir de</span>
                                <div class="aj-card2__price">
                                    <?php echo esc_html( number_format( floatval( $dp ), 0, ',', ' ' ) ); ?>
                                    <span class="aj-card2__price-currency">DHS</span>
                                </div>
                                <span class="aj-card2__price-note">prix par personne par nuit</span>
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
