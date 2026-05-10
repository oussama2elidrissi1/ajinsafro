(function () {
    'use strict';

    function initAdminV2() {
        var sidebar   = document.getElementById('aj-admin-v2-sidebar');
        var overlay   = document.getElementById('aj-admin-v2-overlay');
        var hamburger = document.getElementById('aj-admin-v2-hamburger');

        if (!sidebar) return;

        function openSidebar() {
            sidebar.classList.add('open');
            if (overlay) overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('open');
            document.body.style.overflow = '';
        }

        if (hamburger) {
            hamburger.addEventListener('click', function () {
                sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
            });
        }

        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }

        // Close sidebar on ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                closeSidebar();
            }
        });

        // Close sidebar when a nav link is clicked on mobile
        sidebar.querySelectorAll('.aj-sidebar-v2__link:not(.aj-sidebar-v2__toggle)').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 900) {
                    closeSidebar();
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAdminV2);
    } else {
        initAdminV2();
    }

    window.AjAdminV2 = { init: initAdminV2 };
})();
