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

        <!-- 2) Image gallery -->
        <?php if ($has_gallery): ?>
            <div class="ajtb-hero-gallery-wrap">
                <!-- Desktop: 1 main + 4 secondary -->
                <div class="ajtb-hero-gallery-grid" role="region" aria-label="<?php esc_attr_e('Galerie du voyage', 'ajinsafro-tour-bridge'); ?>">
                    <?php
                    $main = $hero_gallery[0];
                    $secondary = array_slice($hero_gallery, 1, 4);
                    ?>
                    <div class="ajtb-hero-gallery-main">
                        <a href="<?php echo esc_url($main['url']); ?>" class="ajtb-hero-gallery-item" data-lightbox="tour-hero-gallery" data-index="0">
                            <img src="<?php echo esc_url($main['large'] ?: $main['url']); ?>" 
                                 srcset="<?php echo esc_url($main['large'] ?: $main['url']); ?> 1200w, <?php echo esc_url($main['medium'] ?: $main['url']); ?> 800w" 
                                 sizes="(max-width: 768px) 100vw, (max-width: 1200px) 60vw, 50vw"
                                 alt="<?php echo esc_attr($main['alt']); ?>" 
                                 loading="eager">
                        </a>
                        <?php if (count($all_gallery) > 5): ?>
                            <a href="#gallery" class="ajtb-hero-gallery-all-btn"><?php esc_html_e('Voir toutes les photos', 'ajinsafro-tour-bridge'); ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="ajtb-hero-gallery-secondary">
                        <?php
                        $show_more = count($all_gallery) > 5;
                        $secondary_to_show = $show_more ? 3 : min(4, count($secondary));
                        $cell_index = 0;
                        for ($i = 0; $i < $secondary_to_show && isset($secondary[$i]); $i++):
                            $img = $secondary[$i];
                            $cell_index++;
                        ?>
                            <a href="<?php echo esc_url($img['url']); ?>" class="ajtb-hero-gallery-item" data-lightbox="tour-hero-gallery" data-index="<?php echo $i + 1; ?>">
                                <img src="<?php echo esc_url($img['large'] ?: $img['url']); ?>" 
                                     srcset="<?php echo esc_url($img['large'] ?: $img['url']); ?> 800w, <?php echo esc_url($img['medium'] ?: $img['url']); ?> 400w" 
                                     sizes="(max-width: 768px) 50vw, 25vw"
                                     alt="<?php echo esc_attr($img['alt']); ?>" 
                                     loading="lazy">
                            </a>
                        <?php endfor; ?>
                        <?php if ($show_more): $cell_index++; ?>
                            <a href="#gallery" class="ajtb-hero-gallery-item ajtb-hero-gallery-more">
                                <span class="ajtb-hero-gallery-more-count">+<?php echo count($all_gallery) - 5; ?></span>
                                <span class="ajtb-hero-gallery-more-label"><?php esc_html_e('Voir toutes les photos', 'ajinsafro-tour-bridge'); ?></span>
                            </a>
                        <?php endif; ?>
                        <?php while ($cell_index < 4): $cell_index++; ?>
                            <span class="ajtb-hero-gallery-fill" aria-hidden="true"></span>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Tablet: 1 main + 2 secondary -->
                <div class="ajtb-hero-gallery-tablet" role="region" aria-label="<?php esc_attr_e('Galerie du voyage', 'ajinsafro-tour-bridge'); ?>">
                    <?php
                    $main = $hero_gallery[0];
                    $secondary_tablet = array_slice($hero_gallery, 1, 2);
                    ?>
                    <div class="ajtb-hero-gallery-main">
                        <a href="<?php echo esc_url($main['url']); ?>" class="ajtb-hero-gallery-item" data-lightbox="tour-hero-gallery" data-index="0">
                            <img src="<?php echo esc_url($main['large'] ?: $main['url']); ?>" 
                                 srcset="<?php echo esc_url($main['large'] ?: $main['url']); ?> 1200w, <?php echo esc_url($main['medium'] ?: $main['url']); ?> 800w" 
                                 sizes="(max-width: 768px) 100vw, 70vw"
                                 alt="<?php echo esc_attr($main['alt']); ?>" 
                                 loading="eager">
                        </a>
                        <?php if (count($all_gallery) > 3): ?>
                            <a href="#gallery" class="ajtb-hero-gallery-all-btn"><?php esc_html_e('Voir toutes les photos', 'ajinsafro-tour-bridge'); ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="ajtb-hero-gallery-secondary ajtb-hero-gallery-secondary--tablet">
                        <?php foreach ($secondary_tablet as $i => $img): ?>
                            <a href="<?php echo esc_url($img['url']); ?>" class="ajtb-hero-gallery-item" data-lightbox="tour-hero-gallery" data-index="<?php echo $i + 1; ?>">
                                <img src="<?php echo esc_url($img['large'] ?: $img['url']); ?>" 
                                     srcset="<?php echo esc_url($img['large'] ?: $img['url']); ?> 800w, <?php echo esc_url($img['medium'] ?: $img['url']); ?> 400w" 
                                     sizes="(max-width: 768px) 50vw, 35vw"
                                     alt="<?php echo esc_attr($img['alt']); ?>" 
                                     loading="lazy">
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Mobile: swipe slider -->
                <div class="ajtb-hero-gallery-slider" aria-hidden="true">
                    <div class="ajtb-hero-gallery-slider-track">
                        <?php foreach ($hero_gallery as $i => $img): ?>
                            <a href="<?php echo esc_url($img['url']); ?>" class="ajtb-hero-gallery-slide" data-lightbox="tour-hero-gallery" data-index="<?php echo $i; ?>">
                                <img src="<?php echo esc_url($img['large'] ?: $img['url']); ?>" 
                                     srcset="<?php echo esc_url($img['large'] ?: $img['url']); ?> 1200w, <?php echo esc_url($img['medium'] ?: $img['url']); ?> 800w" 
                                     sizes="100vw"
                                     alt="<?php echo esc_attr($img['alt']); ?>" 
                                     loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>">
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="ajtb-hero-gallery-slider-prev" aria-label="<?php esc_attr_e('Précédent', 'ajinsafro-tour-bridge'); ?>"></button>
                    <button type="button" class="ajtb-hero-gallery-slider-next" aria-label="<?php esc_attr_e('Suivant', 'ajinsafro-tour-bridge'); ?>"></button>
                    <div class="ajtb-hero-gallery-slider-dots"></div>
                    <?php if (count($all_gallery) > 5): ?>
                        <a href="#gallery" class="ajtb-hero-gallery-all-btn"><?php esc_html_e('Voir toutes les photos', 'ajinsafro-tour-bridge'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="ajtb-hero-gallery-placeholder">
                <span class="ajtb-hero-gallery-placeholder-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="64" height="64" stroke="currentColor" fill="none" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                </span>
                <span><?php esc_html_e('Aucune image', 'ajinsafro-tour-bridge'); ?></span>
            </div>
        <?php endif; ?>
    </div>
</section>
