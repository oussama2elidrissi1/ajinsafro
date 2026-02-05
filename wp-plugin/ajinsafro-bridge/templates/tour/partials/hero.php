<?php
/**
 * Hero Partial
 *
 * Displays the hero section with featured image for single tour.
 *
 * @var array $tourData Full tour data
 * @package AjinsafroBridge\Templates\Partials
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$wp = $tourData['wp'];
$heroImage = $wp['featured_image']['url'] ?? '';
$heroImageLarge = $wp['featured_image']['sizes']['large'] ?? $heroImage;
?>

<div class="ajbridge-hero" <?php if ($heroImage): ?>style="background-image: url('<?php echo esc_url($heroImageLarge); ?>');"<?php endif; ?>>
    <div class="ajbridge-hero-overlay"></div>
    
    <div class="ajbridge-hero-content">
        <div class="ajbridge-container">
            <!-- Breadcrumbs -->
            <nav class="ajbridge-breadcrumbs" aria-label="Fil d'Ariane">
                <a href="<?php echo esc_url(home_url('/')); ?>">Accueil</a>
                <span class="separator">/</span>
                <a href="<?php echo esc_url(get_post_type_archive_link(AJBRIDGE_POST_TYPE)); ?>">Circuits</a>
                <span class="separator">/</span>
                <?php if (!empty($wp['categories'])): ?>
                    <a href="<?php echo esc_url($wp['categories'][0]['link']); ?>">
                        <?php echo esc_html($wp['categories'][0]['name']); ?>
                    </a>
                    <span class="separator">/</span>
                <?php endif; ?>
                <span class="current"><?php echo esc_html($wp['title']); ?></span>
            </nav>

            <!-- Quick Info Badges -->
            <div class="ajbridge-hero-badges">
                <?php if ($wp['is_featured']): ?>
                    <span class="hero-badge featured">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                            <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                        </svg>
                        Populaire
                    </span>
                <?php endif; ?>

                <?php if ($tourData['pricing']['base']['has_discount']): ?>
                    <span class="hero-badge discount">
                        -<?php echo (int)$tourData['pricing']['base']['discount']; ?>% Promo
                    </span>
                <?php endif; ?>

                <?php if ($wp['duration_day'] > 0): ?>
                    <span class="hero-badge duration">
                        <?php echo $wp['duration_day']; ?> <?php echo $wp['duration_day'] > 1 ? 'jours' : 'jour'; ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Hero Gallery Thumbnails (optional quick preview) -->
    <?php if ($tourData['has_gallery'] && count($wp['gallery']) > 1): ?>
        <div class="ajbridge-hero-thumbnails">
            <div class="thumbnails-inner">
                <?php foreach (array_slice($wp['gallery'], 0, 4) as $index => $image): ?>
                    <a href="<?php echo esc_url($image['url']); ?>" 
                       class="hero-thumb" 
                       data-lightbox="tour-gallery"
                       data-index="<?php echo $index; ?>">
                        <img src="<?php echo esc_url($image['thumbnail']); ?>" 
                             alt="<?php echo esc_attr($image['alt']); ?>"
                             loading="lazy">
                        <?php if ($index === 3 && count($wp['gallery']) > 4): ?>
                            <span class="more-photos">+<?php echo count($wp['gallery']) - 4; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Scroll indicator -->
    <div class="ajbridge-hero-scroll">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="7,13 12,18 17,13"></polyline>
            <polyline points="7,6 12,11 17,6"></polyline>
        </svg>
    </div>
</div>
