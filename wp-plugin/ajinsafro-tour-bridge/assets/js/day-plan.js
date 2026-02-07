/**
 * Day Plan – Click day in left nav to show that day's content (itinerary).
 * Keeps one day active; optional "Read more" for long descriptions.
 *
 * @package AjinsafroTourBridge
 */
(function($) {
    'use strict';

    function initDayPlan() {
        var $container = $('.ajtb-day-plan');
        if (!$container.length) return;

        var $links = $container.find('.aj-day-link');
        var $panels = $container.find('.aj-day-panel');

        $links.on('click', function() {
            var index = parseInt($(this).data('day-index'), 10);
            if (isNaN(index)) return;

            $links.removeClass('active').attr('aria-selected', 'false');
            $(this).addClass('active').attr('aria-selected', 'true');

            $panels.removeClass('active').hide();
            var $panel = $panels.filter('[data-day-index="' + index + '"]');
            $panel.addClass('active').show();

            var $body = $panel.find('.day-body');
            if ($body.length && $body.is(':hidden')) {
                $body.slideDown(200);
                $panel.find('.day-toggle').attr('aria-expanded', 'true');
            }
        });

        $panels.filter(':not(.active)').hide();
        $links.attr('role', 'tab');
        $panels.attr('role', 'tabpanel');
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
            $card.addClass('aj-flight-card--removed').slideUp(200);
        });
    }

    $(function() {
        initDayPlan();
        initReadMore();
        initFlightRemove();
    });
})(jQuery);
