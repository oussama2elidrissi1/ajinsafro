<?php
/**
 * Booking Box Partial - Sticky Sidebar with Price & CTA
 *
 * @var array $tour Tour data
 * @package AjinsafroTourBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$pricing = $tour['pricing'] ?? [];
$display_price = $pricing['display_price'] ?? 0;
$original_price = $pricing['original_price'] ?? $pricing['adult'] ?? 0;
$has_discount = $pricing['has_discount'] ?? false;
$discount = $pricing['discount'] ?? 0;
$currency_symbol = $pricing['currency_symbol'] ?? 'DH';
$has_seasonal = !empty($pricing['seasonal_rules']);
$current_season = $pricing['current_season'] ?? null;
?>

<div class="ajtb-booking-box">
    <!-- Price Header -->
    <div class="booking-price-header">
        <?php if ($current_season): ?>
            <span class="season-badge">
                <?php echo esc_html($current_season['season_name'] ?? 'Saison en cours'); ?>
            </span>
        <?php endif; ?>

        <div class="price-display">
            <?php if ($has_discount && $original_price > $display_price): ?>
                <span class="price-original"><?php echo ajtb_format_price($original_price); ?></span>
            <?php endif; ?>
            <span class="price-current">
                <?php echo number_format($display_price, 0, ',', ' '); ?>
                <small><?php echo esc_html($currency_symbol); ?></small>
            </span>
            <span class="price-unit">/ personne</span>
        </div>

        <?php if ($has_discount && $discount > 0): ?>
            <div class="discount-badge">
                -<?php echo (int)$discount; ?>% de réduction
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Info -->
    <div class="booking-quick-info">
        <?php if ($tour['duration_day'] > 0): ?>
            <div class="info-item">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span><?php echo esc_html($tour['duration_day']); ?> jour<?php echo $tour['duration_day'] > 1 ? 's' : ''; ?></span>
            </div>
        <?php endif; ?>
        <div class="info-item">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22,4 12,14.01 9,11.01"></polyline>
            </svg>
            <span>Confirmation immédiate</span>
        </div>
    </div>

    <!-- Booking Form (date + voyageurs pilotés par la barre de recherche au-dessus) -->
    <form class="booking-form" id="ajtb-booking-form">
        <input type="hidden" name="tour_id" value="<?php echo esc_attr($tour['id']); ?>">
        <input type="hidden" name="date" id="booking-date" value="">
        <input type="hidden" name="departure_place_id" id="booking-departure-place" value="">
        <input type="hidden" name="adults" id="adults" value="2">
        <input type="hidden" name="children" id="children" value="0">

        <!-- Cart Summary -->
        <div class="booking-cart" id="booking-cart">
            <div class="cart-item cart-item-base">
                <span class="cart-label">Prix de base</span>
                <span class="cart-value" id="cart-base-value">
                    <?php echo number_format($display_price * 2, 0, ',', ' '); ?> <?php echo esc_html($currency_symbol); ?>
                </span>
            </div>
            
            <!-- Activities List -->
            <div class="cart-activities" id="cart-activities-wrapper" style="display: none;">
                <div class="cart-activities-header">Activités ajoutées</div>
                <ul class="ajtb-activity-list" id="cart-activities-list">
                    <!-- Activities will be added here dynamically -->
                </ul>
            </div>
        </div>

        <!-- Total -->
        <div class="booking-total">
            <span class="total-label">Total estimé</span>
            <span class="total-value" id="booking-total">
                <?php echo number_format($display_price * 2, 0, ',', ' '); ?> <?php echo esc_html($currency_symbol); ?>
            </span>
        </div>

        <!-- CTA Buttons -->
        <?php if (!empty($tour['external_booking_link'])): ?>
            <a href="<?php echo esc_url($tour['external_booking_link']); ?>" 
               class="btn-primary btn-book" 
               target="_blank" 
               rel="noopener noreferrer">
                Réserver maintenant
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12,5 19,12 12,19"></polyline>
                </svg>
            </a>
        <?php else: ?>
            <button type="submit" class="btn-primary btn-book">
                Réserver maintenant
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12,5 19,12 12,19"></polyline>
                </svg>
            </button>
        <?php endif; ?>
    </form>

    <!-- Share & Wishlist -->
    <div class="booking-actions">
        <button type="button" class="action-btn" id="share-tour" data-url="<?php echo esc_url($tour['permalink']); ?>">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
                <circle cx="18" cy="5" r="3"></circle>
                <circle cx="6" cy="12" r="3"></circle>
                <circle cx="18" cy="19" r="3"></circle>
                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
            </svg>
            Partager
        </button>
        <button type="button" class="action-btn" id="save-tour" data-tour-id="<?php echo esc_attr($tour['id']); ?>">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
            Sauvegarder
        </button>
    </div>
</div>

<!-- Seasonal Pricing Table (if available) -->
<?php if ($has_seasonal && count($pricing['seasonal_rules']) > 1): ?>
    <div class="ajtb-seasonal-pricing">
        <h4>
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            Tarifs par Saison
        </h4>
        <div class="seasons-list">
            <?php foreach ($pricing['seasonal_rules'] as $rule): 
                $is_current = $current_season && ($current_season['id'] ?? null) === ($rule['id'] ?? null);
            ?>
                <div class="season-item <?php echo $is_current ? 'current' : ''; ?>">
                    <div class="season-info">
                        <span class="season-name">
                            <?php echo esc_html($rule['season_name'] ?? 'Saison'); ?>
                            <?php if ($is_current): ?>
                                <small>(actuelle)</small>
                            <?php endif; ?>
                        </span>
                        <span class="season-dates">
                            <?php if (!empty($rule['start_date']) && !empty($rule['end_date'])): ?>
                                <?php 
                                $start = new DateTime($rule['start_date']);
                                $end = new DateTime($rule['end_date']);
                                echo $start->format('d M') . ' - ' . $end->format('d M Y');
                                ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="season-price">
                        <?php echo ajtb_format_price($rule['adult_price']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
