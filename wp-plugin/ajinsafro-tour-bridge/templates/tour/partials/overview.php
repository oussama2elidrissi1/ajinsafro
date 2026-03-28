<?php
/**
 * Overview Partial - Tour Description & Highlights
 *
 * @var array $tour Tour data
 * @package AjinsafroTourBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<section class="ajtb-section ajtb-tab-panel ajtb-tab-panel-hidden" id="overview">
    <h2 class="ajtb-section-title">
        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
        Aperçu du Circuit
    </h2>

    <!-- Highlights -->
    <?php if (!empty($tour['highlights'])): ?>
        <div class="ajtb-highlights">
            <h3 class="highlights-title">Points Forts</h3>
            <ul class="highlights-list">
                <?php foreach ($tour['highlights'] as $highlight): ?>
                    <li>
                        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22,4 12,14.01 9,11.01"></polyline>
                        </svg>
                        <span><?php echo esc_html($highlight); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Overview Content (from Laravel if available) -->
    <?php if (!empty($tour['overview'])): ?>
        <div class="ajtb-overview-content ajtb-content-block">
            <?php echo wp_kses_post($tour['overview']); ?>
        </div>
    <?php endif; ?>

    <!-- Main Description -->
    <div class="ajtb-description">
        <div class="description-content ajtb-content-block">
            <?php echo $tour['content']; ?>
        </div>
    </div>

    <!-- Tour Quick Facts -->
    <div class="ajtb-quick-facts">
        <h3 class="facts-title">Informations Pratiques</h3>
        <div class="facts-grid">
            <?php if ($tour['duration_day'] > 0): ?>
                <div class="fact-item">
                    <div class="fact-icon">
                        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div class="fact-content">
                        <span class="fact-label">Durée</span>
                        <span class="fact-value"><?php echo esc_html($tour['duration_day']); ?> jour<?php echo $tour['duration_day'] > 1 ? 's' : ''; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($tour['max_people'] > 0): ?>
                <div class="fact-item">
                    <div class="fact-icon">
                        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="fact-content">
                        <span class="fact-label">Groupe</span>
                        <span class="fact-value">
                            <?php if ($tour['min_people'] > 0): ?>
                                <?php echo esc_html($tour['min_people']); ?> - 
                            <?php endif; ?>
                            <?php echo esc_html($tour['max_people']); ?> personnes
                        </span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($tour['type_tour'])): ?>
                <div class="fact-item">
                    <div class="fact-icon">
                        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
                            <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                            <line x1="4" y1="22" x2="4" y2="15"></line>
                        </svg>
                    </div>
                    <div class="fact-content">
                        <span class="fact-label">Type</span>
                        <span class="fact-value"><?php echo esc_html(ucfirst(str_replace('_', ' ', $tour['type_tour']))); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($tour['address'])): ?>
                <div class="fact-item">
                    <div class="fact-icon">
                        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </div>
                    <div class="fact-content">
                        <span class="fact-label">Départ</span>
                        <span class="fact-value"><?php echo esc_html($tour['address']); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</section>
