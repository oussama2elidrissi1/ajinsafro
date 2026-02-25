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
            this.initActivityModal();
            this.initActivityEditModal();
            this.initFlightToggle();
            this.initFAQAccordion();
            this.initGallery();
            this.initHeroGallerySlider();
            this.initShareButton();
            this.initSaveButton();
            this.initSmoothScroll();
            this.initStickyNav();
            this.initSearchbar();
            this.initFlightsByDepartureFilter();
        },

        /**
         * Normalize "Starting from" from the selected <option> of #aj-search-from.
         * Priorité: option sélectionnée → out.id = opt.dataset.id (si numérique), out.code = opt.dataset.code, out.name = opt.textContent.trim().
         * Ne dépend pas du JSON data-departure-places.
         */
        normalizeStartingFrom: function() {
            var out = { id: '', name: '', code: '' };
            var select = document.getElementById('aj-search-from');
            if (!select || select.selectedIndex < 0) return out;
            var opt = select.options[select.selectedIndex];
            if (!opt || opt.value === '') return out;
            var dataId = (opt.dataset && opt.dataset.id != null) ? String(opt.dataset.id).trim() : '';
            var dataCode = (opt.dataset && opt.dataset.code != null) ? String(opt.dataset.code).trim() : '';
            var labelText = (opt.textContent || opt.innerText || '').replace(/\s+/g, ' ').trim();
            if (/^\d+$/.test(dataId)) out.id = dataId;
            out.code = dataCode;
            out.name = labelText !== '' ? labelText : ((opt.value != null && opt.value !== '') ? String(opt.value).trim() : '');
            return out;
        },

        /**
         * Filter by "Starting from" only:
         * - #aj-flight-details .aj-flight-card
         * - Programme Vol Aller: .ajtb-day-flight-block.ajtb-day-flight-outbound[data-aj-day-number="1"] .aj-flight-card
         * Match by priority: id == data-departure-place-id OR code == data-departure-place-code OR name == data-departure-place-name (case-insensitive).
         * No filter on #flights section.
         */
        applyFlightsByDeparture: function() {
            var $from = $('#aj-search-from');
            var rawVal = ($from.length && $from.val()) ? String($from.val()).trim() : '';
            var normalized = this.normalizeStartingFrom();
            var hasNorm = (normalized.id !== '' || normalized.name !== '' || normalized.code !== '');
            var nameOnlyNumeric = /^\d+$/.test(normalized.name);
            var showAll = rawVal === '' || !hasNorm || (normalized.id === '' && normalized.code === '' && nameOnlyNumeric);

            function normStr(s) {
                if (s == null || s === '') return '';
                return String(s).replace(/\s+/g, ' ').trim().toLowerCase();
            }
            function nameMatch(a, b) {
                if (a === '' || b === '') return false;
                var na = normStr(a).replace(/\s*\([^)]*\)\s*$/, '').trim();
                var nb = normStr(b).replace(/\s*\([^)]*\)\s*$/, '').trim();
                return na === nb || normStr(a) === normStr(b);
            }

            function cardMatches($card, norm) {
                if (showAll) return true;
                var id = ($card.attr('data-departure-place-id') != null) ? String($card.attr('data-departure-place-id')).trim() : '';
                var code = ($card.attr('data-departure-place-code') != null) ? String($card.attr('data-departure-place-code')).trim() : '';
                var name = ($card.attr('data-departure-place-name') != null) ? String($card.attr('data-departure-place-name')).trim() : '';
                if (/^\d+$/.test(norm.id) && id === norm.id) return true;
                if (norm.code !== '' && code === norm.code) return true;
                if (norm.name !== '' && nameMatch(norm.name, name)) return true;
                return false;
            }

            var selectorOutbound = '.ajtb-day-flight-block.ajtb-day-flight-outbound[data-aj-day-number="1"] .aj-flight-card';
            var $cardsDetails = $('#aj-flight-details .aj-flight-card');
            var $cardsOutbound = $(selectorOutbound);

            var allCards = $cardsDetails.add($cardsOutbound);
            var tableData = [];
            var visibleCount = 0;

            allCards.each(function() {
                var $card = $(this);
                var id = ($card.attr('data-departure-place-id') != null) ? String($card.attr('data-departure-place-id')) : '';
                var name = ($card.attr('data-departure-place-name') != null) ? String($card.attr('data-departure-place-name')) : '';
                var code = ($card.attr('data-departure-place-code') != null) ? String($card.attr('data-departure-place-code')) : '';
                var show = cardMatches($card, normalized);
                if (show) visibleCount++;
                tableData.push({ cardId: id, cardName: name, cardCode: code, match: show });
                if (show) $card.show(); else $card.hide();
            });

            if (!showAll && visibleCount === 0 && allCards.length > 0) {
                allCards.show();
                if (typeof console !== 'undefined' && console.warn) {
                    console.warn('[AJTB Starting from] Aucun match: affichage de tous les vols. raw:', rawVal, 'normalized:', normalized, 'cards:', tableData);
                }
            }

            if (typeof console !== 'undefined' && console.log) {
                console.log('[AJTB Starting from] raw:', rawVal, '| normalized:', normalized);
                console.log('[AJTB Starting from] cards.length', { details: $cardsDetails.length, outbound: $cardsOutbound.length, total: allCards.length });
            }
            if (typeof console !== 'undefined' && console.table && tableData.length) {
                console.table(tableData);
            }
        },

        initFlightsByDepartureFilter: function() {
            // Filter is triggered on change in initSearchbar and after localStorage restore below
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
            // Calculate total (which also updates cart)
            this.calculateTotal();
        },

        /**
         * Get activities added by client (client_added=true AND price > 0)
         * Returns array of {title, price, day_id, activity_id}
         */
        getAddedActivities: function() {
            var activities = [];
            // Find all activities marked as client_added (added by user)
            $('.day-activity-item[data-client-added="true"]').each(function() {
                var $item = $(this);
                var title = $item.find('.activity-title').text().trim() || '';
                var priceText = $item.find('.activity-price').text().trim();
                var price = 0;
                if (priceText) {
                    price = parseFloat(priceText.replace(/[^\d.]/g, '')) || 0;
                }
                // Only include if price > 0 (activities with price)
                // Activities without price or price=0 are considered "included" and don't affect total
                if (price > 0) {
                    activities.push({
                        title: title,
                        price: price,
                        day_id: $item.data('day-id') || 0,
                        activity_id: $item.data('activity-id') || 0
                    });
                }
            });
            return activities;
        },

        /**
         * Update cart display with base + activities
         */
        updateCart: function() {
            var adults = parseInt($('#adults').val(), 10) || 0;
            var children = parseInt($('#children').val(), 10) || 0;
            var adultPrice = this.config.prices.adult || 0;
            var childPrice = this.config.prices.child !== undefined && this.config.prices.child !== null ? this.config.prices.child : 0;

            // Update base labels
            $('#cart-adults-count').text(adults);
            if ($('#cart-children-count').length) {
                $('#cart-children-count').text(children);
            }

            // Update base values
            var baseAdultTotal = adults * adultPrice;
            $('#cart-base-value').text(this.formatPrice(baseAdultTotal) + ' ' + this.config.currency);
            if ($('#cart-child-value').length) {
                var baseChildTotal = children * childPrice;
                $('#cart-child-value').text(this.formatPrice(baseChildTotal) + ' ' + this.config.currency);
            }

            // Get and display activities
            var activities = this.getAddedActivities();
            var $activitiesList = $('#cart-activities-list');
            var $activitiesWrapper = $('#cart-activities-wrapper');
            $activitiesList.empty();

            if (activities.length > 0) {
                $activitiesWrapper.show();
                var totalActivities = 0;
                activities.forEach(function(act) {
                    totalActivities += act.price;
                    var $item = $('<li class="ajtb-activity-row"></li>');
                    $item.append('<span class="ajtb-activity-name">' + this.escapeHtml(act.title) + '</span>');
                    $item.append('<span class="ajtb-activity-price">' + this.formatPrice(act.price) + ' ' + this.config.currency + '</span>');
                    $activitiesList.append($item);
                }.bind(this));
            } else {
                $activitiesWrapper.hide();
            }
        },

        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        /**
         * Calculate total from quantities + activities
         */
        calculateTotal: function() {
            var adults = parseInt($('#adults').val(), 10) || 0;
            var children = parseInt($('#children').val(), 10) || 0;

            var adultPrice = this.config.prices.adult || 0;
            var childPrice = this.config.prices.child !== undefined && this.config.prices.child !== null ? this.config.prices.child : 0;

            var baseTotal = (adults * adultPrice) + (children * childPrice);

            // Add activities prices (only non-included, client-added)
            var activities = this.getAddedActivities();
            var activitiesTotal = 0;
            activities.forEach(function(act) {
                activitiesTotal += act.price;
            });

            var total = baseTotal + activitiesTotal;

            // Update cart display
            this.updateCart();

            // Update total
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
                var $activityItem = null;
                
                if (action === 'remove') {
                    activityIdVal = parseInt(btn.getAttribute('data-activity-id'), 10);
                    // Find the parent activity item for instant removal
                    $activityItem = $(btn).closest('.day-activity-item');
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

                // INSTANT FEEDBACK: Remove activity item immediately (for "remove" action)
                var itemHtml = null;
                var container = document.getElementById('aj-day-activities-' + dayIdVal);
                var $list = container ? $(container).find('.day-activities-list') : null;
                
                if (action === 'remove' && $activityItem && $activityItem.length) {
                    itemHtml = $activityItem[0].outerHTML; // Store for potential restore
                    $activityItem.fadeOut(150, function() {
                        $(this).remove();
                    });
                }

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

                // AJAX call in background
                $.post(ajaxUrl, payload).done(function(resp) {
                    console.log('AJ TB response', resp);
                    if (resp.success && resp.data && resp.data.html !== undefined) {
                        if (container) {
                            container.innerHTML = resp.data.html;
                        }
                        AJTB.showToast(resp.data.message || (action === 'remove' ? 'Activité retirée' : 'Activité ajoutée'));
                        // Recalculate total with activities
                        if (typeof AJTB.calculateTotal === 'function') {
                            AJTB.calculateTotal();
                        }
                    } else {
                        // On error: restore item if it was removed
                        if (action === 'remove' && itemHtml && $list && $list.length) {
                            $list.prepend(itemHtml);
                            $list.find('.day-activity-item').first().hide().fadeIn(150);
                        }
                        var msg = (resp.data && resp.data.message) ? resp.data.message : 'Erreur';
                        AJTB.showToast(msg);
                        btn.disabled = false;
                    }
                }).fail(function(xhr, status, err) {
                    console.warn('AJ TB request failed', status, err);
                    // On error: restore item if it was removed
                    if (action === 'remove' && itemHtml && $list && $list.length) {
                        $list.prepend(itemHtml);
                        $list.find('.day-activity-item').first().hide().fadeIn(150);
                    }
                    AJTB.showToast('Erreur réseau');
                    btn.disabled = false;
                });
            });
        },

        /**
         * Activity Modal: open modal, search, load activities, add activity
         */
        initActivityModal: function() {
            var self = this;
            var $modal = $('#ajtb-activity-modal');
            if (!$modal.length) return;

            var currentTourId = 0;
            var currentDayId = 0;
            var currentPage = 1;
            var currentSearch = '';
            var searchTimeout = null;
            var isLoading = false;

            // Open modal
            $(document).on('click', '.ajtb-btn-open-activity-modal', function(e) {
                e.preventDefault();
                currentTourId = parseInt($(this).attr('data-tour-id'), 10) || 0;
                currentDayId = parseInt($(this).attr('data-day-id'), 10) || 0;
                if (!currentTourId || !currentDayId) {
                    self.showToast('Paramètres manquants');
                    return;
                }
                $modal.attr('aria-hidden', 'false').addClass('is-open');
                currentPage = 1;
                currentSearch = '';
                $('#ajtb-activity-search').val('');
                self.loadActivitiesModal();
            });

            // Close modal
            $(document).on('click', '[data-ajtb-modal-close]', function(e) {
                e.preventDefault();
                $modal.attr('aria-hidden', 'true').removeClass('is-open');
            });

            // Search with debounce
            $('#ajtb-activity-search').on('input', function() {
                var search = $(this).val().trim();
                if (searchTimeout) clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    currentSearch = search;
                    currentPage = 1;
                    self.loadActivitiesModal();
                }, 300);
            });

            // Load activities
            this.loadActivitiesModal = function() {
                if (isLoading || !currentTourId || !currentDayId) return;
                isLoading = true;

                var $grid = $('#ajtb-activity-grid');
                var $loading = $('#ajtb-activity-loading');
                var $empty = $('#ajtb-activity-empty');
                var $pagination = $('#ajtb-activity-pagination');

                $grid.hide();
                $empty.hide();
                $pagination.hide();
                $loading.show();

                var ajaxUrl = typeof ajtbData !== 'undefined' && ajtbData.ajax_url ? ajtbData.ajax_url : ajaxurl;
                $.post(ajaxUrl, {
                    action: 'aj_get_activities_modal',
                    tour_id: currentTourId,
                    day_id: currentDayId,
                    search: currentSearch,
                    page: currentPage,
                    per_page: 12
                }).done(function(resp) {
                    $loading.hide();
                    if (resp.success && resp.data && resp.data.items) {
                        var items = resp.data.items;
                        if (items.length === 0) {
                            $empty.show();
                            $grid.hide();
                        } else {
                            $empty.hide();
                            $grid.html(self.renderActivityCards(items)).show();
                            if (resp.data.total_pages > 1) {
                                $pagination.html(self.renderActivityPagination(resp.data)).show();
                            } else {
                                $pagination.hide();
                            }
                        }
                    } else {
                        $empty.show();
                        $grid.hide();
                    }
                }).fail(function() {
                    $loading.hide();
                    $empty.show();
                    $grid.hide();
                    self.showToast('Erreur lors du chargement');
                }).always(function() {
                    isLoading = false;
                });
            };

            // Render activity cards
            this.renderActivityCards = function(items) {
                var html = '';
                items.forEach(function(item) {
                    var imageHtml = item.image_url 
                        ? '<img src="' + self.escapeHtml(item.image_url) + '" alt="' + self.escapeHtml(item.title) + '" loading="lazy">'
                        : '<div class="ajtb-activity-placeholder"><svg viewBox="0 0 24 24" width="48" height="48" stroke="currentColor" fill="none" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21,15 16,10 5,21"></polyline></svg></div>';
                    var priceHtml = item.base_price !== null && item.base_price !== undefined
                        ? '<div class="ajtb-activity-price">' + self.formatPrice(item.base_price) + ' ' + self.config.currency + '</div>'
                        : '';
                    var durationHtml = item.duration_minutes
                        ? '<div class="ajtb-activity-duration"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" fill="none" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12,6 12,12 16,14"></polyline></svg> ' + item.duration_minutes + ' min</div>'
                        : '';
                    var locationHtml = item.location_text
                        ? '<div class="ajtb-activity-location">' + self.escapeHtml(item.location_text) + '</div>'
                        : '';
                    html += '<div class="ajtb-activity-card" data-activity-id="' + item.id + '">';
                    html += '<div class="ajtb-activity-card-image">' + imageHtml + '</div>';
                    html += '<div class="ajtb-activity-card-content">';
                    html += '<h3 class="ajtb-activity-card-title">' + self.escapeHtml(item.title) + '</h3>';
                    if (item.description) {
                        html += '<p class="ajtb-activity-card-desc">' + self.escapeHtml(item.description.length > 100 ? item.description.substring(0, 100) + '...' : item.description) + '</p>';
                    }
                    html += '<div class="ajtb-activity-card-meta">' + durationHtml + locationHtml + '</div>';
                    html += '<div class="ajtb-activity-card-footer">' + priceHtml;
                    html += '<button type="button" class="ajtb-btn-add-from-modal" data-activity-id="' + item.id + '" data-tour-id="' + currentTourId + '" data-day-id="' + currentDayId + '">' + self.escapeHtml('Ajouter') + '</button>';
                    html += '</div></div></div>';
                });
                return html;
            };

            // Render pagination
            this.renderActivityPagination = function(data) {
                var html = '<div class="ajtb-pagination">';
                if (data.page > 1) {
                    html += '<button type="button" class="ajtb-pagination-btn" data-page="' + (data.page - 1) + '">← Précédent</button>';
                }
                html += '<span class="ajtb-pagination-info">Page ' + data.page + ' / ' + data.total_pages + '</span>';
                if (data.page < data.total_pages) {
                    html += '<button type="button" class="ajtb-pagination-btn" data-page="' + (data.page + 1) + '">Suivant →</button>';
                }
                html += '</div>';
                return html;
            };

            // Pagination click
            $(document).on('click', '.ajtb-pagination-btn', function() {
                currentPage = parseInt($(this).attr('data-page'), 10);
                self.loadActivitiesModal();
                $modal.find('.ajtb-modal-body').scrollTop(0);
            });

            // Add activity from modal (INSTANTANEOUS - no reload)
            $(document).on('click', '.ajtb-btn-add-from-modal', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var activityId = parseInt($btn.attr('data-activity-id'), 10);
                var tourId = parseInt($btn.attr('data-tour-id'), 10);
                var dayId = parseInt($btn.attr('data-day-id'), 10);
                if (!activityId || !tourId || !dayId) return;

                if ($btn.prop('disabled')) return;
                
                // Store card HTML before disabling (for instant removal if needed)
                var $card = $btn.closest('.ajtb-activity-card');
                var cardHtml = $card[0] ? $card[0].outerHTML : null;
                
                // INSTANT FEEDBACK: Remove card immediately from modal
                $card.fadeOut(150, function() {
                    $(this).remove();
                    
                    // Check if grid is now empty
                    var $grid = $('#ajtb-activity-grid');
                    if ($grid.children().length === 0) {
                        $('#ajtb-activity-empty').show();
                        $grid.hide();
                    }
                });
                
                // Disable button
                $btn.prop('disabled', true).text('Ajout...');

                var ajaxUrl = typeof ajtbData !== 'undefined' && ajtbData.ajax_url ? ajtbData.ajax_url : ajaxurl;
                var nonce = typeof ajtbData !== 'undefined' && ajtbData.nonce ? ajtbData.nonce : '';
                var sessionToken = typeof ajtbData !== 'undefined' && ajtbData.session_token ? ajtbData.session_token : '';

                // AJAX call in background
                $.post(ajaxUrl, {
                    action: 'aj_toggle_activity',
                    nonce: nonce,
                    tour_id: tourId,
                    day_id: dayId,
                    activity_id: activityId,
                    toggle_action: 'added',
                    session_token: sessionToken
                }).done(function(resp) {
                    if (resp.success && resp.data && resp.data.html !== undefined) {
                        var container = document.getElementById('aj-day-activities-' + dayId);
                        if (container) {
                            container.innerHTML = resp.data.html;
                        }
                        self.showToast(resp.data.message || 'Activité ajoutée');
                        // Recalculate total with activities
                        if (typeof self.calculateTotal === 'function') {
                            self.calculateTotal();
                        }
                        setTimeout(function() {
                            $modal.attr('aria-hidden', 'true').removeClass('is-open');
                        }, 300);
                    } else {
                        var $grid = $('#ajtb-activity-grid');
                        if (cardHtml && $grid.length) {
                            $grid.prepend(cardHtml);
                            $grid.find('.ajtb-activity-card').last().hide().fadeIn(150);
                        }
                        var msg = (resp.data && resp.data.message) ? resp.data.message : 'Erreur';
                        self.showToast(msg);
                        $btn.prop('disabled', false).text('Ajouter');
                    }
                }).fail(function() {
                    var $grid = $('#ajtb-activity-grid');
                    if (cardHtml && $grid.length) {
                        $grid.prepend(cardHtml);
                        $grid.find('.ajtb-activity-card').last().hide().fadeIn(150);
                    }
                    self.showToast('Erreur réseau');
                    $btn.prop('disabled', false).text('Ajouter');
                });
            });

            // Escape HTML
            this.escapeHtml = function(text) {
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            };
        },

        /**
         * Activity Edit Modal: open edit modal, populate form, update activity
         */
        initActivityEditModal: function() {
            var self = this;
            var $modal = $('#ajtb-activity-edit-modal');
            if (!$modal.length) return;

            // Open edit modal
            $(document).on('click', '.ajtb-btn-edit-activity', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var dayActivityId = parseInt($btn.attr('data-day-activity-id'), 10);
                var tourId = parseInt($btn.attr('data-tour-id'), 10);
                var dayId = parseInt($btn.attr('data-day-id'), 10);
                var activityId = parseInt($btn.attr('data-activity-id'), 10);

                if (!dayActivityId || !tourId || !dayId || !activityId) return;

                // Get current activity data from DOM
                var $item = $btn.closest('.day-activity-item');
                var currentTitle = $item.find('.activity-title').text().trim();
                var currentDesc = $item.find('.activity-description').html() || '';
                var currentPrice = $item.find('.activity-price').text().trim().replace(/[^\d.]/g, '') || '';
                var startTime = $item.find('.activity-time span').first().text().trim() || '';
                var endTime = $item.find('.activity-time span').last().text().trim() || '';

                // Populate form
                $('#ajtb-edit-day-activity-id').val(dayActivityId);
                $('#ajtb-edit-tour-id').val(tourId);
                $('#ajtb-edit-day-id').val(dayId);
                $('#ajtb-edit-activity-id').val(activityId);
                $('#ajtb-edit-custom-title').val(currentTitle);
                $('#ajtb-edit-custom-description').val(currentDesc.replace(/<[^>]*>/g, ''));
                $('#ajtb-edit-custom-price').val(currentPrice);
                $('#ajtb-edit-start-time').val(startTime);
                $('#ajtb-edit-end-time').val(endTime);

                // Open modal
                $modal.attr('aria-hidden', 'false').addClass('is-open');
            });

            // Close modal
            $(document).on('click', '#ajtb-activity-edit-modal [data-ajtb-modal-close]', function(e) {
                e.preventDefault();
                $modal.attr('aria-hidden', 'true').removeClass('is-open');
            });

            // Submit form
            $('#ajtb-activity-edit-form').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var $submitBtn = $form.find('button[type="submit"]');
                
                if ($submitBtn.prop('disabled')) return;
                $submitBtn.prop('disabled', true).text('Enregistrement...');

                var ajaxUrl = typeof ajtbData !== 'undefined' && ajtbData.ajax_url ? ajtbData.ajax_url : ajaxurl;
                var sessionToken = typeof ajtbData !== 'undefined' && ajtbData.session_token ? ajtbData.session_token : '';

                $.post(ajaxUrl, {
                    action: 'aj_update_activity',
                    day_activity_id: $('#ajtb-edit-day-activity-id').val(),
                    tour_id: $('#ajtb-edit-tour-id').val(),
                    day_id: $('#ajtb-edit-day-id').val(),
                    activity_id: $('#ajtb-edit-activity-id').val(),
                    custom_title: $('#ajtb-edit-custom-title').val(),
                    custom_description: $('#ajtb-edit-custom-description').val(),
                    custom_price: $('#ajtb-edit-custom-price').val(),
                    start_time: $('#ajtb-edit-start-time').val(),
                    end_time: $('#ajtb-edit-end-time').val(),
                    session_token: sessionToken
                }).done(function(resp) {
                    if (resp.success && resp.data && resp.data.html !== undefined) {
                        var container = document.getElementById('aj-day-activities-' + resp.data.day_id);
                        if (container) {
                            container.innerHTML = resp.data.html;
                        }
                        self.showToast(resp.data.message || 'Activité mise à jour');
                        $modal.attr('aria-hidden', 'true').removeClass('is-open');
                    } else {
                        var msg = (resp.data && resp.data.message) ? resp.data.message : 'Erreur';
                        self.showToast(msg);
                        $submitBtn.prop('disabled', false).text('Enregistrer');
                    }
                }).fail(function() {
                    self.showToast('Erreur réseau');
                    $submitBtn.prop('disabled', false).text('Enregistrer');
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
         * Hero gallery slider (mobile)
         */
        initHeroGallerySlider: function() {
            var $slider = $('.ajtb-hero-gallery-slider');
            if (!$slider.length) return;

            var $track = $slider.find('.ajtb-hero-gallery-slider-track');
            var $slides = $track.find('.ajtb-hero-gallery-slide');
            var total = $slides.length;
            if (total === 0) return;

            var $dotsContainer = $slider.find('.ajtb-hero-gallery-slider-dots');
            var i, $dot;
            for (i = 0; i < total; i++) {
                $dot = $('<button type="button" class="ajtb-hero-gallery-slider-dot" aria-label="' + (i + 1) + '"></button>');
                $dotsContainer.append($dot);
            }
            var $dots = $dotsContainer.find('.ajtb-hero-gallery-slider-dot');
            $dots.eq(0).addClass('is-active');

            var current = 0;

            function goTo(index) {
                if (index < 0) index = total - 1;
                if (index >= total) index = 0;
                current = index;
                $track.css('transform', 'translateX(-' + (current * 100) + '%)');
                $dots.removeClass('is-active').eq(current).addClass('is-active');
            }

            $slider.find('.ajtb-hero-gallery-slider-prev').on('click', function() {
                goTo(current - 1);
            });

            $slider.find('.ajtb-hero-gallery-slider-next').on('click', function() {
                goTo(current + 1);
            });

            $dots.on('click', function() {
                var idx = $dots.index(this);
                goTo(idx);
            });

            // Swipe
            var startX = 0, startY = 0;
            $slider.on('touchstart', function(e) {
                startX = e.originalEvent.touches[0].clientX;
                startY = e.originalEvent.touches[0].clientY;
            });
            $slider.on('touchend', function(e) {
                var endX = e.originalEvent.changedTouches[0].clientX;
                var endY = e.originalEvent.changedTouches[0].clientY;
                var dx = endX - startX;
                var dy = endY - startY;
                if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 40) {
                    if (dx > 0) goTo(current - 1);
                    else goTo(current + 1);
                }
            });
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
                if (state.start_from !== undefined && $from.length) {
                    var wantedFrom = String(state.start_from).trim();
                    $from.val(wantedFrom);
                    if (($from.val() || '').toString().trim() !== wantedFrom && wantedFrom !== '') {
                        var lowerWanted = wantedFrom.toLowerCase();
                        var mappedValue = '';
                        $from.find('option').each(function() {
                            if (mappedValue !== '') return;
                            var $opt = $(this);
                            var optionValue = ($opt.val() || '').toString().trim();
                            var optionId = (($opt.data('id') || '') + '').trim();
                            var optionCode = (($opt.data('code') || '') + '').trim();
                            var optionLabel = ($opt.text() || '').replace(/\s+/g, ' ').trim();
                            var optionName = optionLabel.replace(/\s*\([^)]*\)\s*$/, '').trim();
                            if (
                                optionValue === wantedFrom ||
                                optionId === wantedFrom ||
                                optionCode.toLowerCase() === lowerWanted ||
                                optionName.toLowerCase() === lowerWanted ||
                                optionLabel.toLowerCase() === lowerWanted
                            ) {
                                mappedValue = optionValue;
                            }
                        });
                        if (mappedValue !== '') $from.val(mappedValue);
                    }
                }
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

            // Starting from (data-aj-search="from") — filter #aj-flight-details + Programme Vol Aller jour 1
            $(document).on('change', '#aj-searchbar [data-aj-search="from"]', function() {
                var state = getSearchbarState();
                setStored(state);
                if (typeof self.applyFlightsByDeparture === 'function') self.applyFlightsByDeparture();
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

            // On load: restore from localStorage, then update UI and total, then apply Starting from filter
            if ($('#aj-searchbar').length) {
                var saved = getStored();
                var state = getSearchbarState();
                if (saved.start_from !== undefined) state.start_from = saved.start_from;
                if (saved.travel_date) state.travel_date = saved.travel_date;
                if (typeof saved.adults === 'number') state.adults = saved.adults;
                if (typeof saved.children === 'number') state.children = saved.children;
                setSearchbarDisplay(state);
                state = getSearchbarState();
                setStored(state);
                syncToBookingForm(state);
                var $from = $('#aj-searchbar #aj-search-from');
                if ($from.length && ($from.val() || '').toString().trim() !== '') {
                    $from.trigger('change');
                } else if (typeof self.applyFlightsByDeparture === 'function') {
                    self.applyFlightsByDeparture();
                }
            }
        }
    };

    // Expose globally
    window.AJTB = AJTB;

})(jQuery);
