<?php
/**
 * Hero Partial - MakeMyTrip-style: title/meta above image gallery
 * Full-width section constrained to same max-width as page content.
 * 1) Top block: breadcrumb, tour title, meta (duration, tour type)
 * 2) Gallery: 1 main + 4 secondary (desktop), 1+2 (tablet), swipe (mobile)
 *
 * @var array $tour Tour data
 * @package AjinsafroTourBridge
 */

if (!defined('ABSPATH')) {
    exit;
}

$gallery = $tour['gallery'] ?? [];
$hero_gallery = [];
if (!empty($tour['hero_gallery']) && is_array($tour['hero_gallery'])) {
    foreach ($tour['hero_gallery'] as $img) {
        if (count($hero_gallery) >= 5) break;
        $hero_gallery[] = [
            'url'    => $img['url'] ?? '',
            'large'  => $img['large'] ?? $img['url'] ?? '',
            'medium' => $img['medium'] ?? $img['thumbnail'] ?? $img['url'] ?? '',
            'alt'    => $img['alt'] ?? $tour['title'],
        ];
    }
} else {
    $hero_url = $tour['hero_image']['url'] ?? $tour['featured_image']['url'] ?? '';
    $hero_alt = $tour['hero_image']['alt'] ?? $tour['featured_image']['alt'] ?? $tour['title'];
    if ($hero_url) {
        $hero_gallery[] = [
            'url'   => $hero_url,
            'large' => $tour['hero_image']['large'] ?? $tour['hero_image']['url'] ?? $hero_url,
            'medium' => $tour['hero_image']['medium'] ?? $hero_url,
            'alt'   => $hero_alt,
        ];
    }
    $main_url_normalized = $hero_url ? rtrim($hero_url, '/') : '';
    foreach ($gallery as $img) {
        if (count($hero_gallery) >= 5) break;
        $u = isset($img['url']) ? rtrim($img['url'], '/') : '';
        if ($u && $u !== $main_url_normalized) {
            $hero_gallery[] = [
                'url'    => $img['url'],
                'large'  => $img['large'] ?? $img['url'],
                'medium' => $img['medium'] ?? $img['thumbnail'] ?? $img['url'],
                'alt'    => $img['alt'] ?? $tour['title'],
            ];
        }
    }
}

$has_gallery = count($hero_gallery) > 0;
$all_gallery = $hero_gallery;
foreach ($gallery as $img) {
    $u = isset($img['url']) ? $img['url'] : '';
    if ($u && count($all_gallery) < 20) {
        $exists = false;
        foreach ($all_gallery as $a) {
            if (rtrim($a['url'], '/') === rtrim($u, '/')) { $exists = true; break; }
        }
        if (!$exists) {
            $all_gallery[] = [
                'url'    => $img['url'],
                'medium' => $img['medium'] ?? $img['thumbnail'] ?? $img['url'],
                'alt'    => $img['alt'] ?? $tour['title'],
            ];
        }
    }
}
?>

<section class="ajtb-hero ajtb-hero-gallery">
    <div class="aj-wide-container">
        <!-- 1) Top block ABOVE images: breadcrumb, title, meta -->
        <div class="ajtb-hero-top">
            <nav class="ajtb-hero-breadcrumb" aria-label="<?php esc_attr_e('Fil d\'Ariane', 'ajinsafro-tour-bridge'); ?>">
                <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Accueil', 'ajinsafro-tour-bridge'); ?></a>
                <span class="ajtb-hero-breadcrumb-sep">/</span>
                <a href="<?php echo esc_url(get_post_type_archive_link(AJTB_POST_TYPE)); ?>"><?php esc_html_e('Circuits', 'ajinsafro-tour-bridge'); ?></a>
                <?php if (!empty($tour['categories'])): ?>
                    <span class="ajtb-hero-breadcrumb-sep">/</span>
                    <a href="<?php echo esc_url($tour['categories'][0]['link']); ?>"><?php echo esc_html($tour['categories'][0]['name']); ?></a>
                <?php endif; ?>
                <span class="ajtb-hero-breadcrumb-sep">/</span>
                <span class="ajtb-hero-breadcrumb-current"><?php echo esc_html(ajtb_truncate($tour['title'], 50)); ?></span>
            </nav>
            <h1 class="ajtb-hero-title"><?php echo esc_html($tour['title']); ?></h1>
            <div class="ajtb-hero-meta">
                <?php if (!empty($tour['duration_day'])): ?>
                    <span class="ajtb-hero-meta-item"><?php echo esc_html($tour['duration_day']); ?> <?php echo $tour['duration_day'] > 1 ? __('Jours', 'ajinsafro-tour-bridge') : __('Jour', 'ajinsafro-tour-bridge'); ?></span>
                <?php endif; ?>
                <?php if (!empty($tour['tour_types'][0]['name'])): ?>
                    <?php if (!empty($tour['duration_day'])): ?><span class="ajtb-hero-meta-sep">·</span><?php endif; ?>
                    <span class="ajtb-hero-meta-item"><?php echo esc_html($tour['tour_types'][0]['name']); ?></span>
                <?php endif; ?>
                <?php if (!empty($tour['rating'])): ?>
                    <span class="ajtb-hero-meta-sep">·</span>
                    <span class="ajtb-hero-meta-item"><?php echo number_format($tour['rating'], 1); ?>/5</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
