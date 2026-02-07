/**
 * Day Plan – Left nav: click day → smooth scroll to panel, expand if collapsed, set active.
 * All day panels stay visible on the right. Optional: IntersectionObserver updates active on scroll.
 *
 * @package AjinsafroTourBridge
 */
(function($) {
    'use strict';

    var SCROLL_OFFSET = 120;

    function setActive(day) {
        var dayNum = String(day);
        var $nav = $('.aj-day-plan-nav');
        if (!$nav.length) return;
        $nav.find('[data-aj-nav-day]').removeClass('active is-active').attr('aria-selected', 'false');
        $nav.find('[data-aj-nav-day="' + dayNum + '"]').addClass('active is-active').attr('aria-selected', 'true');
    }

    function expandPanelIfCollapsed($panel) {
        if (!$panel || !$panel.length) return;
        var $dayBody = $panel.find('.day-body');
        var $toggle = $panel.find('.day-toggle');
        if ($dayBody.length && $dayBody.is(':hidden')) {
            $dayBody.slideDown(200);
            $toggle.attr('aria-expanded', 'true');
        }
    }

    function scrollToDay(day) {
        var dayNum = String(day);
        var el = document.getElementById('aj-day-panel-' + dayNum);
        if (!el) return;

        var panel = $(el);
        expandPanelIfCollapsed(panel);

        var top = el.getBoundingClientRect().top + (window.pageYOffset || document.documentElement.scrollTop) - SCROLL_OFFSET;
        window.scrollTo({ top: top, behavior: 'smooth' });

        setActive(dayNum);
    }

    function initDayPlanNav() {
        var $container = $('.ajtb-day-plan');
        if (!$container.length) return;

        // All panels visible: no hide/show
        $container.find('.aj-day-panel').attr('role', 'tabpanel');
        $container.find('.aj-day-plan-nav [data-aj-nav-day]').attr('role', 'tab');

        // Click: scroll to day, expand, set active
        $(document).on('click', '[data-aj-nav-day]', function(e) {
            e.preventDefault();
            var day = $(this).attr('data-aj-nav-day');
            if (day) scrollToDay(day);
        });

        // IntersectionObserver: update active day on scroll
        var panels = document.querySelectorAll('#itinerary [data-aj-day-panel]');
        if (panels.length && 'IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.intersectionRatio < 0.4) return;
                    var day = entry.target.getAttribute('data-aj-day-panel');
                    if (day) setActive(day);
                });
            }, { root: null, rootMargin: '-' + (SCROLL_OFFSET + 40) + 'px 0px -50% 0px', threshold: [0.2, 0.4, 0.6] });

            panels.forEach(function(p) { observer.observe(p); });
        }
    }

    function initReadMore() {
        $(document).on('click', '#itinerary .aj-day-notes-read-more', function() {
            var $btn = $(this);
            var $wrap = $btn.closest('.aj-day-notes-wrap');
            var expanded = $btn.attr('aria-expanded') === 'true';
            $wrap.toggleClass('aj-day-notes-collapsed', expanded);
            $btn.attr('aria-expanded', !expanded).text(expanded ? 'Lire plus' : 'Lire moins');
        });
    }

    function initFlightRemove() {
        $(document).on('click', '#itinerary [data-aj-flight-remove]', function() {
            var $card = $(this).closest('.aj-flight-card');
            var $block = $card.closest('.ajtb-day-flight-block');
            if ($block.length) {
                $block.addClass('aj-flight-card--removed').slideUp(200);
            } else {
                $card.addClass('aj-flight-card--removed').slideUp(200);
            }
        });
    }

    $(function() {
        initDayPlanNav();
        initReadMore();
        initFlightRemove();
    });
})(jQuery);
