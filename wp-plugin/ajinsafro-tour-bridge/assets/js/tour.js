/**
 * Ajinsafro Tour Bridge - Main JavaScript
 * Handles interactions on single tour pages
 *
 * @version 1.0.0
 */

(function($) {
    'use strict';

    console.log('ajinsafro tour js loaded');

    // Wait for DOM ready
    $(document).ready(function() {
        if (typeof ajtbData !== 'undefined') {
            console.log('ajtbData:', { ajax_url: ajtbData.ajax_url || ajtbData.ajaxUrl, nonce: ajtbData.nonce ? 'set' : 'missing', postId: ajtbData.postId, tour_id: ajtbData.tour_id });
        }
        AJTB.init();
    });

    /**
     * Main Tour Module
     */
    var AJTB = {
        // Config
        config: {
            prices: {
                adult: 0,
                child: 0,
                infant: 0
            },
            currency: 'DH'
        },

        /**
         * Initialize all modules
         */
        init: function() {
            this.loadConfig();
            this.initTabs();
            this.initQuantityControls();
            this.initPriceCalculation();
            this.initItineraryAccordion();
            this.initActivityToggle();
            this.initFlightToggle();
            this.initFAQAccordion();
            this.initGallery();
            this.initShareButton();
            this.initSaveButton();
            this.initSmoothScroll();
            this.initStickyNav();
            this.initSearchbar();
        },

        /**
         * Load configuration from page
         */
        loadConfig: function() {
            if (typeof ajtbData !== 'undefined') {
                this.config.currency = ajtbData.currencySymbol || 'DH';
            }

            // Extract prices from booking box (adult + child; child = 0 if not present)
            var $priceBreakdown = $('.booking-price-breakdown');
            if ($priceBreakdown.length) {
                var $rows = $priceBreakdown.find('.price-row');
                var adultPriceText = $rows.first().find('.value').text();
                this.config.prices.adult = this.parsePrice(adultPriceText);
                if ($rows.length > 1) {
                    var childPriceText = $rows.eq(1).find('.value').text();
                    this.config.prices.child = this.parsePrice(childPriceText);
                }
            } else {
                var headerPrice = $('.price-current').first().text();
                this.config.prices.adult = this.parsePrice(headerPrice);
            }
        },

        /**
         * Parse price from formatted string
         */
        parsePrice: function(priceStr) {
            if (!priceStr) return 0;
            return parseFloat(priceStr.replace(/[^\d.]/g, '')) || 0;
        },

        /**
         * Format price with thousand separators
         */
        formatPrice: function(price) {
            return Math.round(price).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        },

        /**
         * Tab navigation
         */
        initTabs: function() {
            $(document).on('click', '.ajtb-tabs-nav .tab-link', function(e) {
                e.preventDefault();
                
                var target = $(this).attr('href');
                
                // Update active state
                $('.ajtb-tabs-nav .tab-link').removeClass('active');
                $(this).addClass('active');
                
                // Smooth scroll to section
                if ($(target).length) {
                    $('html, body').animate({
                        scrollTop: $(target).offset().top - 80
                    }, 500);
                }
            });
        },

        /**
         * Quantity +/- controls
         */
        initQuantityControls: function() {
            var self = this;

            $(document).on('click', '.qty-btn', function() {
                var $btn = $(this);
                var target = $btn.data('target');
                var $input = $('#' + target);
                var current = parseInt($input.val(), 10) || 0;
                var min = parseInt($input.attr('min'), 10) || 0;
                var max = parseInt($input.attr('max'), 10) || 99;

                if ($btn.hasClass('plus')) {
                    if (current < max) {
                        $input.val(current + 1);
                    }
                } else if ($btn.hasClass('minus')) {
                    if (current > min) {
                        $input.val(current - 1);
                    }
                }

                // Recalculate price
                self.calculateTotal();
            });
        },

        /**
         * Calculate and display total price
         */
        initPriceCalculation: function() {
            this.calculateTotal();
        },

        /**
         * Calculate total from quantities
         */
        calculateTotal: function() {
            var adults = parseInt($('#adults').val(), 10) || 0;
            var children = parseInt($('#children').val(), 10) || 0;

            var adultPrice = this.config.prices.adult || 0;
            var childPrice = this.config.prices.child !== undefined && this.config.prices.child !== null ? this.config.prices.child : 0;

            var total = (adults * adultPrice) + (children * childPrice);

            $('#booking-total').text(this.formatPrice(total) + ' ' + this.config.currency);
        },

        /**
         * Itinerary accordion
         */
        initItineraryAccordion: function() {
            // Day toggle
            $(document).on('click', '.day-header', function() {
                var $dayCard = $(this).closest('.day-card');
                var $dayBody = $dayCard.find('.day-body');
                var $toggle = $(this).find('.day-toggle');
                var isExpanded = $toggle.attr('aria-expanded') === 'true';

                if (isExpanded) {
                    $dayBody.slideUp(200);
                    $toggle.attr('aria-expanded', 'false');
                } else {
                    $dayBody.slideDown(200);
                    $toggle.attr('aria-expanded', 'true');
                }
            });

            // Expand all button
            $(document).on('click', '#expand-all-days', function() {
                var $btn = $(this);
                var allExpanded = $('.day-toggle[aria-expanded="false"]').length === 0;

                if (allExpanded) {
                    // Collapse all
                    $('.day-body').slideUp(200);
                    $('.day-toggle').attr('aria-expanded', 'false');
                    $btn.html('<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><polyline points="15,3 21,3 21,9"></polyline><polyline points="9,21 3,21 3,15"></polyline><line x1="21" y1="3" x2="14" y2="10"></line><line x1="3" y1="21" x2="10" y2="14"></line></svg> Tout déplier');
                } else {
                    // Expand all
                    $('.day-body').slideDown(200);
                    $('.day-toggle').attr('aria-expanded', 'true');
                    $btn.html('<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><polyline points="4,14 10,14 10,20"></polyline><polyline points="20,10 14,10 14,4"></polyline><line x1="14" y1="10" x2="21" y2="3"></line><line x1="3" y1="21" x2="10" y2="14"></line></svg> Tout réduire');
                }
            });

            // Lire plus: expand long notes
            $(document).on('click', '.aj-day-notes-read-more', function() {
                var $wrap = $(this).closest('.aj-day-notes-wrap');
                $wrap.addClass('aj-day-notes-expanded').removeClass('aj-day-notes-collapsed');
                $(this).attr('aria-expanded', 'true');
            });
        },

        /**
         * Activity toggle (add/remove) — event delegation + replace container HTML from server
         */
        initActivityToggle: function() {
            var self = this;
            var ajtbData = typeof window.ajtbData !== 'undefined' ? window.ajtbData : {};
            var ajaxUrl = ajtbData.ajax_url || ajtbData.ajaxUrl || '';
            var nonce = ajtbData.nonce || '';
            var $section = $('#itinerary');
            var tourId = $section.length ? ($section.data('tour-id') || ajtbData.tour_id || ajtbData.postId) : (ajtbData.tour_id || ajtbData.postId);
            var sessionToken = $section.length ? $section.data('session-token') : '';

            if (!ajaxUrl || !nonce) {
                console.warn('AJTB initActivityToggle: missing ajax_url or nonce', { ajaxUrl: !!ajaxUrl, nonce: !!nonce });
                return;
            }

            // Event delegation: one listener for all [data-aj-action] buttons (including dynamically inserted)
            document.addEventListener('click', function(e) {
                var btn = e.target && e.target.closest ? e.target.closest('[data-aj-action]') : null;
                if (!btn) return;
                var action = btn.getAttribute('data-aj-action');
                if (action !== 'remove' && action !== 'add') return;

                var tourIdVal = parseInt(btn.getAttribute('data-tour-id'), 10) || tourId;
                var dayIdVal = parseInt(btn.getAttribute('data-day-id'), 10);
                if (!dayIdVal) return;

                var activityIdVal = 0;
                if (action === 'remove') {
                    activityIdVal = parseInt(btn.getAttribute('data-activity-id'), 10);
                } else {
                    var selectId = btn.getAttribute('data-select-id');
                    var selectEl = selectId ? document.getElementById(selectId) : null;
                    if (!selectEl) return;
                    activityIdVal = parseInt(selectEl.value, 10);
                }
                if (!activityIdVal) {
                    if (action === 'add') AJTB.showToast('Choisissez une activité');
                    return;
                }

                if (btn.disabled) return;
                btn.disabled = true;

                var payload = {
                    action: 'aj_toggle_activity',
                    nonce: nonce,
                    tour_id: tourIdVal,
                    day_id: dayIdVal,
                    activity_id: activityIdVal,
                    toggle_action: action === 'remove' ? 'removed' : 'added',
                    session_token: sessionToken
                };
                console.log('AJ TB payload', payload);

                $.post(ajaxUrl, payload).done(function(resp) {
                    console.log('AJ TB response', resp);
                    if (resp.success && resp.data && resp.data.html !== undefined) {
                        var container = document.getElementById('aj-day-activities-' + dayIdVal);
                        if (container) {
                            container.innerHTML = resp.data.html;
                        }
                        AJTB.showToast(resp.data.message || (action === 'remove' ? 'Activité retirée' : 'Activité ajoutée'));
                    } else {
                        var msg = (resp.data && resp.data.message) ? resp.data.message : 'Erreur';
                        AJTB.showToast(msg);
                    }
                }).fail(function(xhr, status, err) {
                    console.warn('AJ TB request failed', status, err);
                    AJTB.showToast('Erreur réseau');
                }).always(function() {
                    btn.disabled = false;
                });
            });
        },

        /**
         * Flight toggle (add/remove) — event delegation, replace #ajtb-flights-container with response HTML
         */
        initFlightToggle: function() {
            var ajtbData = typeof window.ajtbData !== 'undefined' ? window.ajtbData : {};
            var ajaxUrl = ajtbData.ajax_url || ajtbData.ajaxUrl || '';
            var flightNonce = ajtbData.flight_nonce || '';
            var sessionToken = ajtbData.session_token || '';
            var tourId = ajtbData.tour_id || ajtbData.postId || 0;

            if (!ajaxUrl || !flightNonce) return;

            document.addEventListener('click', function(e) {
                var btn = e.target && e.target.closest ? (e.target.closest('.ajtb-btn-remove-flight') || e.target.closest('.ajtb-btn-add-flight')) : null;
                if (!btn) return;

                var tourIdVal = parseInt(btn.getAttribute('data-tour-id'), 10) || tourId;
                var flightIdVal = parseInt(btn.getAttribute('data-flight-id'), 10);
                var toggleAction = btn.getAttribute('data-toggle-action');
                if (!flightIdVal || !toggleAction) return;

                if (btn.disabled) return;
                btn.disabled = true;

                $.post(ajaxUrl, {
                    action: 'ajtb_toggle_flight',
                    nonce: flightNonce,
                    tour_id: tourIdVal,
                    flight_id: flightIdVal,
                    toggle_action: toggleAction,
                    session_token: sessionToken
                }).done(function(resp) {
                    if (resp.success && resp.data && resp.data.html !== undefined) {
                        var container = document.getElementById('ajtb-flights-container');
                        if (container) {
                            container.innerHTML = resp.data.html;
                        }
                        AJTB.showToast(toggleAction === 'removed' ? 'Vol retiré' : 'Vol ajouté');
                    } else {
                        AJTB.showToast((resp.data && resp.data.message) ? resp.data.message : 'Erreur');
                    }
                }).fail(function() {
                    AJTB.showToast('Erreur réseau');
                }).always(function() {
                    btn.disabled = false;
                });
            });
        },

        /**
         * FAQ accordion
         */
        initFAQAccordion: function() {
            $(document).on('click', '.faq-question', function() {
                var $item = $(this).closest('.faq-item');
                var $answer = $item.find('.faq-answer');
                var isActive = $item.hasClass('active');

                // Close all others
                $('.faq-item').removeClass('active');
                $('.faq-answer').css('max-height', '0');
                $('.faq-question').attr('aria-expanded', 'false');

                // Toggle current
                if (!isActive) {
                    $item.addClass('active');
                    $answer.css('max-height', $answer.get(0).scrollHeight + 'px');
                    $(this).attr('aria-expanded', 'true');
                }
            });
        },

        /**
         * Gallery lightbox
         */
        initGallery: function() {
            $(document).on('click', '[data-lightbox]', function(e) {
                e.preventDefault();
                
                var $this = $(this);
                var group = $this.data('lightbox');
                var $items = $('[data-lightbox="' + group + '"]');
                var index = $items.index($this);

                AJTB.openLightbox($items, index);
            });
        },

        /**
         * Open lightbox
         */
        openLightbox: function($items, startIndex) {
            var currentIndex = startIndex;
            var images = [];

            $items.each(function() {
                images.push({
                    src: $(this).attr('href'),
                    alt: $(this).find('img').attr('alt') || ''
                });
            });

            // Create lightbox
            var $lightbox = $('<div class="ajtb-lightbox">' +
                '<div class="lightbox-backdrop"></div>' +
                '<div class="lightbox-container">' +
                    '<button class="lightbox-close" aria-label="Fermer">&times;</button>' +
                    '<button class="lightbox-nav prev" aria-label="Précédent">&lsaquo;</button>' +
                    '<div class="lightbox-content"><img src="" alt=""></div>' +
                    '<button class="lightbox-nav next" aria-label="Suivant">&rsaquo;</button>' +
                    '<div class="lightbox-counter"></div>' +
                '</div>' +
            '</div>');

            // Styles
            $lightbox.css({
                position: 'fixed',
                inset: 0,
                zIndex: 99999,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center'
            });

            $lightbox.find('.lightbox-backdrop').css({
                position: 'absolute',
                inset: 0,
                background: 'rgba(0,0,0,0.92)'
            });

            $lightbox.find('.lightbox-container').css({
                position: 'relative',
                maxWidth: '90%',
                maxHeight: '90%',
                display: 'flex',
                alignItems: 'center'
            });

            $lightbox.find('.lightbox-content img').css({
                maxWidth: '100%',
                maxHeight: '85vh',
                display: 'block',
                borderRadius: '8px'
            });

            var btnStyle = {
                position: 'absolute',
                background: 'rgba(255,255,255,0.15)',
                color: '#fff',
                border: 'none',
                cursor: 'pointer',
                transition: 'background 0.2s'
            };

            $lightbox.find('.lightbox-close').css($.extend({}, btnStyle, {
                top: '20px',
                right: '20px',
                width: '40px',
                height: '40px',
                borderRadius: '50%',
                fontSize: '24px',
                zIndex: 10
            }));

            $lightbox.find('.lightbox-nav').css($.extend({}, btnStyle, {
                top: '50%',
                transform: 'translateY(-50%)',
                width: '50px',
                height: '50px',
                borderRadius: '50%',
                fontSize: '28px'
            }));

            $lightbox.find('.lightbox-nav.prev').css('left', '20px');
            $lightbox.find('.lightbox-nav.next').css('right', '20px');

            $lightbox.find('.lightbox-counter').css({
                position: 'absolute',
                bottom: '20px',
                left: '50%',
                transform: 'translateX(-50%)',
                color: '#fff',
                fontSize: '14px'
            });

            // Show image
            function showImage(index) {
                currentIndex = index;
                if (currentIndex < 0) currentIndex = images.length - 1;
                if (currentIndex >= images.length) currentIndex = 0;

                $lightbox.find('.lightbox-content img')
                    .attr('src', images[currentIndex].src)
                    .attr('alt', images[currentIndex].alt);

                $lightbox.find('.lightbox-counter')
                    .text((currentIndex + 1) + ' / ' + images.length);
            }

            // Events
            $lightbox.find('.lightbox-backdrop, .lightbox-close').on('click', function() {
                $lightbox.remove();
                $('body').css('overflow', '');
            });

            $lightbox.find('.lightbox-nav.prev').on('click', function() {
                showImage(currentIndex - 1);
            });

            $lightbox.find('.lightbox-nav.next').on('click', function() {
                showImage(currentIndex + 1);
            });

            // Keyboard
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
         * Share button
         */
        initShareButton: function() {
            $(document).on('click', '#share-tour', function() {
                var url = $(this).data('url');
                var title = document.title;

                if (navigator.share) {
                    navigator.share({
                        title: title,
                        url: url
                    }).catch(function() {});
                } else {
                    // Copy to clipboard
                    AJTB.copyToClipboard(url);
                    AJTB.showToast('Lien copié !');
                }
            });
        },

        /**
         * Save/wishlist button
         */
        initSaveButton: function() {
            var savedTours = this.getSavedTours();

            // Check if current tour is saved
            var currentTourId = $('#save-tour').data('tour-id');
            if (currentTourId && savedTours.indexOf(currentTourId.toString()) !== -1) {
                $('#save-tour').addClass('active');
            }

            $(document).on('click', '#save-tour', function() {
                var $btn = $(this);
                var tourId = $btn.data('tour-id').toString();
                var savedTours = AJTB.getSavedTours();

                if ($btn.hasClass('active')) {
                    // Remove
                    $btn.removeClass('active');
                    savedTours = savedTours.filter(function(id) { return id !== tourId; });
                    AJTB.showToast('Retiré des favoris');
                } else {
                    // Add
                    $btn.addClass('active');
                    if (savedTours.indexOf(tourId) === -1) {
                        savedTours.push(tourId);
                    }
                    AJTB.showToast('Ajouté aux favoris !');
                }

                AJTB.saveTours(savedTours);
            });
        },

        /**
         * Get saved tours from localStorage
         */
        getSavedTours: function() {
            try {
                return JSON.parse(localStorage.getItem('ajtb_saved_tours')) || [];
            } catch (e) {
                return [];
            }
        },

        /**
         * Save tours to localStorage
         */
        saveTours: function(tours) {
            try {
                localStorage.setItem('ajtb_saved_tours', JSON.stringify(tours));
            } catch (e) {}
        },

        /**
         * Copy to clipboard
         */
        copyToClipboard: function(text) {
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
        },

        /**
         * Show toast notification
         */
        showToast: function(message) {
            var $toast = $('<div class="ajtb-toast">' + message + '</div>');
            $toast.css({
                position: 'fixed',
                bottom: '30px',
                left: '50%',
                transform: 'translateX(-50%) translateY(20px)',
                padding: '12px 24px',
                background: '#1a1a1a',
                color: '#fff',
                borderRadius: '8px',
                fontSize: '14px',
                fontWeight: '500',
                zIndex: 99999,
                opacity: 0,
                transition: 'all 0.3s ease'
            });

            $('body').append($toast);

            setTimeout(function() {
                $toast.css({ opacity: 1, transform: 'translateX(-50%) translateY(0)' });
            }, 10);

            setTimeout(function() {
                $toast.css({ opacity: 0, transform: 'translateX(-50%) translateY(20px)' });
                setTimeout(function() { $toast.remove(); }, 300);
            }, 2500);
        },

        /**
         * Smooth scroll
         */
        initSmoothScroll: function() {
            $(document).on('click', 'a[href^="#"]:not([data-lightbox])', function(e) {
                var target = $(this.getAttribute('href'));
                if (target.length) {
                    e.preventDefault();
                    $('html, body').animate({
                        scrollTop: target.offset().top - 80
                    }, 500);
                }
            });

            // Scroll down from hero
            $(document).on('click', '.ajtb-hero-scroll', function() {
                var $content = $('.ajtb-tour-layout');
                if ($content.length) {
                    $('html, body').animate({
                        scrollTop: $content.offset().top - 20
                    }, 500);
                }
            });
        },

        /**
         * Sticky nav highlight on scroll
         */
        initStickyNav: function() {
            var $tabs = $('.ajtb-tabs-nav');
            if (!$tabs.length) return;

            var sections = [];
            $tabs.find('.tab-link').each(function() {
                var href = $(this).attr('href');
                if ($(href).length) {
                    sections.push({
                        link: $(this),
                        section: $(href)
                    });
                }
            });

            $(window).on('scroll', function() {
                var scrollPos = $(window).scrollTop() + 100;

                sections.forEach(function(item) {
                    var sectionTop = item.section.offset().top;
                    var sectionBottom = sectionTop + item.section.outerHeight();

                    if (scrollPos >= sectionTop && scrollPos < sectionBottom) {
                        $tabs.find('.tab-link').removeClass('active');
                        item.link.addClass('active');
                    }
                });
            });
        },

        /**
         * Search bar: 3 blocks, localStorage (start_from, travel_date, adults, children), guests panel with Apply, sync total
         */
        initSearchbar: function() {
            var self = this;
            var storageKey = 'aj_tb_search';
            var cookieName = 'aj_tb_search';
            var cookieDays = 30;

            function getStored() {
                try {
                    var tourId = $('#aj-searchbar').data('tour-id');
                    var key = tourId ? storageKey + '_' + tourId : storageKey;
                    var raw = localStorage.getItem(key);
                    if (raw) {
                        var parsed = JSON.parse(raw);
                        if (parsed && typeof parsed === 'object') {
                            return {
                                start_from: parsed.start_from !== undefined ? parsed.start_from : parsed.starting_from,
                                travel_date: parsed.travel_date !== undefined ? parsed.travel_date : parsed.travelling_on,
                                adults: parsed.adults,
                                children: parsed.children
                            };
                        }
                    }
                } catch (e) {}
                var match = document.cookie.match(new RegExp('(^| )' + cookieName + '=([^;]+)'));
                if (match) {
                    try {
                        var parsed = JSON.parse(decodeURIComponent(match[2]));
                        if (parsed && typeof parsed === 'object') {
                            return {
                                start_from: parsed.start_from !== undefined ? parsed.start_from : parsed.starting_from,
                                travel_date: parsed.travel_date !== undefined ? parsed.travel_date : parsed.travelling_on,
                                adults: parsed.adults,
                                children: parsed.children
                            };
                        }
                    } catch (e) {}
                }
                return {};
            }

            function setStored(data) {
                var payload = {
                    start_from: data.start_from !== undefined ? data.start_from : '',
                    travel_date: data.travel_date !== undefined ? data.travel_date : '',
                    adults: typeof data.adults === 'number' ? data.adults : 2,
                    children: typeof data.children === 'number' ? data.children : 0
                };
                try {
                    var tourId = $('#aj-searchbar').data('tour-id');
                    var key = tourId ? storageKey + '_' + tourId : storageKey;
                    localStorage.setItem(key, JSON.stringify(payload));
                } catch (e) {}
                var d = new Date();
                d.setTime(d.getTime() + cookieDays * 24 * 60 * 60 * 1000);
                document.cookie = cookieName + '=' + encodeURIComponent(JSON.stringify(payload)) + ';path=/;expires=' + d.toUTCString() + ';SameSite=Lax';
            }

            function getSearchbarState() {
                var $bar = $('#aj-searchbar');
                if (!$bar.length) return {};
                var dateVal = ($bar.find('#aj-search-date').val() || '').trim();
                var adults = parseInt($bar.find('#aj-panel-adults').text(), 10) || 0;
                var children = parseInt($bar.find('#aj-panel-children').text(), 10) || 0;
                var from = ($bar.find('#aj-search-from').val() || '').trim();
                return { start_from: from, travel_date: dateVal, adults: adults, children: children };
            }

            function setSearchbarDisplay(state) {
                var $bar = $('#aj-searchbar');
                if (!$bar.length) return;
                var $from = $bar.find('#aj-search-from');
                var $dateInput = $bar.find('#aj-search-date');
                var $dateDisplay = $bar.find('#aj-search-date-display');
                var $panelAdults = $bar.find('#aj-panel-adults');
                var $panelChildren = $bar.find('#aj-panel-children');
                if (state.start_from !== undefined && $from.length) $from.val(state.start_from);
                if (state.travel_date) {
                    $dateInput.val(state.travel_date);
                    var parts = state.travel_date.split('-');
                    if (parts.length === 3) $dateDisplay.text(parts[2] + '/' + parts[1] + '/' + parts[0]);
                    else $dateDisplay.text(state.travel_date);
                } else {
                    $dateInput.val('');
                    $dateDisplay.text($dateDisplay.attr('data-placeholder') || '');
                }
                if (typeof state.adults === 'number') { $panelAdults.text(state.adults); }
                if (typeof state.children === 'number') { $panelChildren.text(state.children); }
                updateGuestsSummary();
            }

            function updateGuestsSummary() {
                var a = parseInt($('#aj-panel-adults').text(), 10) || 0;
                var c = parseInt($('#aj-panel-children').text(), 10) || 0;
                var $s = $('#aj-guest-summary');
                if (!$s.length) return;
                var adultLabel = a === 1 ? 'Adulte' : 'Adultes';
                var text = a + ' ' + adultLabel;
                if (c > 0) text += ', ' + c + ' ' + (c === 1 ? 'Enfant' : 'Enfants');
                $s.text(text);
            }

            function syncToBookingForm(state) {
                var $date = $('#booking-date');
                var $adults = $('#adults');
                var $children = $('#children');
                if ($date.length && state.travel_date) $date.val(state.travel_date);
                if ($adults.length && typeof state.adults === 'number') $adults.val(state.adults);
                if ($children.length && typeof state.children === 'number') $children.val(state.children);
                if (typeof self.calculateTotal === 'function') self.calculateTotal();
            }

            function closeGuestsPanel() {
                var trigger = document.getElementById('aj-guest-trigger');
                var panel = document.getElementById('aj-guests-panel');
                if (trigger) trigger.setAttribute('aria-expanded', 'false');
                if (panel) panel.setAttribute('hidden', '');
            }

            // Guests trigger: open/close (data-aj-search="guests-trigger")
            $(document).on('click', '#aj-searchbar [data-aj-search="guests-trigger"]', function(e) {
                e.stopPropagation();
                var trigger = this;
                var panel = document.getElementById('aj-guests-panel');
                var open = trigger.getAttribute('aria-expanded') === 'true';
                trigger.setAttribute('aria-expanded', open ? 'false' : 'true');
                if (panel) {
                    if (open) panel.setAttribute('hidden', '');
                    else panel.removeAttribute('hidden');
                }
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#aj-searchbar').length) closeGuestsPanel();
            });

            // Starting from (data-aj-search="from")
            $(document).on('change', '#aj-searchbar [data-aj-search="from"]', function() {
                var state = getSearchbarState();
                setStored(state);
            });

            // Date (data-aj-search="date")
            $(document).on('change', '#aj-searchbar [data-aj-search="date"]', function() {
                var val = $(this).val() || '';
                var state = getSearchbarState();
                state.travel_date = val;
                setStored(state);
                var $display = $('#aj-search-date-display');
                if (val) {
                    var parts = val.split('-');
                    if (parts.length === 3) $display.text(parts[2] + '/' + parts[1] + '/' + parts[0]);
                    else $display.text(val);
                } else {
                    $display.text($display.attr('data-placeholder') || '');
                }
                syncToBookingForm(state);
            });

            // Panel +/- (data-aj-search="counter" / "plus" / "minus")
            $(document).on('click', '#aj-searchbar [data-aj-search="plus"], #aj-searchbar [data-aj-search="minus"]', function(e) {
                e.stopPropagation();
                var $counter = $(this).closest('[data-aj-search="counter"]');
                if (!$counter.length) return;
                var target = $counter.data('target');
                if (target !== 'adults' && target !== 'children') return;
                var $num = $counter.find('.aj-counter-num');
                var max = parseInt($counter.data('max'), 10) || 99;
                var min = parseInt($counter.data('min'), 10);
                if (target === 'children') min = 0;
                else if (target === 'adults') min = 1;
                var current = parseInt($num.text(), 10) || 0;
                if ($(this).data('aj-search') === 'plus') {
                    if (current < max) current++;
                } else {
                    if (current > min) current--;
                }
                $num.text(current);
            });

            // Apply: copy panel to state, close, persist, sync total (data-aj-search="guests-apply")
            $(document).on('click', '#aj-searchbar [data-aj-search="guests-apply"]', function(e) {
                e.stopPropagation();
                var state = getSearchbarState();
                setSearchbarDisplay(state);
                setStored(state);
                syncToBookingForm(state);
                closeGuestsPanel();
            });

            // On load: restore from localStorage, then update UI and total
            if ($('#aj-searchbar').length) {
                var saved = getStored();
                var state = getSearchbarState();
                if (saved.start_from !== undefined) state.start_from = saved.start_from;
                if (saved.travel_date) state.travel_date = saved.travel_date;
                if (typeof saved.adults === 'number') state.adults = saved.adults;
                if (typeof saved.children === 'number') state.children = saved.children;
                setSearchbarDisplay(state);
                setStored(state);
                syncToBookingForm(state);
            }
        }
    };

    // Expose globally
    window.AJTB = AJTB;

})(jQuery);
