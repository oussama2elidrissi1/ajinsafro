<?php
/**
 * Archive Tour Template
 *
 * Template for displaying st_tours archive.
 * Overrides the theme's archive-st_tours.php template.
 *
 * @package AjinsafroBridge\Templates
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Get archive data with enriched tours
$assembler = new \AjinsafroBridge\Services\TourAssembler();
$paged = get_query_var('paged') ? get_query_var('paged') : 1;

$archiveData = $assembler->getArchiveData([
    'paged' => $paged,
    'posts_per_page' => 12,
]);

$tours = $archiveData['tours'];
$totalPages = $archiveData['pages'];
$totalTours = $archiveData['total'];

// Get archive title
$archiveTitle = post_type_archive_title('', false) ?: 'Nos Circuits';

// Check if taxonomy archive
if (is_tax()) {
    $term = get_queried_object();
    $archiveTitle = $term->name ?? $archiveTitle;
}
?>

<div class="ajbridge-tour-archive">
    <!-- Archive Header -->
    <div class="ajbridge-archive-header">
        <div class="ajbridge-container">
            <h1 class="archive-title"><?php echo esc_html($archiveTitle); ?></h1>
            <p class="archive-count">
                <?php echo $totalTours; ?> circuit<?php echo $totalTours > 1 ? 's' : ''; ?> disponible<?php echo $totalTours > 1 ? 's' : ''; ?>
            </p>
            
            <?php if (is_tax() && !empty($term->description)): ?>
                <div class="archive-description">
                    <?php echo wpautop($term->description); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Bar (optional - can be enhanced later) -->
    <div class="ajbridge-filter-bar">
        <div class="ajbridge-container">
            <div class="filter-controls">
                <div class="filter-sort">
                    <label for="tour-sort">Trier par:</label>
                    <select id="tour-sort" class="tour-sort-select">
                        <option value="date-desc">Plus récent</option>
                        <option value="date-asc">Plus ancien</option>
                        <option value="price-asc">Prix croissant</option>
                        <option value="price-desc">Prix décroissant</option>
                        <option value="duration-asc">Durée courte</option>
                        <option value="duration-desc">Durée longue</option>
                    </select>
                </div>
                <div class="filter-view">
                    <button type="button" class="view-btn active" data-view="grid" title="Vue grille">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                        </svg>
                    </button>
                    <button type="button" class="view-btn" data-view="list" title="Vue liste">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="8" y1="6" x2="21" y2="6"></line>
                            <line x1="8" y1="12" x2="21" y2="12"></line>
                            <line x1="8" y1="18" x2="21" y2="18"></line>
                            <line x1="3" y1="6" x2="3.01" y2="6"></line>
                            <line x1="3" y1="12" x2="3.01" y2="12"></line>
                            <line x1="3" y1="18" x2="3.01" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tours Grid -->
    <div class="ajbridge-archive-content">
        <div class="ajbridge-container">
            <?php if (!empty($tours)): ?>
                <div class="tours-grid" id="tours-grid">
                    <?php foreach ($tours as $tour): 
                        $wp = $tour['wp'];
                        $badges = $tour['laravel']['badges'] ?? [];
                        
                        // Calculate display price
                        $price = $wp['adult_price'] ?? $wp['base_price'] ?? 0;
                        if ($wp['has_discount'] && $wp['sale_price'] > 0) {
                            $originalPrice = $price;
                            $price = $wp['sale_price'];
                        }
                        
                        $currency = get_option('st_currency', 'MAD');
                        $currencySymbol = $currency === 'MAD' ? 'DH' : ($currency === 'EUR' ? '€' : $currency);
                    ?>
                        <article class="tour-card" data-tour-id="<?php echo $wp['id']; ?>">
                            <!-- Card Image -->
                            <div class="tour-card-image">
                                <a href="<?php echo esc_url($wp['permalink']); ?>">
                                    <?php if (!empty($wp['featured_image']['url'])): ?>
                                        <img src="<?php echo esc_url($wp['featured_image']['sizes']['medium'] ?? $wp['featured_image']['url']); ?>" 
                                             alt="<?php echo esc_attr($wp['title']); ?>"
                                             loading="lazy">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                <polyline points="21,15 16,10 5,21"></polyline>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </a>

                                <!-- Badges -->
                                <?php if (!empty($badges) || $wp['is_featured']): ?>
                                    <div class="tour-card-badges">
                                        <?php if ($wp['is_featured']): ?>
                                            <span class="badge badge-featured">Populaire</span>
                                        <?php endif; ?>
                                        <?php foreach (array_slice($badges, 0, 2) as $badge): ?>
                                            <span class="badge" style="background-color: <?php echo esc_attr($badge['bg_color']); ?>; color: <?php echo esc_attr($badge['color']); ?>;">
                                                <?php echo esc_html($badge['label']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Discount Badge -->
                                <?php if ($wp['has_discount'] && $wp['discount'] > 0): ?>
                                    <div class="tour-card-discount">
                                        -<?php echo (int)$wp['discount']; ?>%
                                    </div>
                                <?php endif; ?>

                                <!-- Wishlist Button -->
                                <button type="button" class="wishlist-btn" data-tour-id="<?php echo $wp['id']; ?>" title="Ajouter aux favoris">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                    </svg>
                                </button>
                            </div>

                            <!-- Card Content -->
                            <div class="tour-card-content">
                                <!-- Categories -->
                                <?php if (!empty($wp['categories'])): ?>
                                    <div class="tour-card-categories">
                                        <?php foreach (array_slice($wp['categories'], 0, 2) as $cat): ?>
                                            <a href="<?php echo esc_url($cat['link']); ?>" class="category-link">
                                                <?php echo esc_html($cat['name']); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Title -->
                                <h3 class="tour-card-title">
                                    <a href="<?php echo esc_url($wp['permalink']); ?>">
                                        <?php echo esc_html($wp['title']); ?>
                                    </a>
                                </h3>

                                <!-- Location -->
                                <?php if (!empty($wp['address'])): ?>
                                    <div class="tour-card-location">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        <span><?php echo esc_html($wp['address']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <!-- Meta Info -->
                                <div class="tour-card-meta">
                                    <?php if ($wp['duration_day'] > 0): ?>
                                        <span class="meta-item">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12,6 12,12 16,14"></polyline>
                                            </svg>
                                            <?php echo $wp['duration_day']; ?>j
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($wp['max_people'] > 0): ?>
                                        <span class="meta-item">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="9" cy="7" r="4"></circle>
                                            </svg>
                                            <?php echo $wp['max_people']; ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($wp['rate_review'] > 0): ?>
                                        <span class="meta-item meta-rating">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                                <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                                            </svg>
                                            <?php echo number_format($wp['rate_review'], 1); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Price & CTA -->
                                <div class="tour-card-footer">
                                    <div class="tour-card-price">
                                        <?php if (isset($originalPrice) && $originalPrice > $price): ?>
                                            <span class="price-original"><?php echo number_format($originalPrice, 0, ',', ' '); ?> <?php echo $currencySymbol; ?></span>
                                        <?php endif; ?>
                                        <span class="price-current">
                                            <?php echo number_format($price, 0, ',', ' '); ?> 
                                            <small><?php echo $currencySymbol; ?></small>
                                        </span>
                                        <span class="price-unit">/ personne</span>
                                    </div>
                                    <a href="<?php echo esc_url($wp['permalink']); ?>" class="tour-card-btn">
                                        Voir détails
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php 
                        // Reset for next iteration
                        unset($originalPrice);
                    endforeach; 
                    ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="ajbridge-pagination">
                        <?php
                        echo paginate_links([
                            'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                            'format' => '?paged=%#%',
                            'current' => max(1, $paged),
                            'total' => $totalPages,
                            'prev_text' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15,18 9,12 15,6"></polyline></svg>',
                            'next_text' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9,18 15,12 9,6"></polyline></svg>',
                            'type' => 'list',
                        ]);
                        ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- No Tours Found -->
                <div class="no-tours-found">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <h3>Aucun circuit trouvé</h3>
                    <p>Aucun circuit disponible pour le moment. Revenez bientôt !</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
