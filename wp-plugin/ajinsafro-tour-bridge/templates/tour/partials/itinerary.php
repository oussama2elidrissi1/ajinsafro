<?php
/**
 * Itinerary Partial - Day by Day Timeline/Accordion
 *
 * @var array $tour Tour data
 * @package AjinsafroTourBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$itinerary = $tour['itinerary'] ?? [];
$source = $tour['_sources']['itinerary'] ?? 'wordpress';

if (empty($itinerary)) {
    return;
}
?>

<section class="ajtb-section" id="itinerary">
    <h2 class="ajtb-section-title">
        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
            <path d="M14.5 10c-.83 0-1.5-.67-1.5-1.5v-5c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5v5c0 .83-.67 1.5-1.5 1.5z"></path>
            <path d="M20.5 10H19V8.5c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"></path>
            <path d="M9.5 14c.83 0 1.5.67 1.5 1.5v5c0 .83-.67 1.5-1.5 1.5S8 21.33 8 20.5v-5c0-.83.67-1.5 1.5-1.5z"></path>
            <path d="M3.5 14H5v1.5c0 .83-.67 1.5-1.5 1.5S2 16.33 2 15.5 2.67 14 3.5 14z"></path>
            <path d="M14 14.5c0-.83.67-1.5 1.5-1.5h5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-5c-.83 0-1.5-.67-1.5-1.5z"></path>
            <path d="M15.5 19H14v1.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5-.67-1.5-1.5-1.5z"></path>
            <path d="M10 9.5C10 8.67 9.33 8 8.5 8h-5C2.67 8 2 8.67 2 9.5S2.67 11 3.5 11h5c.83 0 1.5-.67 1.5-1.5z"></path>
            <path d="M8.5 5H10V3.5C10 2.67 9.33 2 8.5 2S7 2.67 7 3.5 7.67 5 8.5 5z"></path>
        </svg>
        Programme du Circuit
        <span class="section-badge"><?php echo count($itinerary); ?> jour<?php echo count($itinerary) > 1 ? 's' : ''; ?></span>
    </h2>

    <div class="ajtb-itinerary-timeline">
        <?php foreach ($itinerary as $index => $day): 
            $day_number = $day['day'] ?? ($index + 1);
            $is_first = ($index === 0);
            $is_last = ($index === count($itinerary) - 1);
            $day_title_display = !empty($day['day_title']) ? $day['day_title'] : ($day['title'] ?? 'Jour ' . $day_number);
            $mode = isset($day['mode']) ? $day['mode'] : 'program';
            $activities = isset($day['activities']) ? $day['activities'] : [];
        ?>
            <div class="itinerary-day <?php echo $is_first ? 'first' : ''; ?> <?php echo $is_last ? 'last' : ''; ?> itinerary-day-mode-<?php echo esc_attr($mode); ?>" data-day="<?php echo $day_number; ?>">
                <!-- Timeline Marker -->
                <div class="day-marker">
                    <span class="day-number"><?php echo $day_number; ?></span>
                    <?php if (!$is_last): ?>
                        <div class="marker-line"></div>
                    <?php endif; ?>
                </div>

                <!-- Day Content -->
                <div class="day-card">
                    <div class="day-header" data-toggle="day-content-<?php echo $index; ?>">
                        <div class="day-header-content">
                            <span class="day-label">Jour <?php echo $day_number; ?></span>
                            <?php if ($mode === 'free'): ?>
                                <span class="badge badge-free-day">Jour libre</span>
                            <?php endif; ?>
                            <h3 class="day-title">
                                <?php echo esc_html($day_title_display); ?>
                            </h3>
                        </div>
                        <button class="day-toggle" aria-expanded="<?php echo $is_first ? 'true' : 'false'; ?>">
                            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="2">
                                <polyline points="6,9 12,15 18,9"></polyline>
                            </svg>
                        </button>
                    </div>

                    <div class="day-body" id="day-content-<?php echo $index; ?>" <?php echo !$is_first ? 'style="display:none;"' : ''; ?>>
                        <!-- Day Image (if available) -->
                        <?php if (!empty($day['image'])): ?>
                            <div class="day-image">
                                <img src="<?php echo esc_url($day['image']); ?>" 
                                     alt="Jour <?php echo $day_number; ?>" 
                                     loading="lazy">
                            </div>
                        <?php endif; ?>

                        <!-- Day Description / Notes (program mode or fallback) -->
                        <?php if ($mode === 'program' && (!empty($day['notes']) || !empty($day['description']) || !empty($day['content']))): ?>
                            <div class="day-description">
                                <?php echo wp_kses_post($day['notes'] ?? $day['description'] ?? $day['content']); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Activities (Laravel: aj_tour_day_activities) -->
                        <?php if (!empty($activities)): ?>
                            <ul class="day-activities-list">
                                <?php foreach ($activities as $act): 
                                    if (empty($act['is_included'])) { continue; }
                                    $act_title = !empty($act['title']) ? $act['title'] : '';
                                    $act_desc = !empty($act['description']) ? $act['description'] : '';
                                ?>
                                    <li class="day-activity-item">
                                        <?php if ($act_title): ?>
                                            <span class="activity-title"><?php echo esc_html($act_title); ?></span>
                                            <?php if (!empty($act['is_mandatory'])): ?>
                                                <span class="badge badge-mandatory">Obligatoire</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($act_desc): ?>
                                            <div class="activity-description"><?php echo wp_kses_post($act_desc); ?></div>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <!-- Day Details (Laravel: meals, accommodation) -->
                        <?php if ($source === 'laravel'): ?>
                            <div class="day-details">
                                <?php if (!empty($day['meals'])): ?>
                                    <div class="detail-item">
                                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="2">
                                            <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                                            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                                            <line x1="6" y1="1" x2="6" y2="4"></line>
                                            <line x1="10" y1="1" x2="10" y2="4"></line>
                                            <line x1="14" y1="1" x2="14" y2="4"></line>
                                        </svg>
                                        <span class="detail-label">Repas:</span>
                                        <span class="detail-value"><?php echo esc_html($day['meals']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($day['accommodation'])): ?>
                                    <div class="detail-item">
                                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="2">
                                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                            <polyline points="9,22 9,12 15,12 15,22"></polyline>
                                        </svg>
                                        <span class="detail-label">Hébergement:</span>
                                        <span class="detail-value"><?php echo esc_html($day['accommodation']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Print/Download Itinerary -->
    <div class="itinerary-actions">
        <button type="button" class="btn-outline" onclick="window.print();">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
                <polyline points="6,9 6,2 18,2 18,9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Imprimer le programme
        </button>
        
        <button type="button" class="btn-text" id="expand-all-days">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
                <polyline points="15,3 21,3 21,9"></polyline>
                <polyline points="9,21 3,21 3,15"></polyline>
                <line x1="21" y1="3" x2="14" y2="10"></line>
                <line x1="3" y1="21" x2="10" y2="14"></line>
            </svg>
            Tout déplier
        </button>
    </div>
</section>
