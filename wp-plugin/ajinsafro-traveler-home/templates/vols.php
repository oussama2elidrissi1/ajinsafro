<?php
/**
 * Vols (Flights) Page Template
 *
 * Displays all available flights from custom tables.
 *
 * @package AjinsafroTravelerHome
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

global $wpdb;
$settings = ajth_get_settings();

$paged = max( 1, absint( get_query_var( 'paged' ) ), absint( get_query_var( 'page' ) ) );
$per_page = 12;
$offset = ( $paged - 1 ) * $per_page;

// Tables
$tour_flights_table = $wpdb->prefix . 'aj_tour_flights';
$legacy_flights_table = $wpdb->prefix . 'aj_travel_departure_flights';

// Search params
$departure = isset( $_GET['departure'] ) ? sanitize_text_field( wp_unslash( $_GET['departure'] ) ) : '';
$arrival = isset( $_GET['arrival'] ) ? sanitize_text_field( wp_unslash( $_GET['arrival'] ) ) : '';
$is_search = ! empty( $departure ) || ! empty( $arrival );

// Build query
$where_clauses = array();
$query_params = array();

if ( $departure ) {
    $where_clauses[] = 'from_city LIKE %s';
    $query_params[] = '%' . $wpdb->esc_like( $departure ) . '%';
}

if ( $arrival ) {
    $where_clauses[] = 'to_city LIKE %s';
    $query_params[] = '%' . $wpdb->esc_like( $arrival ) . '%';
}

$where_sql = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

// Check if table exists
$table_exists = $wpdb->get_var( $wpdb->prepare(
    "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s",
    $tour_flights_table
) );

$flights = array();
$total_flights = 0;

if ( $table_exists ) {
    // Get total count
    $count_query = "SELECT COUNT(*) FROM {$tour_flights_table} {$where_sql}";
    if ( ! empty( $query_params ) ) {
        $total_flights = $wpdb->get_var( $wpdb->prepare( $count_query, $query_params ) );
    } else {
        $total_flights = $wpdb->get_var( $count_query );
    }

    // Get flights
    $flights_query = "SELECT * FROM {$tour_flights_table} {$where_sql} ORDER BY depart_date DESC, depart_time DESC LIMIT %d OFFSET %d";
    $query_params[] = $per_page;
    $query_params[] = $offset;
    
    if ( ! empty( $query_params ) ) {
        $flights = $wpdb->get_results( $wpdb->prepare( $flights_query, $query_params ) );
    }
}

$total_pages = ceil( $total_flights / $per_page );
?>

<div class="aj-home-wrap">
    <div id="aj-home" class="aj-home aj-vols-page">
        <?php if ( ! $is_search ) : ?>
        <section class="aj-vols-hero">
            <div class="aj-container">
                <h1 class="aj-vols-title"><?php esc_html_e( 'Tous les vols disponibles', 'ajinsafro-traveler-home' ); ?></h1>
                <p class="aj-vols-subtitle"><?php esc_html_e( 'Recherchez et réservez votre billet d\'avion rapidement.', 'ajinsafro-traveler-home' ); ?></p>
                <div class="aj-vols-search">
                    <form method="get" class="aj-vols-search-form">
                        <div class="aj-vols-search-fields">
                            <div class="aj-vols-search-field">
                                <i class="fas fa-plane-departure"></i>
                                <input type="text" name="departure" value="<?php echo esc_attr( $departure ); ?>" placeholder="Ville de départ" class="aj-vols-search-input">
                            </div>
                            <div class="aj-vols-search-field">
                                <i class="fas fa-plane-arrival"></i>
                                <input type="text" name="arrival" value="<?php echo esc_attr( $arrival ); ?>" placeholder="Ville d'arrivée" class="aj-vols-search-input">
                            </div>
                            <button type="submit" class="aj-vols-search-btn">
                                <i class="fas fa-search"></i> Rechercher
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <section class="aj-vols-results">
            <div class="aj-container">
                <?php if ( $is_search ) : ?>
                    <div class="aj-vols-search-header">
                        <h1 class="aj-vols-title"><?php esc_html_e( 'Résultats de recherche', 'ajinsafro-traveler-home' ); ?></h1>
                        <?php if ( $departure || $arrival ) : ?>
                            <p class="aj-vols-subtitle">
                                <?php 
                                if ( $departure && $arrival ) {
                                    printf( esc_html__( 'De %s vers %s', 'ajinsafro-traveler-home' ), '<strong>' . esc_html( $departure ) . '</strong>', '<strong>' . esc_html( $arrival ) . '</strong>' );
                                } elseif ( $departure ) {
                                    printf( esc_html__( 'Au départ de : %s', 'ajinsafro-traveler-home' ), '<strong>' . esc_html( $departure ) . '</strong>' );
                                } else {
                                    printf( esc_html__( 'À destination de : %s', 'ajinsafro-traveler-home' ), '<strong>' . esc_html( $arrival ) . '</strong>' );
                                }
                                ?>
                            </p>
                        <?php endif; ?>
                        <div class="aj-vols-search">
                            <form method="get" class="aj-vols-search-form">
                                <div class="aj-vols-search-fields">
                                    <div class="aj-vols-search-field">
                                        <i class="fas fa-plane-departure"></i>
                                        <input type="text" name="departure" value="<?php echo esc_attr( $departure ); ?>" placeholder="Ville de départ" class="aj-vols-search-input">
                                    </div>
                                    <div class="aj-vols-search-field">
                                        <i class="fas fa-plane-arrival"></i>
                                        <input type="text" name="arrival" value="<?php echo esc_attr( $arrival ); ?>" placeholder="Ville d'arrivée" class="aj-vols-search-input">
                                    </div>
                                    <button type="submit" class="aj-vols-search-btn">
                                        <i class="fas fa-search"></i> Rechercher
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="aj-vols-results__head">
                    <h2 class="aj-section-title"><?php echo $is_search ? esc_html__( 'Vols trouvés', 'ajinsafro-traveler-home' ) : esc_html__( 'Liste des vols', 'ajinsafro-traveler-home' ); ?></h2>
                    <p class="aj-vols-results__count">
                        <?php
                        printf(
                            esc_html( _n( '%d vol', '%d vols', intval( $total_flights ), 'ajinsafro-traveler-home' ) ),
                            intval( $total_flights )
                        );
                        ?>
                    </p>
                </div>

                <?php if ( ! empty( $flights ) ) : ?>
                    <div class="aj-vols-list">
                        <?php foreach ( $flights as $flight ) : ?>
                            <div class="aj-flight-card">
                                <div class="aj-flight-card__header">
                                    <span class="aj-flight-card__type <?php echo esc_attr( strtolower( $flight->flight_type ) ); ?>">
                                        <?php echo esc_html( $flight->flight_type ); ?>
                                    </span>
                                    <?php if ( ! empty( $flight->flight_number ) ) : ?>
                                        <span class="aj-flight-card__number"><?php echo esc_html( $flight->flight_number ); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="aj-flight-card__route">
                                    <div class="aj-flight-card__city">
                                        <i class="fas fa-plane-departure"></i>
                                        <strong><?php echo esc_html( $flight->from_city ); ?></strong>
                                        <?php if ( ! empty( $flight->depart_date ) ) : ?>
                                            <div class="aj-flight-card__datetime">
                                                <?php echo esc_html( date( 'd/m/Y', strtotime( $flight->depart_date ) ) ); ?>
                                                <?php if ( ! empty( $flight->depart_time ) ) : ?>
                                                    - <?php echo esc_html( $flight->depart_time ); ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="aj-flight-card__arrow">
                                        <i class="fas fa-long-arrow-alt-right"></i>
                                    </div>
                                    
                                    <div class="aj-flight-card__city">
                                        <i class="fas fa-plane-arrival"></i>
                                        <strong><?php echo esc_html( $flight->to_city ); ?></strong>
                                        <?php if ( ! empty( $flight->arrive_date ) ) : ?>
                                            <div class="aj-flight-card__datetime">
                                                <?php echo esc_html( date( 'd/m/Y', strtotime( $flight->arrive_date ) ) ); ?>
                                                <?php if ( ! empty( $flight->arrive_time ) ) : ?>
                                                    - <?php echo esc_html( $flight->arrive_time ); ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ( $total_pages > 1 ) :
                        $pagination = paginate_links( array(
                            'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
                            'format'    => '?paged=%#%',
                            'current'   => $paged,
                            'total'     => $total_pages,
                            'type'      => 'array',
                            'prev_text' => '«',
                            'next_text' => '»',
                            'add_args'  => array(
                                'departure' => $departure,
                                'arrival'   => $arrival,
                            ),
                        ) );

                        if ( ! empty( $pagination ) ) :
                        ?>
                            <nav class="aj-vols-pagination" aria-label="Pagination vols">
                                <?php foreach ( $pagination as $page_link ) : ?>
                                    <?php echo wp_kses_post( $page_link ); ?>
                                <?php endforeach; ?>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>

                <?php else : ?>
                    <div class="aj-vols-empty">
                        <i class="fas fa-plane" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;"></i>
                        <p style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">
                            <?php esc_html_e( 'Aucun vol trouvé', 'ajinsafro-traveler-home' ); ?>
                        </p>
                        <p style="color: #94a3b8;">
                            <?php echo $is_search ? esc_html__( 'Essayez avec d\'autres critères de recherche.', 'ajinsafro-traveler-home' ) : esc_html__( 'Aucun vol n\'est disponible pour le moment.', 'ajinsafro-traveler-home' ); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<?php
get_footer();
