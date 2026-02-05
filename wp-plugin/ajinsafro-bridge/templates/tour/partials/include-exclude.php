<?php
/**
 * Include/Exclude Partial
 *
 * Displays inclusions and exclusions lists for a tour.
 *
 * @var array $inclusions List of included items
 * @var array $exclusions List of excluded items
 * @package AjinsafroBridge\Templates\Partials
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (empty($inclusions) && empty($exclusions)) {
    return;
}
?>

<div class="ajbridge-tour-include-exclude">
    <h3>Ce qui est inclus / exclus</h3>
    
    <div class="include-exclude-grid">
        <!-- Inclusions Column -->
        <?php if (!empty($inclusions)): ?>
            <div class="inclusions-column">
                <h4 class="column-title inclusions-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    Inclus dans le prix
                </h4>
                <ul class="include-list">
                    <?php foreach ($inclusions as $item): ?>
                        <li class="include-item">
                            <span class="item-icon included">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <polyline points="20,6 9,17 4,12"></polyline>
                                </svg>
                            </span>
                            <span class="item-content">
                                <span class="item-title"><?php echo esc_html($item['title']); ?></span>
                                <?php if (!empty($item['description'])): ?>
                                    <span class="item-description"><?php echo esc_html($item['description']); ?></span>
                                <?php endif; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Exclusions Column -->
        <?php if (!empty($exclusions)): ?>
            <div class="exclusions-column">
                <h4 class="column-title exclusions-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    Non inclus
                </h4>
                <ul class="exclude-list">
                    <?php foreach ($exclusions as $item): ?>
                        <li class="exclude-item">
                            <span class="item-icon excluded">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </span>
                            <span class="item-content">
                                <span class="item-title"><?php echo esc_html($item['title']); ?></span>
                                <?php if (!empty($item['description'])): ?>
                                    <span class="item-description"><?php echo esc_html($item['description']); ?></span>
                                <?php endif; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <!-- Optional Note -->
    <div class="include-exclude-note">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
        <span>Les services listés ci-dessus sont indicatifs et peuvent varier selon la saison et la disponibilité.</span>
    </div>
</div>
