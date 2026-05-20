(function () {
    'use strict';

    function initAdminV6() {
        var html = document.documentElement;
        var key = 'adminV6SidebarCollapsed';

        var collapseBtn = document.getElementById('adminV6SidebarToggle');
        var sidebar = document.getElementById('adminV6Sidebar') || document.getElementById('aj-admin-v2-sidebar');
        var overlay = document.getElementById('adminV6Overlay') || document.getElementById('aj-admin-v2-overlay');
        var hamburger = document.getElementById('adminV6Hamburger') || document.getElementById('aj-admin-v2-hamburger');

        if (!sidebar) return;

        function isMobile() {
            return window.matchMedia('(max-width: 1199.98px)').matches;
        }

        function setCollapsed(collapsed) {
            html.setAttribute('data-admin-v6-sidebar', collapsed ? 'collapsed' : 'expanded');
            try { window.localStorage.setItem(key, collapsed ? '1' : '0'); } catch (e) { }
        }

        function openMobileSidebar() {
            sidebar.classList.add('is-open');
            if (overlay) overlay.classList.add('is-open');
            document.body.classList.add('admin-v6-no-scroll');
        }

        function closeMobileSidebar() {
            sidebar.classList.remove('is-open');
            if (overlay) overlay.classList.remove('is-open');
            document.body.classList.remove('admin-v6-no-scroll');
        }

        // Initial collapsed state (desktop only)
        try {
            if (window.localStorage.getItem(key) === '1' && !isMobile()) {
                setCollapsed(true);
            } else {
                setCollapsed(false);
            }
        } catch (e) {
            setCollapsed(false);
        }

        if (collapseBtn) {
            collapseBtn.addEventListener('click', function (event) {
                event.preventDefault();
                if (isMobile()) {
                    // On mobile this button behaves like open/close.
                    sidebar.classList.contains('is-open') ? closeMobileSidebar() : openMobileSidebar();
                    return;
                }

                var collapsed = html.getAttribute('data-admin-v6-sidebar') === 'collapsed';
                setCollapsed(!collapsed);
            });
        }

        if (hamburger) {
            hamburger.addEventListener('click', function (event) {
                event.preventDefault();
                if (isMobile()) {
                    sidebar.classList.contains('is-open') ? closeMobileSidebar() : openMobileSidebar();
                    return;
                }

                var collapsed = html.getAttribute('data-admin-v6-sidebar') === 'collapsed';
                setCollapsed(!collapsed);
            });
        }

        if (overlay) {
            overlay.addEventListener('click', closeMobileSidebar);
        }

        // Close on ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeMobileSidebar();
        });

        // Close sidebar when a nav link is clicked on mobile
        sidebar.querySelectorAll('a.aj-sidebar-v2__link:not(.aj-sidebar-v2__toggle)').forEach(function (link) {
            link.addEventListener('click', function () {
                if (isMobile()) closeMobileSidebar();
            });
        });

        // Sidebar submenu toggle (pure JS, no dependency on old layout scripts)
        sidebar.addEventListener('click', function (event) {
            var toggle = event.target.closest('[data-aj-sidebar-toggle]');
            if (toggle) {
                event.preventDefault();
                var li = toggle.closest('.aj-sidebar-v2__item');
                if (!li) return;
                var expanded = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                li.classList.toggle('is-open', !expanded);

                // If user is in collapsed mode (desktop), clicking a parent toggle expands sidebar first.
                if (!isMobile() && html.getAttribute('data-admin-v6-sidebar') === 'collapsed') {
                    setCollapsed(false);
                }
                return;
            }

            if (!isMobile() && html.getAttribute('data-admin-v6-sidebar') === 'collapsed') {
                var target = event.target.closest('.aj-sidebar-v2__link, .aj-sidebar-v2__toggle');
                if (!target) return;
                var isLeafLink = target.matches('a.aj-sidebar-v2__link') && target.getAttribute('href') && target.getAttribute('href') !== 'javascript:void(0);';
                if (!isLeafLink) setCollapsed(false);
            }
        });

        // Keep layout sane when switching breakpoint
        window.addEventListener('resize', function () {
            if (!isMobile()) {
                closeMobileSidebar();
                try {
                    if (window.localStorage.getItem(key) === '1') setCollapsed(true);
                } catch (e) { }
            } else {
                // Never keep collapsed mode on mobile
                setCollapsed(false);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAdminV6);
    } else {
        initAdminV6();
    }
})();
