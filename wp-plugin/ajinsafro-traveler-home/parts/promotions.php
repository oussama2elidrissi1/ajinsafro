<?php
/**
 * Part: Destinations de ce mois — Promotional banners
 * 3 colorful gradient promo cards
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! isset( $settings ) || ! is_array( $settings ) ) {
    $settings = array();
}
$promo_settings = isset( $settings['promotions'] ) && is_array( $settings['promotions'] )
    ? $settings['promotions']
    : array();

$section_title = ! empty( $promo_settings['title'] ) ? $promo_settings['title'] : 'Destinations de ce mois';

$default_promos = array(
    array(
        'badge_text'  => 'Profitez',
        'badge_bg'    => '#ef4444',
        'badge_color' => '#fff',
        'title'       => "Cartes de\nfidélités",
        'text'        => "Plus d'espace de voyages pour vous et nos fidèles et plus pour vos nouveaux clients grâce à ce comportement plein d'avantages.",
        'style'       => 'blue',
        'url'         => '#',
    ),
    array(
        'badge_text'  => 'Profitez',
        'badge_bg'    => '#fff',
        'badge_color' => '#f37a1f',
        'title'       => "Programme\nBztam e-Sfar",
        'text'        => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat.',
        'style'       => 'orange',
        'url'         => '#',
    ),
    array(
        'badge_text'  => 'احجز الآن',
        'badge_bg'    => '#ffb300',
        'badge_color' => '#0e3a5a',
        'title'       => 'الحجز بكري',
        'text'        => 'تجمع الان الودائع للمسافرين إلى وجهاتك و تمتع بخصم إضافي، فكلما قمت بالحجز مبكرا كلما زاد الخصم الذي ستحصل عليه.',
        'style'       => 'dark-blue',
        'url'         => '#',
        'rtl'         => true,
    ),
);

$promos = ! empty( $promo_settings['items'] ) && is_array( $promo_settings['items'] )
    ? $promo_settings['items']
    : $default_promos;
if ( empty( $promos ) ) {
    $promos = $default_promos;
}
?>

<section class="aj-promos" id="aj-promos" style="display:block !important;visibility:visible !important;opacity:1 !important;position:relative !important;z-index:100 !important;padding:50px 0 !important;min-height:320px !important;background:#f0f9ff !important;border:3px solid #0083c4 !important;margin-bottom:20px !important;">
    <div class="aj-container" style="max-width:1280px;margin:0 auto;padding:0 16px;">
        <h2 class="aj-section-title" style="font-size:1.75rem;font-weight:700;color:#1f2937;margin-bottom:24px;"><?php echo esc_html( $section_title ); ?></h2>

        <div class="aj-promos__grid" style="display:grid !important;grid-template-columns:repeat(3,1fr);gap:24px;visibility:visible !important;opacity:1 !important;">
            <?php foreach ( $promos as $i => $promo ) :
                $style = ! empty( $promo['style'] ) ? $promo['style'] : 'blue';
                $is_rtl = ! empty( $promo['rtl'] );
                $url = ! empty( $promo['url'] ) ? $promo['url'] : '#';
                
                $bg_gradient = 'linear-gradient(135deg, #42a5f5, #0277bd)';
                if ( $style === 'orange' ) {
                    $bg_gradient = 'linear-gradient(135deg, #ff9800, #ef6c00)';
                } elseif ( $style === 'dark-blue' ) {
                    $bg_gradient = 'linear-gradient(135deg, #0288d1, #01579b)';
                }
            ?>
            <a href="<?php echo esc_url( $url ); ?>" class="aj-promo-card aj-promo-card--<?php echo esc_attr( $style ); ?>" style="display:block !important;visibility:visible !important;opacity:1 !important;position:relative;border-radius:12px;padding:24px;color:#fff;overflow:hidden;height:208px;min-height:208px;box-shadow:0 4px 20px rgba(0,0,0,.08);cursor:pointer;text-decoration:none;background:<?php echo $bg_gradient; ?>;<?php echo $is_rtl ? 'text-align:right;display:flex !important;flex-direction:column;align-items:flex-end;' : ''; ?>">
                <?php if ( ! empty( $promo['badge_text'] ) ) : ?>
                <span class="aj-promo-card__badge" style="display:inline-block;font-size:10px;font-weight:700;padding:4px 12px;border-radius:20px;margin-bottom:12px;background:<?php echo esc_attr( $promo['badge_bg'] ?? '#ef4444' ); ?>;color:<?php echo esc_attr( $promo['badge_color'] ?? '#fff' ); ?>;">
                    <?php echo esc_html( $promo['badge_text'] ); ?>
                </span>
                <?php endif; ?>
                <h3 class="aj-promo-card__title" style="font-size:1.6rem;font-weight:700;line-height:1.2;margin:0 0 8px;color:#fff;"><?php echo nl2br( esc_html( $promo['title'] ?? '' ) ); ?></h3>
                <?php if ( ! empty( $promo['text'] ) ) : ?>
                <p class="aj-promo-card__text" style="font-size:11px;opacity:.9;line-height:1.6;margin:0;max-width:60%;color:#fff;" <?php echo $is_rtl ? 'dir="rtl"' : ''; ?>><?php echo esc_html( $promo['text'] ); ?></p>
                <?php endif; ?>

                <?php if ( $i === 0 ) : ?>
                <div class="aj-promo-card__deco" style="display:flex;gap:0;transform:rotate(-10deg);bottom:-16px;right:-8px;">
                    <div style="width:96px;height:128px;background:#22c55e;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.15);border:2px solid #fff;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1.2rem;transform:rotate(-15deg) translateY(16px);">25%</div>
                    <div style="width:96px;height:128px;background:#f97316;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.15);border:2px solid #fff;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1.2rem;transform:rotate(-5deg);z-index:1;margin-left:-48px;">50%</div>
                    <div style="width:96px;height:128px;background:#60a5fa;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.15);border:2px solid #fff;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1.2rem;transform:rotate(5deg) translateY(-8px);z-index:2;margin-left:-48px;">75%</div>
                </div>
                <?php elseif ( $i === 1 ) : ?>
                <div class="aj-promo-card__deco" style="top:16px;right:16px;bottom:auto;font-size:4rem;opacity:.9;">
                    <i class="fas fa-wallet" style="color:rgba(0,0,0,.15);"></i>
                </div>
                <?php elseif ( $i === 2 ) : ?>
                <div class="aj-promo-card__deco" style="bottom:16px;left:24px;right:auto;font-size:3.5rem;color:#ffb300;opacity:.9;">
                    <i class="fas fa-box-open"></i>
                </div>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Debug: Promotions section rendered at <?php echo date('Y-m-d H:i:s'); ?> with <?php echo count($promos); ?> items -->
