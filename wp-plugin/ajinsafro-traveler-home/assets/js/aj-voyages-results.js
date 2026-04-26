(function () {
    'use strict';

    var root = document.getElementById('aj-voyages-booking');
    if (!root) {
        return;
    }

    var drawer = root.querySelector('#ajvb-mobile-drawer');
    var backdrop = root.querySelector('#ajvb-drawer-backdrop');
    var openButton = root.querySelector('#ajvb-open-filters');
    var closeButton = root.querySelector('#ajvb-close-filters');

    if (!drawer || !backdrop || !openButton || !closeButton) {
        return;
    }

    function openDrawer() {
        drawer.classList.add('active');
        backdrop.classList.add('active');
        document.body.classList.add('aj-voyages-drawer-open');
    }

    function closeDrawer() {
        drawer.classList.remove('active');
        backdrop.classList.remove('active');
        document.body.classList.remove('aj-voyages-drawer-open');
    }

    openButton.addEventListener('click', openDrawer);
    closeButton.addEventListener('click', closeDrawer);
    backdrop.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && drawer.classList.contains('active')) {
            closeDrawer();
        }
    });
})();
