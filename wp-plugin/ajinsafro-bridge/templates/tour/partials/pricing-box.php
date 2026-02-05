<?php
/**
 * Pricing Box Partial
 *
 * Displays the sidebar pricing box with booking CTA.
 *
 * @var array $pricing Pricing data (base, seasonal, display)
 * @var array $tourData Full tour data
 * @package AjinsafroBridge\Templates\Partials
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$wp = $tourData['wp'];
$displayPrice = $pricing['display']['adult'];
$hasDiscount = $pricing['base']['has_discount'];
$originalPrice = $pricing['base']['adult'];
$currencySymbol = $pricing['currency_symbol'];
$seasonName = $pricing['display']['season_name'];
?>

<div class="ajbridge-sidebar-box pricing-box">
    <!-- Price Display -->
    <div class="pricing-header">
        <?php if (!empty($seasonName)): ?>
            <span class="season-badge"><?php echo esc_html($seasonName); ?></span>
        <?php endif; ?>

        <div class="price-main">
            <?php if ($hasDiscount && $originalPrice > $displayPrice): ?>
                <span class="price-original"><?php echo number_format($originalPrice, 0, ',', ' '); ?> <?php echo $currencySymbol; ?></span>
            <?php endif; ?>
            <span class="price-current">
                <?php echo number_format($displayPrice, 0, ',', ' '); ?>
                <small><?php echo $currencySymbol; ?></small>
            </span>
            <span class="price-unit">/ personne</span>
        </div>

        <?php if ($hasDiscount && $pricing['base']['discount'] > 0): ?>
            <div class="discount-badge">
                Économisez <?php echo (int)$pricing['base']['discount']; ?>%
            </div>
        <?php endif; ?>
    </div>

    <!-- Price Breakdown -->
    <div class="pricing-breakdown">
        <div class="price-row">
            <span class="price-label">Adulte</span>
            <span class="price-value"><?php echo number_format($displayPrice, 0, ',', ' '); ?> <?php echo $currencySymbol; ?></span>
        </div>
        
        <?php if ($pricing['display']['child'] > 0): ?>
            <div class="price-row">
                <span class="price-label">Enfant (2-12 ans)</span>
                <span class="price-value"><?php echo number_format($pricing['display']['child'], 0, ',', ' '); ?> <?php echo $currencySymbol; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($pricing['display']['infant'] > 0): ?>
            <div class="price-row">
                <span class="price-label">Bébé (0-2 ans)</span>
                <span class="price-value"><?php echo number_format($pricing['display']['infant'], 0, ',', ' '); ?> <?php echo $currencySymbol; ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Info -->
    <div class="pricing-info">
        <?php if ($wp['duration_day'] > 0): ?>
            <div class="info-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span><?php echo $wp['duration_day']; ?> jour<?php echo $wp['duration_day'] > 1 ? 's' : ''; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($wp['max_people'] > 0): ?>
            <div class="info-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Max <?php echo $wp['max_people']; ?> pers.</span>
            </div>
        <?php endif; ?>

        <div class="info-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>Confirmation immédiate</span>
        </div>
    </div>

    <!-- Booking Form -->
    <div class="pricing-booking">
        <form class="booking-form" id="tour-booking-form">
            <input type="hidden" name="tour_id" value="<?php echo $wp['id']; ?>">
            
            <!-- Date Selection -->
            <div class="form-group">
                <label for="booking-date">Date de départ</label>
                <input type="date" 
                       id="booking-date" 
                       name="booking_date" 
                       class="form-control"
                       min="<?php echo date('Y-m-d'); ?>"
                       required>
            </div>

            <!-- Travelers -->
            <div class="form-group travelers-group">
                <label>Voyageurs</label>
                <div class="travelers-controls">
                    <div class="traveler-row">
                        <span class="traveler-label">
                            Adultes
                            <small>13+ ans</small>
                        </span>
                        <div class="quantity-control">
                            <button type="button" class="qty-btn qty-minus" data-target="adults">−</button>
                            <input type="number" name="adults" id="adults" value="2" min="1" max="<?php echo $wp['max_people'] ?: 20; ?>" readonly>
                            <button type="button" class="qty-btn qty-plus" data-target="adults">+</button>
                        </div>
                    </div>

                    <?php if ($pricing['display']['child'] > 0): ?>
                        <div class="traveler-row">
                            <span class="traveler-label">
                                Enfants
                                <small>2-12 ans</small>
                            </span>
                            <div class="quantity-control">
                                <button type="button" class="qty-btn qty-minus" data-target="children">−</button>
                                <input type="number" name="children" id="children" value="0" min="0" max="10" readonly>
                                <button type="button" class="qty-btn qty-plus" data-target="children">+</button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($pricing['display']['infant'] > 0): ?>
                        <div class="traveler-row">
                            <span class="traveler-label">
                                Bébés
                                <small>0-2 ans</small>
                            </span>
                            <div class="quantity-control">
                                <button type="button" class="qty-btn qty-minus" data-target="infants">−</button>
                                <input type="number" name="infants" id="infants" value="0" min="0" max="5" readonly>
                                <button type="button" class="qty-btn qty-plus" data-target="infants">+</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Total Price -->
            <div class="booking-total">
                <span class="total-label">Total estimé</span>
                <span class="total-price" id="booking-total">
                    <?php echo number_format($displayPrice * 2, 0, ',', ' '); ?> <?php echo $currencySymbol; ?>
                </span>
            </div>

            <!-- Submit Button -->
            <?php if (!empty($wp['external_booking_link'])): ?>
                <a href="<?php echo esc_url($wp['external_booking_link']); ?>" 
                   class="btn-book-now" 
                   target="_blank" 
                   rel="noopener noreferrer">
                    Réserver maintenant
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12,5 19,12 12,19"></polyline>
                    </svg>
                </a>
            <?php else: ?>
                <button type="submit" class="btn-book-now">
                    Réserver maintenant
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12,5 19,12 12,19"></polyline>
                    </svg>
                </button>
            <?php endif; ?>
        </form>

        <!-- Secondary Actions -->
        <div class="booking-actions">
            <button type="button" class="btn-secondary btn-inquiry" data-tour-id="<?php echo $wp['id']; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                </svg>
                Demander un devis
            </button>
            
            <button type="button" class="btn-secondary btn-share" data-url="<?php echo esc_url($wp['permalink']); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="18" cy="5" r="3"></circle>
                    <circle cx="6" cy="12" r="3"></circle>
                    <circle cx="18" cy="19" r="3"></circle>
                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                </svg>
                Partager
            </button>
        </div>
    </div>

    <!-- Deposit Info -->
    <?php if ($wp['allow_deposit']): ?>
        <div class="deposit-info">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <span>
                Acompte de <?php echo $wp['deposit_type'] === 'percent' ? $wp['deposit_amount'] . '%' : number_format($wp['deposit_amount'], 0) . ' ' . $currencySymbol; ?> requis
            </span>
        </div>
    <?php endif; ?>

    <!-- Trust badges -->
    <div class="trust-badges">
        <div class="trust-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            </svg>
            <span>Paiement sécurisé</span>
        </div>
        <div class="trust-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>Meilleur prix garanti</span>
        </div>
        <div class="trust-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
                <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>
            </svg>
            <span>Support 24/7</span>
        </div>
    </div>
</div>

<!-- Seasonal Pricing Table (if available) -->
<?php if ($tourData['has_seasonal_pricing'] && count($pricing['seasonal']) > 1): ?>
    <div class="ajbridge-sidebar-box seasonal-pricing-box">
        <h4>Tarifs par Saison</h4>
        <div class="seasonal-prices">
            <?php foreach ($pricing['seasonal'] as $season): 
                $isCurrentSeason = $pricing['current_season'] && 
                                   $pricing['current_season']['id'] === $season['id'];
            ?>
                <div class="season-row <?php echo $isCurrentSeason ? 'current' : ''; ?>">
                    <div class="season-info">
                        <span class="season-name">
                            <?php echo esc_html($season['season_name']); ?>
                            <?php if ($isCurrentSeason): ?>
                                <small>(Actuelle)</small>
                            <?php endif; ?>
                        </span>
                        <span class="season-dates">
                            <?php 
                            $start = new DateTime($season['start_date']);
                            $end = new DateTime($season['end_date']);
                            echo $start->format('d M') . ' - ' . $end->format('d M Y');
                            ?>
                        </span>
                    </div>
                    <div class="season-price">
                        <?php echo number_format($season['adult_price'], 0, ',', ' '); ?> <?php echo $currencySymbol; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
