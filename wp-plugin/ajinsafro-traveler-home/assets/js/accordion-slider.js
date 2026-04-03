/**
 * AjiNsafro Accordion Slider
 * Matches Slide(3).html reference: autoplay bounce, pause on hover, click to expand.
 */
(function () {
	'use strict';

	function initAjiAccordion() {
		var slider = document.getElementById('aji-accordion');
		if (!slider) {
			return;
		}

		var panels = Array.prototype.slice.call(slider.querySelectorAll('.aji-panel'));
		if (!panels.length) {
			return;
		}

		var currentIndex = 0;
		var direction = 1;
		var autoplayTimer = null;
		var DELAY_MS = 5000;

		function setActive(index) {
			currentIndex = index;
			panels.forEach(function (panel, i) {
				if (i === index) {
					panel.classList.add('aji-panel--active');
				} else {
					panel.classList.remove('aji-panel--active');
				}
			});
		}

		function step() {
			if (currentIndex >= panels.length - 1) {
				direction = -1;
			} else if (currentIndex <= 0) {
				direction = 1;
			}
			setActive(currentIndex + direction);
		}

		function startAutoplay() {
			clearInterval(autoplayTimer);
			autoplayTimer = setInterval(step, DELAY_MS);
		}

		function stopAutoplay() {
			clearInterval(autoplayTimer);
			autoplayTimer = null;
		}

		panels.forEach(function (panel) {
			panel.addEventListener('click', function () {
				var idx = parseInt(panel.getAttribute('data-index'), 10);
				setActive(idx);
				stopAutoplay();
				startAutoplay();
			});

			panel.addEventListener('keydown', function (e) {
				var idx = parseInt(panel.getAttribute('data-index'), 10);
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					setActive(idx);
					stopAutoplay();
					startAutoplay();
				} else if (e.key === 'ArrowRight') {
					e.preventDefault();
					var next = (idx + 1) % panels.length;
					setActive(next);
					panels[next].focus();
					stopAutoplay();
					startAutoplay();
				} else if (e.key === 'ArrowLeft') {
					e.preventDefault();
					var prev = (idx - 1 + panels.length) % panels.length;
					setActive(prev);
					panels[prev].focus();
					stopAutoplay();
					startAutoplay();
				}
			});
		});

		slider.addEventListener('mouseenter', stopAutoplay);
		slider.addEventListener('mouseleave', startAutoplay);
		slider.addEventListener('focusin', stopAutoplay);
		slider.addEventListener('focusout', function () {
			if (!slider.contains(document.activeElement)) {
				startAutoplay();
			}
		});

		setActive(0);
		startAutoplay();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAjiAccordion);
	} else {
		initAjiAccordion();
	}
})();
