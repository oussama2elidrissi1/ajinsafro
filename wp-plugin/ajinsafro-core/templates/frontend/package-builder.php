<?php
if (!defined('ABSPATH')) exit;

$tour = $package_state['tour'] ?? [];
$session = $package_state['session'] ?? [];
$pricing = $package_state['pricing'] ?? [];
$days = $package_state['days'] ?? [];
$counters = $package_state['included_counters'] ?? [];
?>

<div class="aj-package-builder" data-voyage-id="<?php echo esc_attr($laravel_voyage_id); ?>" data-session-id="<?php echo esc_attr($session['id'] ?? ''); ?>">
    
    <!-- Header -->
    <div class="aj-package-header">
        <h2 class="aj-package-title"><?php echo esc_html($tour['name'] ?? ''); ?></h2>
        <div class="aj-package-meta">
            <span class="aj-duration"><?php echo esc_html($tour['duration_text'] ?? ''); ?></span>
            <span class="aj-destination"><?php echo esc_html($tour['destination'] ?? ''); ?></span>
        </div>
    </div>

    <div class="aj-package-container">
        
        <!-- Sidebar: Days Navigation -->
        <div class="aj-package-sidebar">
            <h3><?php _e('Days', 'ajinsafro-core'); ?></h3>
            <ul class="aj-days-nav">
                <?php foreach ($days as $index => $day): ?>
                    <li>
                        <a href="#" 
                           class="aj-day-tab <?php echo $index === 0 ? 'active' : ''; ?>" 
                           data-day="<?php echo esc_attr($day['day_number']); ?>">
                            <?php printf(__('Day %d', 'ajinsafro-core'), $day['day_number']); ?>
                            <?php if (!empty($day['city'])): ?>
                                <span class="aj-day-city"><?php echo esc_html($day['city']); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Pricing Summary -->
            <div class="aj-pricing-sidebar">
                <h4><?php _e('Pricing', 'ajinsafro-core'); ?></h4>
                <div class="aj-price-per-person">
                    <span class="aj-price-label"><?php _e('Per Person', 'ajinsafro-core'); ?></span>
                    <span class="aj-price-amount"><?php echo esc_html($this->format_price($pricing['total_per_person'] ?? 0, $pricing['currency'] ?? 'MAD')); ?></span>
                </div>
                <div class="aj-price-total">
                    <span class="aj-price-label"><?php printf(__('Total (%d pax)', 'ajinsafro-core'), $session['total_pax'] ?? 2); ?></span>
                    <span class="aj-price-amount"><?php echo esc_html($this->format_price($pricing['total_group'] ?? 0, $pricing['currency'] ?? 'MAD')); ?></span>
                </div>
                <button type="button" class="aj-btn-book-now">
                    <?php _e('Book Now', 'ajinsafro-core'); ?>
                </button>
            </div>
        </div>

        <!-- Main Content: Day Details -->
        <div class="aj-package-content">
            <?php foreach ($days as $index => $day): ?>
                <div class="aj-day-content <?php echo $index === 0 ? 'active' : ''; ?>" 
                     data-day="<?php echo esc_attr($day['day_number']); ?>">
                    
                    <h3 class="aj-day-title">
                        <?php printf(__('Day %d: %s', 'ajinsafro-core'), $day['day_number'], esc_html($day['title'])); ?>
                    </h3>

                    <?php if (!empty($day['description'])): ?>
                        <div class="aj-day-description">
                            <?php echo wp_kses_post($day['description']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($day['items'])): ?>
                        <div class="aj-day-items">
                            <h4><?php _e('Included in this day', 'ajinsafro-core'); ?></h4>
                            <ul class="aj-items-list">
                                <?php foreach ($day['items'] as $item): ?>
                                    <?php if ($item['selected'] ?? false): ?>
                                        <li class="aj-item <?php echo esc_attr($item['type']); ?> <?php echo $item['included'] ? 'included' : 'optional'; ?>">
                                            <span class="aj-item-icon">
                                                <?php echo $this->get_item_icon($item['type']); ?>
                                            </span>
                                            <div class="aj-item-details">
                                                <strong class="aj-item-title"><?php echo esc_html($item['title']); ?></strong>
                                                <?php if (!empty($item['details'])): ?>
                                                    <p class="aj-item-description"><?php echo esc_html($item['details']); ?></p>
                                                <?php endif; ?>
                                                <?php if (!$item['included']): ?>
                                                    <span class="aj-item-badge optional"><?php _e('Optional', 'ajinsafro-core'); ?></span>
                                                    <span class="aj-item-price"><?php echo esc_html($item['formatted_price'] ?? ''); ?></span>
                                                <?php else: ?>
                                                    <span class="aj-item-badge included"><?php _e('Included', 'ajinsafro-core'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($day['meals'])): ?>
                        <div class="aj-day-meals">
                            <h4><?php _e('Meals', 'ajinsafro-core'); ?></h4>
                            <ul class="aj-meals-list">
                                <?php if ($day['meals']['breakfast']): ?>
                                    <li>🍳 <?php _e('Breakfast', 'ajinsafro-core'); ?></li>
                                <?php endif; ?>
                                <?php if ($day['meals']['lunch']): ?>
                                    <li>🍽️ <?php _e('Lunch', 'ajinsafro-core'); ?></li>
                                <?php endif; ?>
                                <?php if ($day['meals']['dinner']): ?>
                                    <li>🍷 <?php _e('Dinner', 'ajinsafro-core'); ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        </div>

    </div>

</div>

<?php
// Helper methods
function format_price($cents, $currency) {
    $amount = $cents / 100;
    $symbol = match(strtoupper($currency)) {
        'MAD' => 'DH',
        'EUR' => '€',
        'USD' => '$',
        default => $currency,
    };
    return number_format($amount, 2, ',', ' ') . ' ' . $symbol;
}

function get_item_icon($type) {
    return match($type) {
        'flight' => '✈️',
        'hotel_stay' => '🏨',
        'transfer' => '🚗',
        'activity' => '🎯',
        'meal' => '🍽️',
        'addon' => '➕',
        default => '📦',
    };
}

// Make helper available in scope
$this->format_price = 'format_price';
$this->get_item_icon = 'get_item_icon';
?>
