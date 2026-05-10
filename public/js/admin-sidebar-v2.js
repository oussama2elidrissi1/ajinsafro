(function () {
    function storageKey(root) {
        return 'aj-sidebar-v2:active-group:' + (root.dataset.sidebarContext || 'default');
    }

    /**
     * Sauvegarde SEULEMENT le groupe actif (un seul à la fois).
     */
    function persistState(root) {
        var key = storageKey(root);
        var openItem = root.querySelector('.aj-sidebar-v2__item.has-children.is-open');
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

    /**
     * Restaure l'état:
     * 1. Ferme tous les groupes
     * 2. Ouvre SEULEMENT le groupe actif (route actuelle)
     * 3. Sinon, ouvre le groupe stocké en localStorage
     */
    function restoreState(root) {
        var key = storageKey(root);
        var activeItem = root.querySelector('.aj-sidebar-v2__item.is-active.has-children');
        var savedGroupKey = null;

        try {
            savedGroupKey = window.localStorage.getItem(key);
        } catch (error) {
            savedGroupKey = null;
        }

        // Fermer tous les groupes
        root.querySelectorAll('.aj-sidebar-v2__item.has-children').forEach(function (item) {
            item.classList.remove('is-open');
            var toggle = item.querySelector('.aj-sidebar-v2__toggle');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Ouvrir SEULEMENT le groupe actif (route actuelle)
        if (activeItem) {
            activeItem.classList.add('is-open');
            var activeToggle = activeItem.querySelector('.aj-sidebar-v2__toggle');
            if (activeToggle) {
                activeToggle.setAttribute('aria-expanded', 'true');
            }
            // Ouvrir aussi les parents si imbriqués
            var parent = activeItem.parentElement ? activeItem.parentElement.closest('.aj-sidebar-v2__item.has-children') : null;
            while (parent) {
                parent.classList.add('is-open');
                var parentToggle = parent.querySelector('.aj-sidebar-v2__toggle');
                if (parentToggle) {
                    parentToggle.setAttribute('aria-expanded', 'true');
                }
                parent = parent.parentElement ? parent.parentElement.closest('.aj-sidebar-v2__item.has-children') : null;
            }
        }
        // Sinon, ouvrir le groupe sauvegardé en localStorage
        else if (savedGroupKey) {
            var savedItem = root.querySelector('[data-group-key="' + savedGroupKey + '"]');
            if (savedItem) {
                savedItem.classList.add('is-open');
                var savedToggle = savedItem.querySelector('.aj-sidebar-v2__toggle');
                if (savedToggle) {
                    savedToggle.setAttribute('aria-expanded', 'true');
                }
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
            toggle.addEventListener('click', function () {
                var item = toggle.closest('.aj-sidebar-v2__item.has-children');
                if (!item) {
                    return;
                }

                var isOpen = item.classList.contains('is-open');

                // Fermer tous les autres groupes
                root.querySelectorAll('.aj-sidebar-v2__item.has-children').forEach(function (otherItem) {
                    if (otherItem !== item) {
                        otherItem.classList.remove('is-open');
                        var otherToggle = otherItem.querySelector('.aj-sidebar-v2__toggle');
                        if (otherToggle) {
                            otherToggle.setAttribute('aria-expanded', 'false');
                        }
                    }
                });

                // Basculer le groupe actif
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
