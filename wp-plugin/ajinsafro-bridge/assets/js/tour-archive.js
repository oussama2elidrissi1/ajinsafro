/**
 * Ajinsafro Bridge - Archive Tour JavaScript
 * Handles interactions on tour archive/listing pages
 */

(function($) {
    'use strict';

    // Wait for DOM ready
    $(document).ready(function() {
        AjbridgeTourArchive.init();
    });

    /**
     * Main Tour Archive Module
     */
    var AjbridgeTourArchive = {
        /**
         * Initialize all components
         */
        init: function() {
            this.initViewToggle();
            this.initWishlist();
            this.initSortFilter();
            this.initLazyLoad();
            this.initHoverEffects();
        },

        /**
         * Grid/List view toggle
         */
        initViewToggle: function() {
            $(document).on('click', '.view-btn', function() {
                var $btn = $(this);
                var view = $btn.data('view');
                var $grid = $('#tours-grid');

                // Update active state
                $('.view-btn').removeClass('active');
                $btn.addClass('active');

                // Toggle grid class
                if (view === 'list') {
                    $grid.addClass('list-view');
                } else {
                    $grid.removeClass('list-view');
                }

                // Save preference
                localStorage.setItem('ajbridge_tour_view', view);
            });

            // Load saved preference
            var savedView = localStorage.getItem('ajbridge_tour_view');
            if (savedView) {
                $('.view-btn[data-view="' + savedView + '"]').trigger('click');
            }
        },

        /**
         * Wishlist toggle functionality
         */
        initWishlist: function() {
            // Load saved wishlist
            var wishlist = this.getWishlist();

            // Mark saved items
            $('.wishlist-btn').each(function() {
                var tourId = $(this).data('tour-id');
                if (wishlist.indexOf(tourId.toString()) !== -1) {
                    $(this).addClass('active');
                }
            });

            // Toggle wishlist
            $(document).on('click', '.wishlist-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $btn = $(this);
                var tourId = $btn.data('tour-id').toString();
                var wishlist = AjbridgeTourArchive.getWishlist();

                if ($btn.hasClass('active')) {
                    // Remove from wishlist
                    $btn.removeClass('active');
                    wishlist = wishlist.filter(function(id) {
                        return id !== tourId;
                    });
                    AjbridgeTourArchive.showNotification('Retiré des favoris');
                } else {
                    // Add to wishlist
                    $btn.addClass('active');
                    if (wishlist.indexOf(tourId) === -1) {
                        wishlist.push(tourId);
                    }
                    AjbridgeTourArchive.showNotification('Ajouté aux favoris');
                }

                // Save wishlist
                AjbridgeTourArchive.saveWishlist(wishlist);
            });
        },

        /**
         * Get wishlist from localStorage
         */
        getWishlist: function() {
            try {
                return JSON.parse(localStorage.getItem('ajbridge_wishlist')) || [];
            } catch (e) {
                return [];
            }
        },

        /**
         * Save wishlist to localStorage
         */
        saveWishlist: function(wishlist) {
            try {
                localStorage.setItem('ajbridge_wishlist', JSON.stringify(wishlist));
            } catch (e) {
                console.warn('Could not save wishlist to localStorage');
            }
        },

        /**
         * Sort/Filter functionality
         */
        initSortFilter: function() {
            $(document).on('change', '#tour-sort', function() {
                var sortValue = $(this).val();
                var $grid = $('#tours-grid');
                var $cards = $grid.find('.tour-card');

                // Sort cards based on value
                var sortedCards = AjbridgeTourArchive.sortCards($cards, sortValue);

                // Re-append in new order
                $grid.empty().append(sortedCards);

                // Re-trigger animations
                AjbridgeTourArchive.animateCards();
            });
        },

        /**
         * Sort tour cards
         */
        sortCards: function($cards, sortType) {
            var cardsArray = $cards.toArray();

            cardsArray.sort(function(a, b) {
                var $a = $(a);
                var $b = $(b);

                switch (sortType) {
                    case 'date-desc':
                        // Newest first (default order, no data-date attr needed)
                        return 0;

                    case 'date-asc':
                        // Oldest first (reverse default)
                        return $cards.index(b) - $cards.index(a);

                    case 'price-asc':
                        var priceA = AjbridgeTourArchive.getPrice($a);
                        var priceB = AjbridgeTourArchive.getPrice($b);
                        return priceA - priceB;

                    case 'price-desc':
                        var priceA = AjbridgeTourArchive.getPrice($a);
                        var priceB = AjbridgeTourArchive.getPrice($b);
                        return priceB - priceA;

                    case 'duration-asc':
                        var durA = AjbridgeTourArchive.getDuration($a);
                        var durB = AjbridgeTourArchive.getDuration($b);
                        return durA - durB;

                    case 'duration-desc':
                        var durA = AjbridgeTourArchive.getDuration($a);
                        var durB = AjbridgeTourArchive.getDuration($b);
                        return durB - durA;

                    default:
                        return 0;
                }
            });

            return cardsArray;
        },

        /**
         * Get price from card
         */
        getPrice: function($card) {
            var priceText = $card.find('.price-current').text();
            return parseFloat(priceText.replace(/[^\d.]/g, '')) || 0;
        },

        /**
         * Get duration from card
         */
        getDuration: function($card) {
            var durationText = $card.find('.meta-item').first().text();
            var match = durationText.match(/(\d+)/);
            return match ? parseInt(match[1], 10) : 0;
        },

        /**
         * Animate cards after sorting
         */
        animateCards: function() {
            $('.tour-card').each(function(index) {
                var $card = $(this);
                $card.css({
                    opacity: 0,
                    transform: 'translateY(20px)'
                });

                setTimeout(function() {
                    $card.css({
                        opacity: 1,
                        transform: 'translateY(0)',
                        transition: 'all 0.4s ease'
                    });
                }, index * 50);
            });
        },

        /**
         * Lazy load images
         */
        initLazyLoad: function() {
            // Using native lazy loading (loading="lazy" attribute in HTML)
            // This is a fallback for older browsers

            if ('IntersectionObserver' in window) {
                var imageObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var image = entry.target;
                            if (image.dataset.src) {
                                image.src = image.dataset.src;
                                image.removeAttribute('data-src');
                                imageObserver.unobserve(image);
                            }
                        }
                    });
                }, {
                    rootMargin: '50px 0px'
                });

                $('img[data-src]').each(function() {
                    imageObserver.observe(this);
                });
            }
        },

        /**
         * Hover effects and interactions
         */
        initHoverEffects: function() {
            // Card hover animation is handled by CSS
            // This can be used for more complex interactions

            // Example: Show quick view on hover after delay
            var hoverTimeout;

            $('.tour-card').on('mouseenter', function() {
                var $card = $(this);
                hoverTimeout = setTimeout(function() {
                    // Could show quick view overlay
                }, 1000);
            }).on('mouseleave', function() {
                clearTimeout(hoverTimeout);
            });
        },

        /**
         * Show notification toast
         */
        showNotification: function(message) {
            var $toast = $('<div class="ajbridge-toast">' + message + '</div>');
            $toast.css({
                position: 'fixed',
                bottom: '20px',
                left: '50%',
                transform: 'translateX(-50%)',
                padding: '12px 24px',
                background: '#1e293b',
                color: '#fff',
                borderRadius: '8px',
                fontSize: '14px',
                zIndex: 9999,
                opacity: 0,
                transition: 'opacity 0.3s ease'
            });

            $('body').append($toast);

            setTimeout(function() {
                $toast.css('opacity', 1);
            }, 10);

            setTimeout(function() {
                $toast.css('opacity', 0);
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, 2000);
        }
    };

    // Expose to global scope
    window.AjbridgeTourArchive = AjbridgeTourArchive;

})(jQuery);
