/**
 * Ajinsafro Bridge - Single Tour JavaScript
 * Handles interactions on tour detail pages
 */

(function($) {
    'use strict';

    // Wait for DOM ready
    $(document).ready(function() {
        AjbridgeTourSingle.init();
    });

    /**
     * Main Tour Single Module
     */
    var AjbridgeTourSingle = {
        /**
         * Initialize all components
         */
        init: function() {
            this.initQuantityControls();
            this.initPriceCalculation();
            this.initFAQToggle();
            this.initDayToggle();
            this.initShareButton();
            this.initInquiryButton();
            this.initLightbox();
            this.initSmoothScroll();
            this.initStickyBooking();
        },

        /**
         * Quantity +/- controls for travelers
         */
        initQuantityControls: function() {
            $(document).on('click', '.qty-btn', function() {
                var $btn = $(this);
                var target = $btn.data('target');
                var $input = $('#' + target);
                var currentVal = parseInt($input.val(), 10) || 0;
                var min = parseInt($input.attr('min'), 10) || 0;
                var max = parseInt($input.attr('max'), 10) || 99;

                if ($btn.hasClass('qty-plus')) {
                    if (currentVal < max) {
                        $input.val(currentVal + 1);
                    }
                } else if ($btn.hasClass('qty-minus')) {
                    if (currentVal > min) {
                        $input.val(currentVal - 1);
                    }
                }

                // Trigger price recalculation
                $(document).trigger('ajbridge:quantityChanged');
            });
        },

        /**
         * Calculate and update total price
         */
        initPriceCalculation: function() {
            var self = this;

            // Listen for quantity changes
            $(document).on('ajbridge:quantityChanged', function() {
                self.calculateTotal();
            });

            // Initial calculation
            this.calculateTotal();
        },

        /**
         * Calculate total price based on quantities
         */
        calculateTotal: function() {
            if (typeof ajbridgeTour === 'undefined') {
                return;
            }

            var adults = parseInt($('#adults').val(), 10) || 0;
            var children = parseInt($('#children').val(), 10) || 0;
            var infants = parseInt($('#infants').val(), 10) || 0;

            // Get prices from data attributes or global config
            var $pricingBox = $('.pricing-box');
            var adultPrice = parseFloat($pricingBox.find('.price-current').text().replace(/[^\d.]/g, '')) || 0;
            
            // For child and infant, we'd normally get from separate elements
            // Using simple calculation here (can be enhanced)
            var childPrice = adultPrice * 0.7; // 70% of adult price
            var infantPrice = 0; // Free for infants

            var total = (adults * adultPrice) + (children * childPrice) + (infants * infantPrice);

            // Format number with spaces as thousand separator
            var formatted = this.formatPrice(total);
            
            // Update display
            $('#booking-total').text(formatted + ' ' + this.getCurrencySymbol());
        },

        /**
         * Format price with proper separators
         */
        formatPrice: function(price) {
            return Math.round(price).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        },

        /**
         * Get currency symbol
         */
        getCurrencySymbol: function() {
            if (typeof ajbridgeTour !== 'undefined' && ajbridgeTour.currency) {
                var symbols = {
                    'MAD': 'DH',
                    'EUR': '€',
                    'USD': '$',
                    'GBP': '£'
                };
                return symbols[ajbridgeTour.currency] || ajbridgeTour.currency;
            }
            return 'DH';
        },

        /**
         * FAQ accordion toggle
         */
        initFAQToggle: function() {
            $(document).on('click', '.faq-question', function() {
                var $item = $(this).closest('.faq-item');
                var $answer = $item.find('.faq-answer');
                var isActive = $item.hasClass('active');

                // Close all others
                $('.faq-item').removeClass('active');
                $('.faq-answer').css('max-height', '0').css('padding', '0 20px');

                // Toggle current
                if (!isActive) {
                    $item.addClass('active');
                    var scrollHeight = $answer.get(0).scrollHeight;
                    $answer.css('max-height', scrollHeight + 'px').css('padding', '16px 20px');
                }
            });
        },

        /**
         * Itinerary day toggle (for mobile)
         */
        initDayToggle: function() {
            $(document).on('click', '.day-toggle', function() {
                var $dayContent = $(this).closest('.day-content');
                var $dayBody = $dayContent.find('.day-body');
                var $icon = $(this).find('svg');

                $dayBody.slideToggle(200);
                $icon.toggleClass('rotated');
            });
        },

        /**
         * Share button functionality
         */
        initShareButton: function() {
            $(document).on('click', '.btn-share', function() {
                var url = $(this).data('url');
                var title = document.title;

                // Check for Web Share API
                if (navigator.share) {
                    navigator.share({
                        title: title,
                        url: url
                    }).catch(function(err) {
                        console.log('Share cancelled', err);
                    });
                } else {
                    // Fallback: copy to clipboard
                    AjbridgeTourSingle.copyToClipboard(url);
                    AjbridgeTourSingle.showNotification('Lien copié dans le presse-papier!');
                }
            });
        },

        /**
         * Copy text to clipboard
         */
        copyToClipboard: function(text) {
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
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
            }, 3000);
        },

        /**
         * Inquiry button - open modal or redirect
         */
        initInquiryButton: function() {
            $(document).on('click', '.btn-inquiry', function() {
                var tourId = $(this).data('tour-id');
                
                // Simple implementation: scroll to a contact form if exists
                // Or trigger a modal (to be implemented based on requirements)
                
                // For now, show a simple alert
                AjbridgeTourSingle.showNotification('Fonctionnalité de devis à venir!');
            });
        },

        /**
         * Simple lightbox for gallery
         */
        initLightbox: function() {
            $(document).on('click', '[data-lightbox]', function(e) {
                e.preventDefault();

                var $this = $(this);
                var group = $this.data('lightbox');
                var $items = $('[data-lightbox="' + group + '"]');
                var index = $items.index($this);

                AjbridgeTourSingle.openLightbox($items, index);
            });
        },

        /**
         * Open lightbox overlay
         */
        openLightbox: function($items, startIndex) {
            var self = this;
            var currentIndex = startIndex;
            var images = [];

            $items.each(function() {
                images.push({
                    src: $(this).attr('href'),
                    alt: $(this).find('img').attr('alt') || ''
                });
            });

            // Create lightbox HTML
            var $lightbox = $('<div class="ajbridge-lightbox">' +
                '<div class="lightbox-overlay"></div>' +
                '<div class="lightbox-content">' +
                    '<button class="lightbox-close">&times;</button>' +
                    '<button class="lightbox-prev">&lsaquo;</button>' +
                    '<img class="lightbox-image" src="" alt="">' +
                    '<button class="lightbox-next">&rsaquo;</button>' +
                    '<div class="lightbox-counter"></div>' +
                '</div>' +
            '</div>');

            // Add styles
            $lightbox.css({
                position: 'fixed',
                inset: 0,
                zIndex: 99999,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center'
            });

            $lightbox.find('.lightbox-overlay').css({
                position: 'absolute',
                inset: 0,
                background: 'rgba(0,0,0,0.9)'
            });

            $lightbox.find('.lightbox-content').css({
                position: 'relative',
                maxWidth: '90%',
                maxHeight: '90%'
            });

            $lightbox.find('.lightbox-image').css({
                maxWidth: '100%',
                maxHeight: '85vh',
                display: 'block'
            });

            var buttonStyle = {
                position: 'absolute',
                background: 'rgba(255,255,255,0.2)',
                color: '#fff',
                border: 'none',
                fontSize: '30px',
                cursor: 'pointer',
                padding: '10px 15px',
                borderRadius: '4px'
            };

            $lightbox.find('.lightbox-close').css($.extend({}, buttonStyle, {
                top: '-40px',
                right: 0,
                fontSize: '24px'
            }));

            $lightbox.find('.lightbox-prev').css($.extend({}, buttonStyle, {
                left: '-50px',
                top: '50%',
                transform: 'translateY(-50%)'
            }));

            $lightbox.find('.lightbox-next').css($.extend({}, buttonStyle, {
                right: '-50px',
                top: '50%',
                transform: 'translateY(-50%)'
            }));

            $lightbox.find('.lightbox-counter').css({
                textAlign: 'center',
                color: '#fff',
                marginTop: '10px',
                fontSize: '14px'
            });

            // Show image function
            function showImage(index) {
                currentIndex = index;
                if (currentIndex < 0) currentIndex = images.length - 1;
                if (currentIndex >= images.length) currentIndex = 0;

                $lightbox.find('.lightbox-image')
                    .attr('src', images[currentIndex].src)
                    .attr('alt', images[currentIndex].alt);

                $lightbox.find('.lightbox-counter')
                    .text((currentIndex + 1) + ' / ' + images.length);
            }

            // Bind events
            $lightbox.find('.lightbox-overlay, .lightbox-close').on('click', function() {
                $lightbox.remove();
                $('body').css('overflow', '');
            });

            $lightbox.find('.lightbox-prev').on('click', function() {
                showImage(currentIndex - 1);
            });

            $lightbox.find('.lightbox-next').on('click', function() {
                showImage(currentIndex + 1);
            });

            // Keyboard navigation
            $(document).on('keydown.lightbox', function(e) {
                if (e.key === 'Escape') {
                    $lightbox.remove();
                    $('body').css('overflow', '');
                    $(document).off('keydown.lightbox');
                } else if (e.key === 'ArrowLeft') {
                    showImage(currentIndex - 1);
                } else if (e.key === 'ArrowRight') {
                    showImage(currentIndex + 1);
                }
            });

            // Append and show
            $('body').append($lightbox).css('overflow', 'hidden');
            showImage(startIndex);
        },

        /**
         * Smooth scroll to sections
         */
        initSmoothScroll: function() {
            $(document).on('click', 'a[href^="#"]', function(e) {
                var target = $(this.getAttribute('href'));
                if (target.length) {
                    e.preventDefault();
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 500);
                }
            });
        },

        /**
         * Sticky booking sidebar on scroll
         */
        initStickyBooking: function() {
            // The CSS handles this with position: sticky
            // This is for any additional JS-based behavior if needed
        }
    };

    // Expose to global scope
    window.AjbridgeTourSingle = AjbridgeTourSingle;

})(jQuery);
