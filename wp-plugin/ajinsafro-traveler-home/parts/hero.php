<?php
/**
 * Part: Hero + floating search bar
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$hero = isset( $settings['hero'] ) && is_array( $settings['hero'] ) ? $settings['hero'] : array();

$hero_type = ! empty( $hero['type'] ) ? $hero['type'] : 'image';
$hero_image_url = ! empty( $hero['image_url'] ) ? $hero['image_url'] : '';
$hero_video_url = ! empty( $hero['video_url'] ) ? $hero['video_url'] : '';

if ( function_exists( 'ajth_normalize_storage_url' ) ) {
    $hero_image_url = ajth_normalize_storage_url( $hero_image_url );
    $hero_video_url = ajth_normalize_storage_url( $hero_video_url );
}
$hero_title = ! empty( $hero['title'] ) ? $hero['title'] : 'Partir en vacances au meilleur prix !';
$hero_subtitle = ! empty( $hero['subtitle'] ) ? $hero['subtitle'] : '';
$hero_cta_text = ! empty( $hero['cta_text'] ) ? $hero['cta_text'] : '';
$hero_cta_url = ! empty( $hero['cta_url'] ) ? $hero['cta_url'] : '';
$hero_overlay = isset( $hero['overlay'] ) ? max( 0, min( 1, floatval( $hero['overlay'] ) ) ) : 0.35;

$default_hero_image_url = function_exists( 'get_header_image' ) ? (string) get_header_image() : '';
$fallback_hero_image = 'https://images.unsplash.com/photo-1514890547357-a9ee288728e0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80';
$hero_image_source = $hero_image_url !== '' ? $hero_image_url : ( $default_hero_image_url !== '' ? $default_hero_image_url : $fallback_hero_image );
$hero_mode = ( $hero_type === 'video' && $hero_video_url !== '' ) ? 'video' : 'image';

$bg = $hero_image_source
    ? 'background-image:url(' . esc_url( $hero_image_source ) . ');'
    : 'background:linear-gradient(135deg,#0e3a5a 0%,#0083c4 100%);';

$is_mp4_video = ! empty( $hero_video_url ) && preg_match( '/\.mp4(\?.*)?$/i', $hero_video_url );

$embed_video_url = '';
if ( ! empty( $hero_video_url ) && ! $is_mp4_video ) {
    if ( preg_match( '~(?:youtube\.com/watch\?v=|youtu\.be/)([^&?/]+)~i', $hero_video_url, $m ) ) {
        $embed_video_url = 'https://www.youtube.com/embed/' . $m[1] . '?autoplay=1&mute=1&loop=1&controls=0&playlist=' . $m[1] . '&rel=0';
    } elseif ( preg_match( '~vimeo\.com/(\d+)~i', $hero_video_url, $m ) ) {
        $embed_video_url = 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1&muted=1&loop=1&background=1';
    }
}
?>

<section class="aj-hero <?php echo $hero_mode === 'video' ? 'aj-hero--video' : 'aj-hero--image'; ?>" style="<?php echo esc_attr( $hero_mode === 'image' ? $bg : '' ); ?>">
    <?php if ( $hero_mode === 'video' && $hero_video_url ) : ?>
        <div class="aj-hero__media" aria-hidden="true">
            <?php if ( $is_mp4_video ) : ?>
                <video class="aj-hero__video" autoplay muted loop playsinline>
                    <source src="<?php echo esc_url( $hero_video_url ); ?>" type="video/mp4">
                </video>
            <?php elseif ( $embed_video_url ) : ?>
                <iframe class="aj-hero__iframe" src="<?php echo esc_url( $embed_video_url ); ?>" frameborder="0" allow="autoplay; fullscreen; picture-in-picture"></iframe>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="aj-hero__overlay" style="background: rgba(0,0,0,<?php echo esc_attr( $hero_overlay ); ?>);"></div>
    <div class="aj-hero__center">
        <h1 class="aj-hero__title"><?php echo esc_html( $hero_title ); ?></h1>
        <?php if ( $hero_subtitle ) : ?>
            <p class="aj-hero__sub"><?php echo esc_html( $hero_subtitle ); ?></p>
        <?php endif; ?>
        <?php if ( $hero_cta_text && $hero_cta_url ) : ?>
            <p class="aj-hero__cta-wrap">
                <a class="aj-hero__cta" href="<?php echo esc_url( $hero_cta_url ); ?>"><?php echo esc_html( $hero_cta_text ); ?></a>
            </p>
        <?php endif; ?>
    </div>
</section>

<?php if ( ! empty( $settings['sections']['search'] ) ) : ?>
    <div class="aj-search-float">
        <?php include AJTH_DIR . 'parts/search.php'; ?>
    </div>
<?php endif; ?>
