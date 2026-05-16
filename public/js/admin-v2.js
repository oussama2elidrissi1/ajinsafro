(function () {
    'use strict';

    function initAdminV2() {
        var sidebar   = document.getElementById('aj-admin-v2-sidebar');
        var overlay   = document.getElementById('aj-admin-v2-overlay');
        var hamburger = document.getElementById('aj-admin-v2-hamburger');

        // Defensive: remove any duplicate sidebar wrappers that may leak from cached/old layouts
        var root = document.getElementById('aj-admin-v2-root');
        if (root) {
            var sidebars = root.querySelectorAll('.aj-admin-v2-sidebar');
            for (var i = 1; i < sidebars.length; i++) {
                sidebars[i].remove();
            }
            var menus = root.querySelectorAll('.vertical-menu');
            for (var j = 0; j < menus.length; j++) {
                menus[j].style.display = 'none';
            }
        }

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
