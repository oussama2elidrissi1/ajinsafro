/**
 * Ajinsafro Traveler Home — home.js
 * Mobile menu + Tab switching + horizontal slider prev/next
 */
(function(){
    'use strict';

    /* ── Mobile burger menu ───────────────────────────────────── */
    var burger = document.getElementById('aj-burger');
    var navMenu = document.getElementById('aj-nav-menu');
    if (burger && navMenu) {
        burger.addEventListener('click', function () {
            var open = navMenu.classList.toggle('aj-menu-open');
            burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', function (e) {
            if (!burger.contains(e.target) && !navMenu.contains(e.target)) {
                navMenu.classList.remove('aj-menu-open');
                burger.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /* ── Tabs ──────────────────────────────────────────────────── */
    var tabs = document.querySelectorAll('.aj-tab');
    var postTypeInput = document.querySelector('.aj-search__form input[name="post_type"]');
    var map = {
        voyage:'st_tours', hebergement:'st_hotel', activites:'st_activity',
        location:'st_rental', transport:'st_cars', guide:'st_tours'
    };
    tabs.forEach(function(tab){
        tab.addEventListener('click', function(){
            tabs.forEach(function(t){ t.classList.remove('aj-tab--on'); });
            tab.classList.add('aj-tab--on');
            if (postTypeInput && map[tab.dataset.tab]) {
                postTypeInput.value = map[tab.dataset.tab];
            }
        });
    });

    /* ── Slider ────────────────────────────────────────────────── */
    var track = document.getElementById('aj-lm-track');
    if (track) {
        var prevBtn = document.querySelector('.aj-arrow--prev');
        var nextBtn = document.querySelector('.aj-arrow--next');

        function scrollAmt(){
            var card = track.querySelector('.aj-card');
            if (!card) return 320;
            var gap = parseFloat(getComputedStyle(track).gap) || 22;
            return card.offsetWidth + gap;
        }

        if (prevBtn) prevBtn.addEventListener('click', function(){
            track.scrollBy({ left: -scrollAmt(), behavior:'smooth' });
        });
        if (nextBtn) nextBtn.addEventListener('click', function(){
            track.scrollBy({ left: scrollAmt(), behavior:'smooth' });
        });
    }
})();
