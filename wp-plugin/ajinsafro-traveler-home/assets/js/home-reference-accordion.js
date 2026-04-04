(function () {
    'use strict';

    var DELAY_MS = 5000;
    var PANE_DELAY_MS = 100;

    function initAccordion(root) {
        if (!root || root.getAttribute('data-ajha-ref-ready') === '1') {
            return;
        }

        var slides = root.querySelectorAll('.ajha-ref-accordion__slide');
        if (!slides.length) {
            return;
        }

        root.setAttribute('data-ajha-ref-ready', '1');

        var panels = root.querySelectorAll('.ajha-ref-accordion__panel');
        var count = slides.length;
        var current = parseInt(root.getAttribute('data-start-index') || '0', 10);
        var delay = parseInt(root.getAttribute('data-delay') || String(DELAY_MS), 10);
        var autoplayEnabled = root.getAttribute('data-ajha-ref-autoplay') !== '0';
        var direction = 1;
        var timer = null;
        var token = 0;

        if (isNaN(current) || current < 0 || current >= count) {
            current = 0;
        }

        if (isNaN(delay) || delay < 2000) {
            delay = DELAY_MS;
        }

        function clearTimer() {
            if (timer !== null) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        function setActive(index) {
            var next = ((index % count) + count) % count;
            current = next;
            token += 1;

            slides.forEach(function (slide, slideIndex) {
                var panel = panels[slideIndex];
                if (slideIndex === next) {
                    var activeToken = token;
                    slide.classList.add('is-active');
                    slide.setAttribute('aria-expanded', 'true');
                    window.setTimeout(function () {
                        if (token === activeToken && panel) {
                            panel.classList.add('is-visible');
                        }
                    }, PANE_DELAY_MS);
                } else {
                    slide.classList.remove('is-active');
                    slide.setAttribute('aria-expanded', 'false');
                    if (panel) {
                        panel.classList.remove('is-visible');
                    }
                }
            });
        }

        function step() {
            if (current >= count - 1) {
                direction = -1;
            } else if (current <= 0) {
                direction = 1;
            }

            setActive(current + direction);
        }

        function startAutoplay() {
            clearTimer();
            if (!autoplayEnabled || count < 2) {
                return;
            }

            timer = window.setInterval(step, delay);
        }

        slides.forEach(function (slide, index) {
            slide.addEventListener('click', function (event) {
                if (event.target && event.target.closest('a')) {
                    return;
                }

                setActive(index);
                if (autoplayEnabled) {
                    startAutoplay();
                }
            });

            slide.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }

                if (event.target && event.target.closest('a')) {
                    return;
                }

                event.preventDefault();
                setActive(index);
                if (autoplayEnabled) {
                    startAutoplay();
                }
            });
        });

        root.addEventListener('mouseenter', clearTimer);
        root.addEventListener('mouseleave', startAutoplay);
        root.addEventListener('focusin', clearTimer);
        root.addEventListener('focusout', function () {
            if (autoplayEnabled && !root.contains(document.activeElement)) {
                startAutoplay();
            }
        });

        setActive(current);
        if (autoplayEnabled) {
            startAutoplay();
        }
    }

    function boot() {
        document.querySelectorAll('[data-ajha-ref-accordion="1"]').forEach(initAccordion);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
