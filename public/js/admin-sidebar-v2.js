(function () {
    function storageKey(root) {
        return 'aj-sidebar-v2:' + (root.dataset.sidebarContext || 'default');
    }

    function persistState(root) {
        var key = storageKey(root);
        var openKeys = [];

        root.querySelectorAll('.aj-sidebar-v2__item.has-children.is-open').forEach(function (item) {
            if (item.dataset.groupKey) {
                openKeys.push(item.dataset.groupKey);
            }
        });

        try {
            window.localStorage.setItem(key, JSON.stringify(openKeys));
        } catch (error) {
        }
    }

    function restoreState(root) {
        var key = storageKey(root);
        var activeItems = root.querySelectorAll('.aj-sidebar-v2__item.is-active.has-children');
        var openKeys = [];

        try {
            openKeys = JSON.parse(window.localStorage.getItem(key) || '[]');
        } catch (error) {
            openKeys = [];
        }

        root.querySelectorAll('.aj-sidebar-v2__item.has-children').forEach(function (item) {
            var shouldOpen = item.classList.contains('is-active') || openKeys.indexOf(item.dataset.groupKey) !== -1;
            item.classList.toggle('is-open', shouldOpen);

            var toggle = item.querySelector('.aj-sidebar-v2__toggle');
            if (toggle) {
                toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            }
        });

        activeItems.forEach(function (item) {
            var parent = item.parentElement ? item.parentElement.closest('.aj-sidebar-v2__item.has-children') : null;
            while (parent) {
                parent.classList.add('is-open');
                var parentToggle = parent.querySelector('.aj-sidebar-v2__toggle');
                if (parentToggle) {
                    parentToggle.setAttribute('aria-expanded', 'true');
                }
                parent = parent.parentElement ? parent.parentElement.closest('.aj-sidebar-v2__item.has-children') : null;
            }
        });
    }

    function initSidebar(root) {
        if (!root || root.dataset.sidebarReady === '1') {
            return;
        }

        root.dataset.sidebarReady = '1';
        restoreState(root);

        root.querySelectorAll('[data-aj-sidebar-toggle]').forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                var item = toggle.closest('.aj-sidebar-v2__item.has-children');
                if (!item) {
                    return;
                }

                item.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', item.classList.contains('is-open') ? 'true' : 'false');
                persistState(root);
            });
        });
    }

    function initAll() {
        document.querySelectorAll('[data-aj-sidebar-v2]').forEach(initSidebar);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    window.AjSidebarV2 = {
        init: initAll,
    };
})();
