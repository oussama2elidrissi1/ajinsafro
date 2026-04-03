(function () {
  'use strict';

  function initAjiSlider() {
    var sliders = document.querySelectorAll('#aji-accordion-slider, .aji-accordion-slider');

    sliders.forEach(function (slider) {
      var slides = Array.prototype.slice.call(slider.querySelectorAll('.aji-slide'));
      if (!slides.length) {
        return;
      }

      var tabs = Array.prototype.slice.call(slider.querySelectorAll('.aji-tab-bar'));
      var current = 0;
      var direction = 1;
      var timer = null;
      var delayMs = 5000;

      function setActive(index) {
        var next = ((index % slides.length) + slides.length) % slides.length;
        current = next;

        slides.forEach(function (slide, i) {
          var active = i === current;
          slide.classList.toggle('aji-slide--active', active);
          if (tabs[i]) {
            tabs[i].setAttribute('aria-pressed', active ? 'true' : 'false');
          }
        });
      }

      function step() {
        if (current >= slides.length - 1) {
          direction = -1;
        } else if (current <= 0) {
          direction = 1;
        }
        setActive(current + direction);
      }

      function stopAutoplay() {
        if (timer) {
          clearInterval(timer);
          timer = null;
        }
      }

      function startAutoplay() {
        stopAutoplay();
        timer = setInterval(step, delayMs);
      }

      function resetAutoplay() {
        stopAutoplay();
        startAutoplay();
      }

      tabs.forEach(function (tab, i) {
        tab.addEventListener('click', function () {
          setActive(i);
          resetAutoplay();
        });

        tab.addEventListener('keydown', function (e) {
          if (e.key === 'ArrowRight') {
            e.preventDefault();
            var next = (i + 1) % tabs.length;
            setActive(next);
            tabs[next].focus();
            resetAutoplay();
          } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            var prev = (i - 1 + tabs.length) % tabs.length;
            setActive(prev);
            tabs[prev].focus();
            resetAutoplay();
          } else if (e.key === 'Home') {
            e.preventDefault();
            setActive(0);
            tabs[0].focus();
            resetAutoplay();
          } else if (e.key === 'End') {
            e.preventDefault();
            var last = tabs.length - 1;
            setActive(last);
            tabs[last].focus();
            resetAutoplay();
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
    });
  }

  function scheduleInit() {
    setTimeout(function () {
      initAjiSlider();
    }, 100);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scheduleInit);
  } else {
    scheduleInit();
  }
})();
