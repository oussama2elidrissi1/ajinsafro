<?php
/**
 * Voyages Page Template
 *
 * Displays all trips (st_tours) with integrated search bar.
 *
 * @package AjinsafroTravelerHome
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$settings = ajth_get_settings();

$paged = max(
    1,
    absint( get_query_var( 'paged' ) ),
    absint( get_query_var( 'page' ) )
);

$location_name = isset( $_GET['location_name'] ) ? sanitize_text_field( wp_unslash( $_GET['location_name'] ) ) : '';
$search_text   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$keyword       = $location_name !== '' ? $location_name : $search_text;

$query_args = array(
    'post_type'      => 'st_tours',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
);

if ( $keyword !== '' ) {
    $query_args['s'] = $keyword;
}

$q = new WP_Query( $query_args );

$is_search = ! empty( $keyword );
?>

<div class="aj-home-wrap">
    <div id="aj-home" class="aj-home aj-voyages-page">
        <?php if ( ! $is_search ) : ?>
        <section class="aj-voyages-hero">
            <div class="aj-container">
                <h1 class="aj-voyages-title"><?php esc_html_e( 'Tous les voyages', 'ajinsafro-traveler-home' ); ?></h1>
                <p class="aj-voyages-subtitle"><?php esc_html_e( 'Trouvez votre prochaine destination et réservez rapidement.', 'ajinsafro-traveler-home' ); ?></p>
                <div class="aj-voyages-search">
                    <?php include AJTH_DIR . 'parts/search.php'; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <section class="aj-voyages-results">
            <div class="aj-container">
                <?php if ( $is_search ) : ?>
                    <div class="aj-voyages-search-header">
                        <h1 class="aj-voyages-title"><?php esc_html_e( 'Résultats de recherche', 'ajinsafro-traveler-home' ); ?></h1>
                        <?php if ( $keyword ) : ?>
                            <p class="aj-voyages-subtitle">
                                <?php printf( esc_html__( 'Recherche pour : %s', 'ajinsafro-traveler-home' ), '<strong>' . esc_html( $keyword ) . '</strong>' ); ?>
                            </p>
                        <?php endif; ?>
                        <div class="aj-voyages-search">
                            <?php include AJTH_DIR . 'parts/search.php'; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="aj-voyages-results__head">
                    <h2 class="aj-section-title"><?php echo $is_search ? esc_html__( 'Voyages trouvés', 'ajinsafro-traveler-home' ) : esc_html__( 'Liste des voyages', 'ajinsafro-traveler-home' ); ?></h2>
                    <p class="aj-voyages-results__count">
                        <?php
                        printf(
                            esc_html( _n( '%d résultat', '%d résultats', intval( $q->found_posts ), 'ajinsafro-traveler-home' ) ),
                            intval( $q->found_posts )
                        );
                        ?>
                    </p>
                </div>

                <?php if ( $q->have_posts() ) : ?>
                    <div class="aj-voyages-grid">
                        <?php while ( $q->have_posts() ) : $q->the_post();
                            $price      = get_post_meta( get_the_ID(), 'price', true );
                            $sale_price = get_post_meta( get_the_ID(), 'sale_price', true );
                            $duration   = get_post_meta( get_the_ID(), 'duration', true );
                            $excerpt    = get_the_excerpt() ? wp_trim_words( get_the_excerpt(), 18, '…' ) : wp_trim_words( get_the_content(), 18, '…' );
                            $display_price = $sale_price ?: $price;
                        ?>
                            <article class="aj-voyages-grid__item">
                                <a href="<?php the_permalink(); ?>" class="aj-card2 aj-hover-glass">
                                    <div class="aj-card2__image">
                                        <?php if ( has_post_thumbnail() ) : ?>
                                            <?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?>
                                        <?php else : ?>
                                            <div class="aj-voyages-image-fallback"></div>
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
                                                <?php if ( $display_price ) : ?>
                                                    <span class="aj-card2__price-label"><?php esc_html_e( 'à partir de', 'ajinsafro-traveler-home' ); ?></span>
                                                    <div class="aj-card2__price">
                                                        <?php echo esc_html( number_format( floatval( $display_price ), 0, ',', ' ' ) ); ?>
                                                        <span class="aj-card2__price-currency">DHS</span>
                                                    </div>
                                                    <span class="aj-card2__price-note"><?php esc_html_e( 'prix par personne', 'ajinsafro-traveler-home' ); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="aj-card2__cta"><?php esc_html_e( "VOIR L'OFFRE", 'ajinsafro-traveler-home' ); ?></span>
                                        </div>
                                    </div>
                                </a>
                            </article>
                        <?php endwhile; ?>
                    </div>

                    <?php
                    $pagination = paginate_links( array(
                        'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
                        'format'    => '?paged=%#%',
                        'current'   => $paged,
                        'total'     => max( 1, intval( $q->max_num_pages ) ),
                        'type'      => 'array',
                        'prev_text' => '«',
                        'next_text' => '»',
                    ) );

                    if ( ! empty( $pagination ) ) :
                    ?>
                        <nav class="aj-voyages-pagination" aria-label="Pagination voyages">
                            <?php foreach ( $pagination as $page_link ) : ?>
                                <?php echo wp_kses_post( $page_link ); ?>
                            <?php endforeach; ?>
                        </nav>
                    <?php endif; ?>

                <?php else : ?>
                    <div class="aj-voyages-empty">
                        <i class="fas fa-search" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;"></i>
                        <p style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">
                            <?php esc_html_e( 'Aucun voyage trouvé', 'ajinsafro-traveler-home' ); ?>
                        </p>
                        <p style="color: #94a3b8;">
                            <?php echo $keyword ? esc_html__( 'Essayez avec d\'autres mots-clés.', 'ajinsafro-traveler-home' ) : esc_html__( 'Aucun voyage n\'est disponible pour le moment.', 'ajinsafro-traveler-home' ); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<?php
wp_reset_postdata();
get_footer();
