(function () {
    'use strict';

    function initAdminV6Sidebar() {
        var html = document.documentElement;
        var key = 'adminV6SidebarCollapsed';
        var toggle = document.getElementById('adminV6SidebarToggle');
        var sidebar = document.getElementById('aj-admin-v2-sidebar');
        var hamburger = document.getElementById('aj-admin-v2-hamburger');

        function setCollapsed(collapsed) {
            html.setAttribute('data-admin-v6-sidebar', collapsed ? 'collapsed' : 'expanded');
            try { window.localStorage.setItem(key, collapsed ? '1' : '0'); } catch (e) {}
        }

        function isMobile() {
            return window.matchMedia('(max-width: 1199.98px)').matches;
        }

        try {
            if (window.localStorage.getItem(key) === '1' && !isMobile()) {
                setCollapsed(true);
            } else {
                setCollapsed(false);
            }
        } catch (e) {
            setCollapsed(false);
        }

        if (toggle) {
            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                var collapsed = html.getAttribute('data-admin-v6-sidebar') === 'collapsed';
                setCollapsed(!collapsed);
            });
        }

        if (sidebar) {
            sidebar.addEventListener('click', function (event) {
                if (html.getAttribute('data-admin-v6-sidebar') !== 'collapsed') return;
                var target = event.target.closest('.aj-sidebar-v2__link, .aj-sidebar-v2__toggle');
                if (!target) return;
                var isLeafLink = target.matches('a.aj-sidebar-v2__link') && target.getAttribute('href') && target.getAttribute('href') !== 'javascript:void(0);';
                if (!isLeafLink) {
                    setCollapsed(false);
                }
            });
        }

        if (hamburger) {
            hamburger.addEventListener('click', function () {
                if (isMobile()) return;
                var collapsed = html.getAttribute('data-admin-v6-sidebar') === 'collapsed';
                setCollapsed(!collapsed);
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAdminV6Sidebar);
    } else {
        initAdminV6Sidebar();
    }
})();
