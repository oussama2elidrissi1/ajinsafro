<?php
/**
 * Hero Partial - Gallery multi-images (MakeMyTrip/Booking style)
 * Replaces single background image with grid: 1 main + 3-5 secondary.
 * Data: hero_image (main) + gallery (secondary). Lightbox on click.
 *
 * @var array $tour Tour data
 * @package AjinsafroTourBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$gallery = $tour['gallery'] ?? [];
// Priorité: utiliser hero_gallery si disponible (5 images spécifiques), sinon construire depuis hero_image + gallery
$hero_gallery = [];
if (!empty($tour['hero_gallery']) && is_array($tour['hero_gallery'])) {
    // Utiliser les 5 images spécifiques de la galerie hero (saisies dans le CRUD)
    foreach ($tour['hero_gallery'] as $img) {
        if (count($hero_gallery) >= 5) break;
        $hero_gallery[] = [
            'url'    => $img['url'] ?? '',
            'medium' => $img['medium'] ?? $img['thumbnail'] ?? $img['url'] ?? '',
            'alt'    => $img['alt'] ?? $tour['title'],
        ];
    }
} else {
    // Fallback: construire depuis hero_image + gallery (ancienne logique)
    $hero_url = $tour['hero_image']['url'] ?? $tour['featured_image']['url'] ?? '';
    $hero_alt = $tour['hero_image']['alt'] ?? $tour['featured_image']['alt'] ?? $tour['title'];

    if ($hero_url) {
        $hero_gallery[] = [
            'url'   => $hero_url,
            'medium' => $tour['hero_image']['medium'] ?? $hero_url,
            'alt'   => $hero_alt,
        ];
    }
    $main_url_normalized = $hero_url ? rtrim($hero_url, '/') : '';
    foreach ($gallery as $img) {
        if (count($hero_gallery) >= 5) {
            break;
        }
        $u = isset($img['url']) ? rtrim($img['url'], '/') : '';
        if ($u && $u !== $main_url_normalized) {
            $hero_gallery[] = [
                'url'    => $img['url'],
                'medium' => $img['medium'] ?? $img['thumbnail'] ?? $img['url'],
                'alt'    => $img['alt'] ?? $tour['title'],
            ];
        }
    }
}

$has_gallery = count($hero_gallery) > 0;
$total_photos = count($hero_gallery) + (count($gallery) > count($hero_gallery) ? count($gallery) - count($hero_gallery) : 0);
$all_gallery = $hero_gallery;
foreach ($gallery as $img) {
    $u = isset($img['url']) ? $img['url'] : '';
    if ($u && count($all_gallery) < 20) {
        $exists = false;
        foreach ($all_gallery as $a) {
            if (rtrim($a['url'], '/') === rtrim($u, '/')) {
                $exists = true;
                break;
            }
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
    <?php if ($has_gallery): ?>
        <!-- Desktop: grid 1 main + 4 secondary (exactly 5 images) -->
        <div class="aj-wide-container">
            <div class="ajtb-hero-gallery-grid" role="region" aria-label="<?php esc_attr_e('Galerie du voyage', 'ajinsafro-tour-bridge'); ?>">
                <?php
                $main = $hero_gallery[0];
                $secondary = array_slice($hero_gallery, 1, 4); // Exactly 4 secondary images
                ?>
            <div class="ajtb-hero-gallery-main">
                <a href="<?php echo esc_url($main['url']); ?>" class="ajtb-hero-gallery-item" data-lightbox="tour-hero-gallery" data-index="0">
                    <img src="<?php echo esc_url($main['medium']); ?>" alt="<?php echo esc_attr($main['alt']); ?>" loading="eager">
                </a>
                <?php if (count($all_gallery) > 5): ?>
                    <a href="#gallery" class="ajtb-hero-gallery-all-btn"><?php esc_html_e('Voir toutes les photos', 'ajinsafro-tour-bridge'); ?></a>
                <?php endif; ?>
            </div>
            <div class="ajtb-hero-gallery-secondary">
                <?php 
                // Always show exactly 4 secondary images (or less if not available)
                // If we have more than 5 total images, replace the 4th with "more" cell
                $show_more = count($all_gallery) > 5;
                $secondary_to_show = $show_more ? 3 : min(4, count($secondary));
                
                foreach ($secondary as $i => $img): 
                    if ($i < $secondary_to_show):
                ?>
                    <a href="<?php echo esc_url($img['url']); ?>" class="ajtb-hero-gallery-item" data-lightbox="tour-hero-gallery" data-index="<?php echo $i + 1; ?>">
                        <img src="<?php echo esc_url($img['medium']); ?>" alt="<?php echo esc_attr($img['alt']); ?>" loading="lazy">
                    </a>
                <?php 
                    endif;
                endforeach; 
                
                // Show "more" cell if we have more than 5 images total
                if ($show_more): 
                ?>
                    <a href="#gallery" class="ajtb-hero-gallery-item ajtb-hero-gallery-more">
                        <span class="ajtb-hero-gallery-more-count">+<?php echo count($all_gallery) - 5; ?></span>
                        <span class="ajtb-hero-gallery-more-label"><?php esc_html_e('Voir toutes les photos', 'ajinsafro-tour-bridge'); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        </div>

        <!-- Mobile: slider -->
        <div class="aj-wide-container">
            <div class="ajtb-hero-gallery-slider" aria-hidden="true">
                <div class="ajtb-hero-gallery-slider-track">
                    <?php foreach ($hero_gallery as $i => $img): ?>
                        <a href="<?php echo esc_url($img['url']); ?>" class="ajtb-hero-gallery-slide" data-lightbox="tour-hero-gallery" data-index="<?php echo $i; ?>">
                            <img src="<?php echo esc_url($img['medium']); ?>" alt="<?php echo esc_attr($img['alt']); ?>" loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>">
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
        <div class="aj-wide-container">
            <div class="ajtb-hero-gallery-placeholder">
                <span class="ajtb-hero-gallery-placeholder-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="64" height="64" stroke="currentColor" fill="none" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                </span>
                <span><?php esc_html_e('Aucune image', 'ajinsafro-tour-bridge'); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Title bar (below gallery) -->
    <div class="ajtb-hero-content">
        <div class="aj-wide-container">
            <nav class="ajtb-breadcrumbs" aria-label="<?php esc_attr_e('Fil d\'Ariane', 'ajinsafro-tour-bridge'); ?>">
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9,22 9,12 15,12 15,22"></polyline></svg>
                    <?php esc_html_e('Accueil', 'ajinsafro-tour-bridge'); ?>
                </a>
                <span class="sep">/</span>
                <a href="<?php echo esc_url(get_post_type_archive_link(AJTB_POST_TYPE)); ?>"><?php esc_html_e('Circuits', 'ajinsafro-tour-bridge'); ?></a>
                <?php if (!empty($tour['categories'])): ?>
                    <span class="sep">/</span>
                    <a href="<?php echo esc_url($tour['categories'][0]['link']); ?>"><?php echo esc_html($tour['categories'][0]['name']); ?></a>
                <?php endif; ?>
                <span class="sep">/</span>
                <span class="current"><?php echo esc_html(ajtb_truncate($tour['title'], 40)); ?></span>
            </nav>

            <div class="ajtb-hero-title-wrap">
                <?php if (!empty($tour['is_featured'])): ?>
                    <span class="ajtb-badge featured">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon></svg>
                        <?php esc_html_e('Populaire', 'ajinsafro-tour-bridge'); ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($tour['pricing']['has_discount'])): ?>
                    <span class="ajtb-badge discount">-<?php echo (int) $tour['pricing']['discount']; ?>%</span>
                <?php endif; ?>
                <h1 class="ajtb-hero-title"><?php echo esc_html($tour['title']); ?></h1>
                <?php if (!empty($tour['address'])): ?>
                    <div class="ajtb-hero-location">
                        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <span><?php echo esc_html($tour['address']); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="ajtb-hero-stats">
                <?php if (!empty($tour['duration_day'])): ?>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo esc_html($tour['duration_day']); ?></span>
                        <span class="stat-label"><?php echo $tour['duration_day'] > 1 ? __('Jours', 'ajinsafro-tour-bridge') : __('Jour', 'ajinsafro-tour-bridge'); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($tour['rating'])): ?>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo number_format($tour['rating'], 1); ?></span>
                        <span class="stat-label"><?php esc_html_e('Note', 'ajinsafro-tour-bridge'); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($tour['tour_types'][0]['name'])): ?>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo esc_html($tour['tour_types'][0]['name']); ?></span>
                        <span class="stat-label"><?php esc_html_e('Type', 'ajinsafro-tour-bridge'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
