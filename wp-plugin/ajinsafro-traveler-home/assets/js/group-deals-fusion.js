(function () {
    'use strict';

    var root = document.getElementById('aj-groupdeals-fusion');
    if (!root) {
        return;
    }

    var openButton = root.querySelector('#ajgd-open-filters');
    var closeButton = root.querySelector('#ajgd-close-filters');
    var drawer = root.querySelector('#ajgd-mobile-drawer');
    var backdrop = root.querySelector('#ajgd-drawer-backdrop');
    var body = document.body;

    if (!openButton || !closeButton || !drawer || !backdrop) {
        return;
    }

    function openDrawer() {
        drawer.classList.add('active');
        backdrop.classList.add('active');
        body.classList.add('aj-groupdeals-drawer-open');
    }

    function closeDrawer() {
        drawer.classList.remove('active');
        backdrop.classList.remove('active');
        body.classList.remove('aj-groupdeals-drawer-open');
    }

    openButton.addEventListener('click', openDrawer);
    closeButton.addEventListener('click', closeDrawer);
    backdrop.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDrawer();
        }
    });
})();
