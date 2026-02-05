<?php
/**
 * Itinerary Partial
 *
 * Displays the day-by-day itinerary for a tour.
 * Data can come from Laravel (aj_tour_days) or WordPress (tours_program meta).
 *
 * @var array $itinerary Array of days/steps
 * @var string $source 'laravel' or 'wordpress'
 * @package AjinsafroBridge\Templates\Partials
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (empty($itinerary)) {
    return;
}
?>

<div class="ajbridge-tour-itinerary">
    <h3>Itinéraire du Circuit</h3>
    
    <div class="itinerary-timeline">
        <?php foreach ($itinerary as $index => $day): 
            $dayNumber = $day['day_number'] ?? ($index + 1);
            $isLast = ($index === count($itinerary) - 1);
        ?>
            <div class="itinerary-day <?php echo $isLast ? 'is-last' : ''; ?>" data-day="<?php echo $dayNumber; ?>">
                <!-- Day marker -->
                <div class="day-marker">
                    <span class="day-number"><?php echo $dayNumber; ?></span>
                    <?php if (!$isLast): ?>
                        <div class="day-line"></div>
                    <?php endif; ?>
                </div>

                <!-- Day content -->
                <div class="day-content">
                    <div class="day-header">
                        <h4 class="day-title">
                            <span class="day-label">Jour <?php echo $dayNumber; ?></span>
                            <?php if (!empty($day['title'])): ?>
                                <span class="day-name"><?php echo esc_html($day['title']); ?></span>
                            <?php endif; ?>
                        </h4>
                        
                        <!-- Toggle button for mobile -->
                        <button type="button" class="day-toggle" aria-expanded="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6,9 12,15 18,9"></polyline>
                            </svg>
                        </button>
                    </div>

                    <div class="day-body">
                        <!-- Day image (if available) -->
                        <?php if (!empty($day['image_url'])): ?>
                            <div class="day-image">
                                <img src="<?php echo esc_url($day['image_url']); ?>" 
                                     alt="Jour <?php echo $dayNumber; ?> - <?php echo esc_attr($day['title']); ?>"
                                     loading="lazy">
                            </div>
                        <?php endif; ?>

                        <!-- Day description -->
                        <?php if (!empty($day['description'])): ?>
                            <div class="day-description">
                                <?php echo wp_kses_post($day['description']); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Day details (Laravel extras) -->
                        <?php if ($source === 'laravel'): ?>
                            <div class="day-details">
                                <!-- Meals -->
                                <?php if (!empty($day['meals_included'])): ?>
                                    <div class="detail-item meals">
                                        <span class="detail-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                                                <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                                                <line x1="6" y1="1" x2="6" y2="4"></line>
                                                <line x1="10" y1="1" x2="10" y2="4"></line>
                                                <line x1="14" y1="1" x2="14" y2="4"></line>
                                            </svg>
                                        </span>
                                        <span class="detail-label">Repas:</span>
                                        <span class="detail-value">
                                            <?php echo esc_html(implode(', ', $day['meals_included'])); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <!-- Accommodation -->
                                <?php if (!empty($day['accommodation'])): ?>
                                    <div class="detail-item accommodation">
                                        <span class="detail-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                                <polyline points="9,22 9,12 15,12 15,22"></polyline>
                                            </svg>
                                        </span>
                                        <span class="detail-label">Hébergement:</span>
                                        <span class="detail-value"><?php echo esc_html($day['accommodation']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <!-- Transport -->
                                <?php if (!empty($day['transport'])): ?>
                                    <div class="detail-item transport">
                                        <span class="detail-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="1" y="3" width="15" height="13"></rect>
                                                <polygon points="16,8 20,8 23,11 23,16 16,16 16,8"></polygon>
                                                <circle cx="5.5" cy="18.5" r="2.5"></circle>
                                                <circle cx="18.5" cy="18.5" r="2.5"></circle>
                                            </svg>
                                        </span>
                                        <span class="detail-label">Transport:</span>
                                        <span class="detail-value"><?php echo esc_html($day['transport']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <!-- Distance -->
                                <?php if (!empty($day['distance_km']) && $day['distance_km'] > 0): ?>
                                    <div class="detail-item distance">
                                        <span class="detail-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polygon points="16.24,7.76 14.12,14.12 7.76,16.24 9.88,9.88 16.24,7.76"></polygon>
                                            </svg>
                                        </span>
                                        <span class="detail-label">Distance:</span>
                                        <span class="detail-value"><?php echo number_format($day['distance_km'], 0); ?> km</span>
                                    </div>
                                <?php endif; ?>

                                <!-- Duration -->
                                <?php if (!empty($day['duration_hours']) && $day['duration_hours'] > 0): ?>
                                    <div class="detail-item duration">
                                        <span class="detail-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12,6 12,12 16,14"></polyline>
                                            </svg>
                                        </span>
                                        <span class="detail-label">Durée:</span>
                                        <span class="detail-value">~<?php echo number_format($day['duration_hours'], 0); ?>h</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Highlights -->
                            <?php if (!empty($day['highlights'])): ?>
                                <div class="day-highlights">
                                    <span class="highlights-label">Points forts:</span>
                                    <ul class="highlights-list">
                                        <?php foreach ($day['highlights'] as $highlight): ?>
                                            <li><?php echo esc_html($highlight); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Print itinerary button -->
    <div class="itinerary-actions">
        <button type="button" class="btn-print-itinerary" onclick="window.print();">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6,9 6,2 18,2 18,9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Imprimer l'itinéraire
        </button>
    </div>
</div>
