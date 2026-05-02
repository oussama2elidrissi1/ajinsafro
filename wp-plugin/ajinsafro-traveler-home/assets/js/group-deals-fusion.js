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
    var formState = document.getElementById('ajgd-modal-form-state');
    var successState = document.getElementById('ajgd-modal-success');
    var successMessage = document.getElementById('ajgd-success-message');
    var successStats = document.getElementById('ajgd-success-stats');

    function openModal(slug, title) {
        if (!modalOverlay) return;
        if (modalSlug) modalSlug.value = slug || '';
        var heading = modalOverlay.querySelector('#ajgd-modal-title');
        if (heading && title) heading.textContent = 'Rejoindre : ' + title;
        clearFieldErrors();
        if (modalError) modalError.textContent = '';
        if (formState) formState.style.display = '';
        if (successState) successState.style.display = 'none';
        modalOverlay.classList.add('is-open');
        modalOverlay.setAttribute('aria-hidden', 'false');
        body.classList.add('gd-modal-open');
        var firstInput = modalOverlay.querySelector('input:not([type=hidden])');
        if (firstInput) firstInput.focus();
    }

    function closeModal() {
        if (!modalOverlay) return;
        modalOverlay.classList.remove('is-open');
        modalOverlay.setAttribute('aria-hidden', 'true');
        body.classList.remove('gd-modal-open');
        if (modalError) modalError.textContent = '';
        clearFieldErrors();
        if (formState) formState.style.display = '';
        if (successState) successState.style.display = 'none';
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
    var modalCloseSuccessBtn = document.getElementById('ajgd-modal-close-success');
    var modalCloseOkBtn = document.getElementById('ajgd-modal-close-ok');
    if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
    if (modalCancelBtn) modalCancelBtn.addEventListener('click', closeModal);
    if (modalCloseSuccessBtn) modalCloseSuccessBtn.addEventListener('click', closeModal);
    if (modalCloseOkBtn) modalCloseOkBtn.addEventListener('click', closeModal);
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function (e) {
            if (e.target === modalOverlay) closeModal();
        });
    }

    // --- Field-level validation ---
    function clearFieldErrors() {
        var errors = document.querySelectorAll('.gd-field-error');
        for (var i = 0; i < errors.length; i++) {
            errors[i].textContent = '';
        }
        var inputs = document.querySelectorAll('.gd-form-group input, .gd-form-group textarea');
        for (var j = 0; j < inputs.length; j++) {
            inputs[j].style.borderColor = '';
        }
    }

    function showFieldError(id, message) {
        var el = document.getElementById(id);
        if (el) el.textContent = message;
    }

    function validateForm() {
        clearFieldErrors();
        var valid = true;
        var name = document.getElementById('ajgd-p-name');
        var phone = document.getElementById('ajgd-p-phone');
        var count = document.getElementById('ajgd-p-count');
        var accept = document.getElementById('ajgd-p-accept');

        if (!name || !name.value.trim()) {
            showFieldError('ajgd-error-name', 'Veuillez saisir votre nom complet.');
            if (name) name.style.borderColor = '#e74c3c';
            valid = false;
        }
        if (!phone || !phone.value.trim()) {
            showFieldError('ajgd-error-phone', 'Veuillez saisir votre numero de telephone.');
            if (phone) phone.style.borderColor = '#e74c3c';
            valid = false;
        }
        if (!count || !count.value.trim() || parseInt(count.value, 10) < 1) {
            showFieldError('ajgd-error-count', 'Veuillez indiquer au moins 1 personne.');
            if (count) count.style.borderColor = '#e74c3c';
            valid = false;
        }
        if (!accept || !accept.checked) {
            showFieldError('ajgd-error-accept', 'Vous devez accepter les conditions de participation.');
            valid = false;
        }
        return valid;
    }

    // --- Form submission ---
    if (modalForm) {
        modalForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!validateForm()) return;
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
                    if (formState) formState.style.display = 'none';
                    if (successState) successState.style.display = '';
                    if (successMessage) successMessage.textContent = data.message || 'Merci pour votre inscription.';

                    var statsHtml = '';
                    if (data.stats) {
                        var s = data.stats;
                        if (s.remaining_to_guarantee && parseInt(s.remaining_to_guarantee, 10) > 0) {
                            statsHtml += '<div>Il reste <strong>' + s.remaining_to_guarantee + '</strong> personne(s) pour garantir ce voyage.</div>';
                        } else if (s.is_guaranteed) {
                            statsHtml += '<div>✅ Ce voyage est maintenant <strong>garanti</strong> !</div>';
                        }
                        if (s.current_price) {
                            statsHtml += '<div>Prix actuel : <strong>' + s.current_price + ' DH</strong> par personne.</div>';
                        }
                    }
                    if (successStats) successStats.innerHTML = statsHtml;
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
