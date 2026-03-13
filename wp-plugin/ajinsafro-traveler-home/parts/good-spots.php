<?php
/**
 * Part: Les bons coins sur votre destination — 4-column grid with FA icons
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$spots = ! empty( $settings['good_spots'] ) ? $settings['good_spots'] : array();
$defs  = array(
    array( 'title' => 'Restaurants', 'subtitle' => 'Où manger ?', 'icon' => 'fas fa-utensils', 'image_url' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=800&q=80', 'link_url' => '#' ),
    array( 'title' => 'Loisirs', 'subtitle' => 'Lorem ipsum dolor sit amet', 'icon' => 'fas fa-icons', 'image_url' => 'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?auto=format&fit=crop&w=800&q=80', 'link_url' => '#' ),
    array( 'title' => 'Que faire ?', 'subtitle' => 'Lorem ipsum dolor sit amet', 'icon' => 'fas fa-map-marked-alt', 'image_url' => 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=800&q=80', 'link_url' => '#' ),
    array( 'title' => 'Shopping', 'subtitle' => 'Lorem ipsum dolor sit amet', 'icon' => 'fas fa-shopping-bag', 'image_url' => 'https://images.unsplash.com/photo-1481437156560-3205f6a55735?auto=format&fit=crop&w=800&q=80', 'link_url' => '#' ),
);
while ( count( $spots ) < 4 ) {
    $spots[] = $defs[ count( $spots ) ];
}

$spot_icon_map = array(
    'restaurants' => 'fas fa-utensils',
    'loisirs'     => 'fas fa-icons',
    'que faire ?' => 'fas fa-map-marked-alt',
    'shopping'    => 'fas fa-shopping-bag',
);

$section_title = ! empty( $settings['good_spots_title'] )
    ? $settings['good_spots_title']
    : 'Les bons coins sur votre destination';
?>

<section class="aj-spots" id="aj-spots">
    <div class="aj-container">
        <h2 class="aj-section-title" style="text-transform:uppercase;letter-spacing:-.3px;"><?php echo esc_html( $section_title ); ?></h2>
        <div class="aj-spots2__grid">
            <?php foreach ( array_slice( $spots, 0, 4 ) as $i => $sp ) :
                $img = ! empty( $sp['image_url'] ) ? $sp['image_url'] : ( $defs[ $i ]['image_url'] ?? '' );
                $t   = ! empty( $sp['title'] ) ? $sp['title'] : $defs[ $i ]['title'];
                $sub = ! empty( $sp['subtitle'] ) ? $sp['subtitle'] : ( $defs[ $i ]['subtitle'] ?? '' );
                $u   = ! empty( $sp['link_url'] ) ? $sp['link_url'] : '#';
                $icon = ! empty( $sp['icon'] ) ? $sp['icon'] : '';
                if ( empty( $icon ) ) {
                    $key = mb_strtolower( trim( $t ), 'UTF-8' );
                    $icon = isset( $spot_icon_map[ $key ] ) ? $spot_icon_map[ $key ] : ( $defs[ $i ]['icon'] ?? 'fas fa-compass' );
                }
            ?>
            <a href="<?php echo esc_url( $u ); ?>" class="aj-spot2 aj-hover-glass">
                <?php if ( $img ) : ?>
                    <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $t ); ?>" loading="lazy">
                <?php else : ?>
                    <div style="width:100%;height:100%;background:linear-gradient(135deg,#d4a574,#a67c52);"></div>
                <?php endif; ?>
                <div class="aj-spot2__overlay">
                    <i class="<?php echo esc_attr( $icon ); ?> aj-spot2__icon"></i>
                    <h3 class="aj-spot2__title"><?php echo esc_html( $t ); ?></h3>
                    <p class="aj-spot2__subtitle"><?php echo esc_html( $sub ); ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
