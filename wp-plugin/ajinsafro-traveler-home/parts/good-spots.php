<?php
/**
 * Part: Les bons coins — 2×2 grid
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$spots = ! empty( $settings['good_spots'] ) ? $settings['good_spots'] : array();
$defs  = array(
    array('title'=>'Restaurants','subtitle'=>'Où manger ?','image_url'=>'','link_url'=>'#'),
    array('title'=>'Loisirs','subtitle'=>'Lorem ipsum dolor sit amet, consetetur','image_url'=>'','link_url'=>'#'),
    array('title'=>'Que faire ?','subtitle'=>'Lorem ipsum dolor sit amet, consetetur','image_url'=>'','link_url'=>'#'),
    array('title'=>'Shopping','subtitle'=>'Lorem ipsum dolor sit amet, consetetur','image_url'=>'','link_url'=>'#'),
);
while ( count($spots) < 4 ) $spots[] = $defs[ count($spots) ];
?>

<section class="aj-spots" id="aj-spots">
    <div class="aj-container">
        <h2 class="aj-section-title aj-section-title--green"><?php esc_html_e( 'Les bons coins sur votre destination', 'ajinsafro-traveler-home' ); ?></h2>
        <div class="aj-spots__grid">
            <?php foreach ( array_slice($spots,0,4) as $i => $sp ) :
                $img = ! empty($sp['image_url']) ? $sp['image_url'] : '';
                $t   = ! empty($sp['title'])    ? $sp['title']    : $defs[$i]['title'];
                $sub = ! empty($sp['subtitle']) ? $sp['subtitle'] : $defs[$i]['subtitle'];
                $u   = ! empty($sp['link_url'])      ? $sp['link_url']      : '#';
            ?>
            <a href="<?php echo esc_url($u); ?>" class="aj-spot">
                <?php if ($img) : ?><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($t); ?>" loading="lazy" class="aj-spot__img"><?php else : ?><div class="aj-spot__ph"></div><?php endif; ?>
                <div class="aj-spot__ov">
                    <h3 class="aj-spot__t"><?php echo esc_html($t); ?></h3>
                    <p class="aj-spot__s"><?php echo esc_html($sub); ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
