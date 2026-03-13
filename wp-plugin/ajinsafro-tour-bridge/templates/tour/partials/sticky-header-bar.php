<?php
/**
 * Sticky header bar: titre + barre onglets uniquement (collés). Les jours et le plan de séjour scrollent avec le contenu.
 *
 * @var array $tour Tour data
 * @var array $global_totals ['days','flights','transfers','hotels','activities','meals']
 * @package AjinsafroTourBridge
 */

if (!defined('ABSPATH')) {
    exit;
}

$tour_title_display = $tour['title'] ?? get_the_title();
?>
<div class="ajtb-itinerary-sticky-wrap ajtb-sticky-header-bar">
    <h2 class="ajtb-itinerary-sticky-title"><?php echo esc_html($tour_title_display); ?></h2>
    <div class="ajtb-global-summary-bar" id="ajtb-global-summary-bar" data-ajtb-mode="global">
        <nav class="ajtb-global-summary-nav" aria-label="<?php esc_attr_e('Résumé du séjour', 'ajinsafro-tour-bridge'); ?>">
            <button type="button" class="ajtb-global-pill active" data-ajtb-global-tab="programme" aria-pressed="true">
                <span class="ajtb-global-pill-label"><?php echo esc_html($global_totals['days']); ?> DAY PLAN</span>
            </button>
            <?php if ($global_totals['flights'] > 0 || $global_totals['transfers'] > 0): ?>
                <button type="button" class="ajtb-global-pill" data-ajtb-global-tab="flights-transfers" aria-pressed="false">
                    <span class="ajtb-global-pill-label">
                        <?php
                        $parts = [];
                        if ($global_totals['flights'] > 0) $parts[] = $global_totals['flights'] . ' ' . ($global_totals['flights'] > 1 ? __('FLIGHTS', 'ajinsafro-tour-bridge') : __('FLIGHT', 'ajinsafro-tour-bridge'));
                        if ($global_totals['transfers'] > 0) $parts[] = $global_totals['transfers'] . ' ' . ($global_totals['transfers'] > 1 ? __('TRANSFERS', 'ajinsafro-tour-bridge') : __('TRANSFER', 'ajinsafro-tour-bridge'));
                        echo esc_html(implode(' & ', $parts));
                        ?>
                    </span>
                </button>
            <?php endif; ?>
            <?php if ($global_totals['hotels'] > 0): ?>
                <button type="button" class="ajtb-global-pill" data-ajtb-global-tab="hotels" aria-pressed="false">
                    <span class="ajtb-global-pill-label"><?php echo esc_html($global_totals['hotels']); ?> <?php echo $global_totals['hotels'] > 1 ? __('HOTELS', 'ajinsafro-tour-bridge') : __('HOTEL', 'ajinsafro-tour-bridge'); ?></span>
                </button>
            <?php endif; ?>
            <?php if ($global_totals['activities'] > 0): ?>
                <button type="button" class="ajtb-global-pill" data-ajtb-global-tab="activities" aria-pressed="false">
                    <span class="ajtb-global-pill-label"><?php echo esc_html($global_totals['activities']); ?> <?php echo $global_totals['activities'] > 1 ? __('ACTIVITIES', 'ajinsafro-tour-bridge') : __('ACTIVITY', 'ajinsafro-tour-bridge'); ?></span>
                </button>
            <?php endif; ?>
            <?php if ($global_totals['meals'] > 0): ?>
                <button type="button" class="ajtb-global-pill" data-ajtb-global-tab="meals" aria-pressed="false">
                    <span class="ajtb-global-pill-label"><?php echo esc_html($global_totals['meals']); ?> <?php echo $global_totals['meals'] > 1 ? __('MEALS', 'ajinsafro-tour-bridge') : __('MEAL', 'ajinsafro-tour-bridge'); ?></span>
                </button>
            <?php endif; ?>
        </nav>
    </div>
</div>
