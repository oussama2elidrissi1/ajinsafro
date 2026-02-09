/**
 * Day Plan – Layout MakeMyTrip: tous les jours affichés; clic sur un jour = scroll vers ce jour.
 * Left: sticky day list. Center: tous les panneaux visibles. Right: sidebar prix (page level).
 *
 * @package AjinsafroTourBridge
 */
(function($) {
    'use strict';

    function setActive(dayNum) {
        dayNum = String(dayNum);
        var $nav = $('.aj-day-plan-nav');
        if (!$nav.length) return;
        $nav.find('[data-aj-nav-day]').removeClass('active is-active').attr('aria-selected', 'false');
        $nav.find('[data-aj-nav-day="' + dayNum + '"]').addClass('active is-active').attr('aria-selected', 'true');
    }

    function scrollToDay(dayNum) {
        dayNum = String(dayNum);
        setActive(dayNum);
        var el = document.getElementById('aj-day-panel-' + dayNum);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function initDayPlanNav() {
        var $container = $('.ajtb-programme-mmt, .ajtb-day-plan');
        if (!$container.length) return;

        var $panels = $('#itinerary .ajtb-day-content-panel');
        var $tabs = $('#itinerary .aj-day-plan-nav [data-aj-nav-day]');
        if ($panels.length && $tabs.length) {
            $panels.attr('role', 'tabpanel');
            $tabs.attr('role', 'tab');
            // Click: scroll vers le panneau du jour (tous les jours restent affichés)
            $(document).on('click', '#itinerary [data-aj-nav-day]', function(e) {
                e.preventDefault();
                var day = $(this).attr('data-aj-nav-day');
                if (day) scrollToDay(day);
            });
            return;
        }

        // Fallback: legacy timeline (scroll to day)
        var $legacyPanels = $('#itinerary .aj-day-panel');
        if ($legacyPanels.length) {
            $legacyPanels.attr('role', 'tabpanel');
            $container.find('.aj-day-plan-nav [data-aj-nav-day]').attr('role', 'tab');
            $(document).on('click', '[data-aj-nav-day]', function(e) {
                e.preventDefault();
                var day = $(this).attr('data-aj-nav-day');
                if (day) {
                    setActive(day);
                    var el = document.getElementById('aj-day-panel-' + day);
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
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
