<?php
/**
 * Part: Search bar (tabs + form)
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$tabs = array(
    'voyage'      => 'Voyage',
    'hebergement' => 'Hébergement',
    'activites'   => 'Activité',
    'location'    => 'Location de vacances',
    'transport'   => 'Transport',
    'guide'       => 'Votre guide',
);

$search_shortcode = ! empty( $settings['search']['shortcode'] )
    ? $settings['search']['shortcode']
    : '[traveler_search]';

$shortcode_tag = 'traveler_search';
if ( preg_match( '/\[([a-zA-Z0-9_\-:]+)/', $search_shortcode, $m ) ) {
    $shortcode_tag = $m[1];
}
?>

<?php if ( shortcode_exists( $shortcode_tag ) && ! empty( $search_shortcode ) ) : ?>
    <div class="aj-search-native"><?php echo do_shortcode( $search_shortcode ); ?></div>
<?php else : ?>
<div class="aj-search-card">
    <div class="aj-search__tabs">
        <?php $first = true; foreach ( $tabs as $k => $label ) : ?>
            <button type="button" class="aj-tab<?php echo $first ? ' aj-tab--on' : ''; ?>" data-tab="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></button>
        <?php $first = false; endforeach; ?>
    </div>
    <form class="aj-search__form" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <input type="hidden" name="s" value="">
        <input type="hidden" name="post_type" value="st_tours">

        <div class="aj-sf">
            <span class="aj-sf__ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1a73a7" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
            <div class="aj-sf__txt"><small>Destination / Hôtel</small><input type="text" name="location_name" placeholder="Où voulez vous partir ?"></div>
        </div>

        <i class="aj-sep"></i>

        <div class="aj-sf">
            <span class="aj-sf__ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1a73a7" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
            <div class="aj-sf__txt"><small>Arrivée – Départ</small><input type="text" name="start" placeholder="jj/mm/aaaa – jj/mm/aaaa"></div>
        </div>

        <i class="aj-sep"></i>

        <div class="aj-sf">
            <span class="aj-sf__ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1a73a7" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
            <div class="aj-sf__txt"><small>Voyageurs</small><input type="text" name="adults" placeholder="1 Adulte · 0 Enfant · 0 Bébé" readonly></div>
        </div>

        <i class="aj-sep"></i>

        <div class="aj-sf aj-sf--more">
            <button type="button" class="aj-more-btn">Plus +</button>
        </div>

        <button type="submit" class="aj-search__btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Rechercher
        </button>
    </form>
</div>
<?php endif; ?>
