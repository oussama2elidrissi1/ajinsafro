<?php
/**
 * Hero Partial - Tour Header with Background Image
 *
 * @var array $tour Tour data
 * @package AjinsafroTourBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$hero_image = $tour['featured_image']['url'] ?? '';
$hero_alt = $tour['featured_image']['alt'] ?? $tour['title'];
?>

<section class="ajtb-hero" <?php if ($hero_image): ?>style="background-image: url('<?php echo esc_url($hero_image); ?>');"<?php endif; ?>>
    <div class="ajtb-hero-overlay"></div>
    
    <div class="ajtb-hero-content">
        <div class="ajtb-container">
            <!-- Breadcrumbs -->
            <nav class="ajtb-breadcrumbs" aria-label="Fil d'Ariane">
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9,22 9,12 15,12 15,22"></polyline>
                    </svg>
                    Accueil
                </a>
                <span class="sep">/</span>
                <a href="<?php echo esc_url(get_post_type_archive_link(AJTB_POST_TYPE)); ?>">Circuits</a>
                <?php if (!empty($tour['categories'])): ?>
                    <span class="sep">/</span>
                    <a href="<?php echo esc_url($tour['categories'][0]['link']); ?>">
                        <?php echo esc_html($tour['categories'][0]['name']); ?>
                    </a>
                <?php endif; ?>
                <span class="sep">/</span>
                <span class="current"><?php echo esc_html(ajtb_truncate($tour['title'], 40)); ?></span>
            </nav>

            <!-- Title & Location -->
            <div class="ajtb-hero-title-wrap">
                <?php if ($tour['is_featured']): ?>
                    <span class="ajtb-badge featured">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                            <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                        </svg>
                        Populaire
                    </span>
                <?php endif; ?>

                <?php if ($tour['pricing']['has_discount']): ?>
                    <span class="ajtb-badge discount">
                        -<?php echo (int)$tour['pricing']['discount']; ?>%
                    </span>
                <?php endif; ?>

                <h1 class="ajtb-hero-title"><?php echo esc_html($tour['title']); ?></h1>

                <?php if (!empty($tour['address'])): ?>
                    <div class="ajtb-hero-location">
                        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span><?php echo esc_html($tour['address']); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Stats -->
            <div class="ajtb-hero-stats">
                <?php if ($tour['duration_day'] > 0): ?>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo esc_html($tour['duration_day']); ?></span>
                        <span class="stat-label">Jour<?php echo $tour['duration_day'] > 1 ? 's' : ''; ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($tour['rating'] > 0): ?>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo number_format($tour['rating'], 1); ?></span>
                        <span class="stat-label">Note</span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($tour['tour_types'])): ?>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo esc_html($tour['tour_types'][0]['name']); ?></span>
                        <span class="stat-label">Type</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Gallery Thumbnails Preview -->
    <?php if (!empty($tour['gallery']) && count($tour['gallery']) > 1): ?>
        <div class="ajtb-hero-gallery-preview">
            <?php foreach (array_slice($tour['gallery'], 0, 4) as $index => $image): ?>
                <a href="#gallery" class="preview-thumb" data-index="<?php echo $index; ?>">
                    <img src="<?php echo esc_url($image['thumbnail']); ?>" 
                         alt="<?php echo esc_attr($image['alt']); ?>" 
                         loading="lazy">
                    <?php if ($index === 3 && count($tour['gallery']) > 4): ?>
                        <span class="more-count">+<?php echo count($tour['gallery']) - 4; ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Scroll Indicator -->
    <div class="ajtb-hero-scroll">
        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
            <path d="M12 5v14M5 12l7 7 7-7"></path>
        </svg>
    </div>
</section>
