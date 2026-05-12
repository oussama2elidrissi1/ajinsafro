(function () {
    function setItemOpen(item, isOpen) {
        if (!item) {
            return;
        }

        item.classList.toggle('is-open', isOpen);

        var toggle = item.querySelector('.aj-sidebar-v2__toggle');
        if (toggle) {
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
    }

    function closeDescendantGroups(item) {
        if (!item) {
            return;
        }

        item.querySelectorAll('.aj-sidebar-v2__item.has-children.is-open').forEach(function (child) {
            setItemOpen(child, false);
        });
    }

    function openAncestors(item) {
        var parent = item && item.parentElement ? item.parentElement.closest('.aj-sidebar-v2__item.has-children') : null;

        while (parent) {
            setItemOpen(parent, true);
            parent = parent.parentElement ? parent.parentElement.closest('.aj-sidebar-v2__item.has-children') : null;
        }
    }

    function storageKey(root) {
        return 'aj-sidebar-v2:active-group:' + (root.dataset.sidebarContext || 'default');
    }

    function persistState(root) {
        var key = storageKey(root);
        var openItem = root.querySelector('.aj-sidebar-v2__list--depth-0 > .aj-sidebar-v2__item.has-children.is-open');
        var activeGroupKey = openItem ? openItem.dataset.groupKey : null;

        try {
            if (activeGroupKey) {
                window.localStorage.setItem(key, activeGroupKey);
            } else {
                window.localStorage.removeItem(key);
            }
        } catch (error) {
        }
    }

    function restoreState(root) {
        var key = storageKey(root);
        var activeItems = root.querySelectorAll('.aj-sidebar-v2__item.is-active.has-children');
        var savedGroupKey = null;

        try {
            savedGroupKey = window.localStorage.getItem(key);
        } catch (error) {
            savedGroupKey = null;
        }

        root.querySelectorAll('.aj-sidebar-v2__item.has-children').forEach(function (item) {
            setItemOpen(item, false);
        });

        if (activeItems.length > 0) {
            activeItems.forEach(function (activeItem) {
                setItemOpen(activeItem, true);
                openAncestors(activeItem);
            });
            return;
        }

        if (savedGroupKey) {
            var savedItem = root.querySelector('[data-group-key="' + savedGroupKey + '"]');
            if (savedItem) {
                setItemOpen(savedItem, true);
                openAncestors(savedItem);
            }
        }
    }

    function initSidebar(root) {
        if (!root || root.dataset.sidebarReady === '1') {
            return;
        }

        root.dataset.sidebarReady = '1';
        restoreState(root);

        root.querySelectorAll('[data-aj-sidebar-toggle]').forEach(function (toggle) {
            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                var item = toggle.closest('.aj-sidebar-v2__item.has-children');
                if (!item) {
                    return;
                }

                var isOpen = item.classList.contains('is-open');
                var siblings = item.parentElement ? Array.prototype.slice.call(item.parentElement.children) : [];

                siblings.forEach(function (sibling) {
                    if (sibling !== item && sibling.classList && sibling.classList.contains('aj-sidebar-v2__item') && sibling.classList.contains('has-children')) {
                        setItemOpen(sibling, false);
                        closeDescendantGroups(sibling);
                    }
                });

                if (isOpen) {
                    setItemOpen(item, false);
                    closeDescendantGroups(item);
                } else {
                    setItemOpen(item, true);
                    openAncestors(item);
                }

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
