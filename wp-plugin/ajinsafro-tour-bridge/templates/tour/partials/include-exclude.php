<?php
/**
 * Include/Exclude Partial - What's included and not included
 *
 * @var array $tour Tour data
 * @package AjinsafroTourBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$inclusions = $tour['inclusions'] ?? [];
$exclusions = $tour['exclusions'] ?? [];

if (empty($inclusions) && empty($exclusions)) {
    return;
}
?>

<section class="ajtb-section" id="include-exclude">
    <h2 class="ajtb-section-title">
        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
        </svg>
        Ce qui est Inclus / Exclus
    </h2>

    <div class="ajtb-include-exclude-grid">
        <!-- Inclusions -->
        <?php if (!empty($inclusions)): ?>
            <div class="include-column">
                <div class="column-header included">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" fill="none" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22,4 12,14.01 9,11.01"></polyline>
                    </svg>
                    <span>Inclus dans le prix</span>
                </div>
                <ul class="items-list">
                    <?php foreach ($inclusions as $item): ?>
                        <li class="item included">
                            <span class="item-icon">
                                <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="3">
                                    <polyline points="20,6 9,17 4,12"></polyline>
                                </svg>
                            </span>
                            <span class="item-text"><?php echo esc_html($item); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Exclusions -->
        <?php if (!empty($exclusions)): ?>
            <div class="exclude-column">
                <div class="column-header excluded">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" fill="none" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <span>Non inclus</span>
                </div>
                <ul class="items-list">
                    <?php foreach ($exclusions as $item): ?>
                        <li class="item excluded">
                            <span class="item-icon">
                                <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="3">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </span>
                            <span class="item-text"><?php echo esc_html($item); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <div class="ajtb-note">
        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
        <span>Les services listés sont indicatifs et peuvent varier selon la saison et la disponibilité.</span>
    </div>
</section>
