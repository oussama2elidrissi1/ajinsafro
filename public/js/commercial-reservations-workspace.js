document.addEventListener('DOMContentLoaded', function () {
    var page = document.getElementById('commercialWorkspacePage');
    var sidebarToggle = document.getElementById('commercialWorkspaceSidebarToggle');
    var topSearch = document.getElementById('commercialWorkspaceTopSearch');
    var workspaceSearch = document.getElementById('ws-filter-search');
    var isMobile = window.matchMedia('(max-width: 768px)');
    var storageKey = 'aj-commercial-workspace-sidebar-collapsed';

    if (!page || !sidebarToggle) {
        return;
    }

    if (!isMobile.matches && window.localStorage.getItem(storageKey) === '1') {
        page.classList.add('is-sidebar-collapsed');
    }

    sidebarToggle.addEventListener('click', function () {
        if (isMobile.matches) {
            page.classList.toggle('is-sidebar-open-mobile');
            return;
        }

        page.classList.toggle('is-sidebar-collapsed');
        window.localStorage.setItem(storageKey, page.classList.contains('is-sidebar-collapsed') ? '1' : '0');
    });

    if (topSearch && workspaceSearch) {
        topSearch.addEventListener('input', function () {
            workspaceSearch.value = topSearch.value;
        });
    }

    document.addEventListener('click', function (event) {
        if (!isMobile.matches || !page.classList.contains('is-sidebar-open-mobile')) {
            return;
        }

        var sidebar = document.getElementById('commercialWorkspaceSidebar');
        var insideSidebar = sidebar && sidebar.contains(event.target);
        var onToggle = sidebarToggle.contains(event.target);
        if (!insideSidebar && !onToggle) {
            page.classList.remove('is-sidebar-open-mobile');
        }
    });
});
