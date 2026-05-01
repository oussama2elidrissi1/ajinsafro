(function () {
    'use strict';

    var root = document.getElementById('aj-groupdeals-fusion');
    if (!root) {
        return;
    }

    var body = document.body;

    // --- Mobile drawer ---
    var openButton = root.querySelector('#ajgd-open-filters');
    var closeButton = root.querySelector('#ajgd-close-filters');
    var drawer = root.querySelector('#ajgd-mobile-drawer');
    var drawerBackdrop = root.querySelector('#ajgd-drawer-backdrop');

    function openDrawer() {
        if (drawer) drawer.classList.add('active');
        if (drawerBackdrop) drawerBackdrop.classList.add('active');
        body.classList.add('aj-groupdeals-drawer-open');
    }

    function closeDrawer() {
        if (drawer) drawer.classList.remove('active');
        if (drawerBackdrop) drawerBackdrop.classList.remove('active');
        body.classList.remove('aj-groupdeals-drawer-open');
    }

    if (openButton && drawer && drawerBackdrop) {
        openButton.addEventListener('click', openDrawer);
    }
    if (closeButton && drawer && drawerBackdrop) {
        closeButton.addEventListener('click', closeDrawer);
    }
    if (drawerBackdrop) {
        drawerBackdrop.addEventListener('click', closeDrawer);
    }

    // --- Modal ---
    var modalOverlay = document.getElementById('ajgd-modal-overlay');
    var modalForm = document.getElementById('ajgd-participation-form');
    var modalError = document.getElementById('ajgd-modal-error');
    var modalSlug = document.getElementById('ajgd-modal-slug');

    function openModal(slug, title) {
        if (!modalOverlay) return;
        if (modalSlug) modalSlug.value = slug || '';
        var heading = modalOverlay.querySelector('#ajgd-modal-title');
        if (heading && title) heading.textContent = 'Rejoindre : ' + title;
        if (modalError) modalError.textContent = '';
        modalOverlay.classList.add('is-open');
        body.classList.add('aj-groupdeals-modal-open');
        var firstInput = modalOverlay.querySelector('input:not([type=hidden])');
        if (firstInput) firstInput.focus();
    }

    function closeModal() {
        if (!modalOverlay) return;
        modalOverlay.classList.remove('is-open');
        body.classList.remove('aj-groupdeals-modal-open');
        if (modalError) modalError.textContent = '';
    }

    root.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-open-participation]');
        if (btn) {
            e.preventDefault();
            openModal(btn.getAttribute('data-deal-slug'), btn.getAttribute('data-deal-title'));
            return;
        }
        var shareBtn = e.target.closest('[data-share-deal]');
        if (shareBtn) {
            e.preventDefault();
            var url = shareBtn.getAttribute('data-share-url');
            var shareTitle = shareBtn.getAttribute('data-share-title') || '';
            if (navigator.share) {
                navigator.share({ title: shareTitle, url: url });
            } else if (url) {
                window.open('https://wa.me/?text=' + encodeURIComponent(shareTitle + ' ' + url), '_blank');
            }
            return;
        }
        var card = e.target.closest('.ajgd-card');
        if (card && !e.target.closest('.ajgd-card__actions') && card.dataset.url) {
            window.location.href = card.dataset.url;
        }
    });

    var modalCloseBtn = document.getElementById('ajgd-modal-close');
    var modalCancelBtn = document.getElementById('ajgd-modal-cancel');
    if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
    if (modalCancelBtn) modalCancelBtn.addEventListener('click', closeModal);
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function (e) {
            if (e.target === modalOverlay) closeModal();
        });
    }

    // --- Form submission ---
    if (modalForm) {
        modalForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (modalError) modalError.textContent = '';
            var submitBtn = document.getElementById('ajgd-modal-submit');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Envoi en cours...';
            }

            var slug = (modalSlug ? modalSlug.value : '').trim();
            var apiBase = (window.ajgdConfig && window.ajgdConfig.apiBase) ? window.ajgdConfig.apiBase : '';
            var url = apiBase + '/group-deals/' + encodeURIComponent(slug) + '/participate';

            var formData = new FormData(modalForm);
            var payload = {};
            formData.forEach(function (value, key) {
                if (payload[key] !== undefined) {
                    if (!Array.isArray(payload[key])) payload[key] = [payload[key]];
                    payload[key].push(value);
                } else {
                    payload[key] = value;
                }
            });

            fetch(url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(function (res) {
                return res.json().catch(function () { return { success: false, message: 'Erreur reseau.' }; });
            })
            .then(function (data) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Envoyer ma participation';
                }
                if (data && data.success) {
                    closeModal();
                    showNotification(data.message || 'Participation enregistree avec succes !', 'success');
                    if (window.ajgdConfig && window.ajgdConfig.currentSlug && slug === window.ajgdConfig.currentSlug) {
                        setTimeout(function () { window.location.reload(); }, 2200);
                    }
                } else {
                    if (modalError) modalError.textContent = (data && data.message) ? data.message : 'Une erreur est survenue.';
                }
            })
            .catch(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Envoyer ma participation';
                }
                if (modalError) modalError.textContent = 'Erreur reseau. Veuillez reessayer.';
            });
        });
    }

    // --- Notifications ---
    function showNotification(message, type) {
        type = type || 'info';
        var container = document.getElementById('ajgd-notifications');
        if (!container) {
            container = document.createElement('div');
            container.id = 'ajgd-notifications';
            if (root) root.insertBefore(container, root.firstChild);
        }
        var banner = document.createElement('div');
        banner.className = 'ajgd-notification ajgd-notification--' + type;
        banner.textContent = message;
        container.appendChild(banner);
        setTimeout(function () {
            banner.classList.add('is-hiding');
            setTimeout(function () { if (banner.parentNode) banner.parentNode.removeChild(banner); }, 350);
        }, 4500);
    }

    // --- Escape key ---
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDrawer();
            closeModal();
        }
    });
})();
