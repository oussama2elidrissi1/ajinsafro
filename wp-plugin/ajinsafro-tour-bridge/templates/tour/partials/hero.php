<?php
/**
 * Hero Partial - Titre et meta uniquement (sans galerie d'images)
 *
 * @var array $tour Tour data
 * @package AjinsafroTourBridge
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<section class="ajtb-hero ajtb-hero-no-gallery">
    <div class="aj-wide-container">
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
