<?php
/**
 * Itinerary Partial - By day: aj_tour_days (notes) + aj_tour_day_activities. No WP tours_program in a day.
 * Fallback: when no Laravel days, show WP tours_program list (Traveler style).
 *
 * @var array $tour Tour data
 * @package AjinsafroTourBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$itinerary = $tour['itinerary'] ?? [];
$wp_program = $tour['wp_program'] ?? ['style' => 'style1', 'items' => []];
$source = $tour['_sources']['itinerary'] ?? 'wordpress';
$session_token = $tour['_session_token'] ?? '';
$tour_id = (int) ($tour['id'] ?? 0);
$activities_catalog = $tour['activities_catalog'] ?? [];
$can_toggle_activities = ($source === 'laravel' && !empty($session_token) && $tour_id > 0);

// No Laravel days: fallback to WP tours_program list only if WP has items
if (empty($itinerary)) {
    if (!empty($wp_program['items'])) {
        $program_style = isset($wp_program['style']) ? sanitize_html_class($wp_program['style']) : 'style1';
        if ($program_style === '') { $program_style = 'style1'; }
        ?>
    <section class="ajtb-section" id="itinerary">
        <h2 class="ajtb-section-title">Programme du Circuit</h2>
        <div class="aj-program-list program-style-<?php echo esc_attr($program_style); ?>">
            <?php foreach ($wp_program['items'] as $item): 
                $title = isset($item['title']) ? trim((string) $item['title']) : '';
                $desc = isset($item['desc']) ? trim((string) $item['desc']) : '';
            ?>
                <div class="aj-program-item">
                    <?php if ($title !== ''): ?><h4 class="aj-program-item-title"><?php echo esc_html($title); ?></h4><?php endif; ?>
                    <?php if ($desc !== ''): ?><div class="aj-program-item-desc"><?php echo wp_kses_post(nl2br($desc)); ?></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="itinerary-actions">
            <button type="button" class="btn-outline" onclick="window.print();"><?php esc_html_e('Imprimer le programme', 'ajinsafro-tour-bridge'); ?></button>
        </div>
    </section>
    <?php
    } else {
        ?>
    <section class="ajtb-section" id="itinerary">
        <h2 class="ajtb-section-title">Programme du Circuit</h2>
        <p class="aj-program-unavailable"><?php esc_html_e('Programme non disponible.', 'ajinsafro-tour-bridge'); ?></p>
    </section>
    <?php
    }
    return;
}
?>

<section class="ajtb-section" id="itinerary" data-tour-id="<?php echo $tour_id; ?>" data-session-token="<?php echo esc_attr($session_token); ?>" data-activities-catalog="<?php echo esc_attr(wp_json_encode($activities_catalog)); ?>">
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

    <div class="ajtb-day-plan">
        <nav class="aj-day-plan-nav" aria-label="<?php esc_attr_e('Day Plan', 'ajinsafro-tour-bridge'); ?>">
            <?php foreach ($itinerary as $index => $day): 
                $day_num = $day['day'] ?? ($index + 1);
                $day_title_short = !empty($day['day_title']) ? $day['day_title'] : ('Jour ' . $day_num);
                if (strlen($day_title_short) > 28) {
                    $day_title_short = wp_trim_words($day_title_short, 4);
                }
            ?>
                <button type="button" class="aj-day-link <?php echo $index === 0 ? 'active' : ''; ?>" data-day-index="<?php echo $index; ?>" data-day="<?php echo $day_num; ?>">
                    <span class="aj-day-link-num"><?php echo $day_num; ?></span>
                    <span class="aj-day-link-title"><?php echo esc_html($day_title_short); ?></span>
                </button>
            <?php endforeach; ?>
        </nav>
        <div class="aj-day-plan-content">
    <div class="ajtb-itinerary-timeline">
        <?php foreach ($itinerary as $index => $day): 
            $day_number = $day['day'] ?? ($index + 1);
            $is_first = ($index === 0);
            $is_last = ($index === count($itinerary) - 1);
            $day_title_display = !empty($day['day_title']) ? $day['day_title'] : ($day['title'] ?? 'Jour ' . $day_number);
            $mode = isset($day['mode']) ? $day['mode'] : 'program';
            $activities = isset($day['activities']) ? $day['activities'] : [];
            $day_id = (int) ($day['id'] ?? 0);
            $day_activity_ids = array_map(function ($a) { return (int) ($a['activity_id'] ?? 0); }, $activities);
        ?>
            <div class="itinerary-day <?php echo $is_first ? 'first' : ''; ?> <?php echo $is_last ? 'last' : ''; ?> itinerary-day-mode-<?php echo esc_attr($mode); ?>" data-day="<?php echo $day_number; ?>" data-day-id="<?php echo $day_id; ?>" data-day-activity-ids="<?php echo esc_attr(implode(',', $day_activity_ids)); ?>">
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

                        <!-- Programme du jour: une seule description (notes) ou "Jour libre" -->
                        <div id="aj-day-notes-<?php echo $day_id; ?>" class="aj-day-programme-block">
                        <?php 
                        $day_notes = trim((string) ($day['notes'] ?? ''));
                        if ($day_notes === '' && isset($day['description'])) {
                            $day_notes = trim((string) $day['description']);
                        }
                        if ($day_notes === '' && isset($day['content'])) {
                            $day_notes = trim((string) $day['content']);
                        }
                        if ($day_notes !== ''): 
                            $notes_html = wp_kses_post(nl2br($day_notes));
                            $is_long = strlen(strip_tags($day_notes)) > 300;
                        ?>
                            <div class="aj-day-notes-wrap <?php echo $is_long ? 'aj-day-notes-collapsed' : ''; ?>">
                                <div class="aj-day-notes-content"><?php echo $notes_html; ?></div>
                                <?php if ($is_long): ?>
                                    <button type="button" class="aj-day-notes-read-more" aria-expanded="false"><?php esc_html_e('Lire plus', 'ajinsafro-tour-bridge'); ?></button>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($mode === 'free'): ?>
                            <p class="aj-day-notes day-notes day-description aj-day-free-label"><?php esc_html_e('Jour libre', 'ajinsafro-tour-bridge'); ?></p>
                        <?php endif; ?>
                        </div>

                        <!-- Activities container (id stable: JS replaces innerHTML after AJAX) -->
                        <div id="aj-day-activities-<?php echo $day_id; ?>">
                        <ul class="day-activities-list" data-day-id="<?php echo $day_id; ?>">
                            <?php 
                            $included_count = 0;
                            if (!empty($activities)): ?>
                                <?php foreach ($activities as $act): 
                                    if (empty($act['is_included'])) { continue; }
                                    $included_count++;
                                    $act_title = isset($act['title']) && (string) $act['title'] !== '' ? $act['title'] : '';
                                    $act_desc = isset($act['description']) && (string) $act['description'] !== '' ? $act['description'] : '';
                                    $act_id = (int) ($act['activity_id'] ?? 0);
                                    $is_mandatory = !empty($act['is_mandatory']);
                                    $show_remove = $can_toggle_activities && !$is_mandatory;
                                ?>
                                    <li class="day-activity-item" data-activity-id="<?php echo $act_id; ?>" data-is-mandatory="<?php echo $is_mandatory ? '1' : '0'; ?>">
                                        <span class="activity-title"><?php echo $act_title !== '' ? esc_html($act_title) : esc_html__('Activité', 'ajinsafro-tour-bridge'); ?></span>
                                        <?php if ($is_mandatory): ?>
                                            <span class="badge badge-mandatory">Obligatoire</span>
                                        <?php endif; ?>
                                        <?php if ($show_remove): ?>
                                            <button type="button" class="ajtb-btn-remove-activity" data-aj-action="remove" data-tour-id="<?php echo $tour_id; ?>" data-day-id="<?php echo $day_id; ?>" data-activity-id="<?php echo $act_id; ?>" aria-label="<?php esc_attr_e('Retirer cette activité', 'ajinsafro-tour-bridge'); ?>">Retirer</button>
                                        <?php endif; ?>
                                        <?php if ($act_desc !== ''): ?>
                                            <div class="activity-description"><?php echo wp_kses_post($act_desc); ?></div>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if ($included_count === 0): ?>
                                <li class="day-activity-item day-no-activities"><?php esc_html_e('Aucune activité', 'ajinsafro-tour-bridge'); ?></li>
                            <?php endif; ?>
                        </ul>
                        <?php if ($can_toggle_activities && $day_id > 0): ?>
                            <div class="day-add-activity" data-day-id="<?php echo $day_id; ?>">
                                <label for="aj-add-select-<?php echo $day_id; ?>"><?php esc_html_e('Ajouter une activité', 'ajinsafro-tour-bridge'); ?></label>
                                <select id="aj-add-select-<?php echo $day_id; ?>" class="ajtb-add-activity-select" data-day-id="<?php echo $day_id; ?>">
                                    <option value="">— <?php esc_html_e('Choisir', 'ajinsafro-tour-bridge'); ?> —</option>
                                    <?php
                                    $in_day = $day_activity_ids;
                                    foreach ($activities_catalog as $c): 
                                        $cid = (int) ($c['id'] ?? 0);
                                        if ($cid && !in_array($cid, $in_day, true)): ?>
                                            <option value="<?php echo $cid; ?>"><?php echo esc_html($c['title'] ?? ''); ?></option>
                                    <?php endif; endforeach; ?>
                                </select>
                                <button type="button" class="ajtb-btn-add-activity" data-aj-action="add" data-tour-id="<?php echo $tour_id; ?>" data-day-id="<?php echo $day_id; ?>" data-select-id="aj-add-select-<?php echo $day_id; ?>"><?php esc_html_e('Ajouter', 'ajinsafro-tour-bridge'); ?></button>
                            </div>
                        <?php endif; ?>
                        </div>

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
        </div>
    </div>

    <!-- Print/Download Itinerary (Laravel timeline only) -->
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
