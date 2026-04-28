(function () {
    'use strict';

    var root = document.getElementById('aj-hebergement-booking');
    if (!root) {
        return;
    }

    var config = typeof window.ajthHebergementConfig === 'object' && window.ajthHebergementConfig
        ? window.ajthHebergementConfig
        : {};

    var strings = config.strings || {};
    var currency = config.currency || 'DH';

    var amenityLabels = {
        wifi: 'Wi-Fi gratuit',
        pool: 'Piscine',
        parking: 'Parking',
        air_conditioning: 'Climatisation',
        breakfast: 'Petit-dejeuner',
        restaurant: 'Restaurant',
        spa: 'Spa',
        gym: 'Salle de sport',
        sea_view: 'Vue mer',
        family: 'Chambre familiale',
        transfer: 'Transfert',
        activity: 'Activite',
        assistance: 'Assistance Ajinsafro',
        half_board: 'Demi-pension',
        full_board: 'Pension complete'
    };

    var boardLabels = {
        room_only: 'Sans repas',
        breakfast: 'Petit-dejeuner inclus',
        half_board: 'Demi-pension',
        full_board: 'Pension complete',
        all_inclusive: 'All inclusive'
    };

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatPrice(value) {
        if (value === null || value === undefined || value === '' || Number.isNaN(Number(value))) {
            return 'Sur demande';
        }

        return Number(value).toLocaleString('fr-FR') + ' ' + currency;
    }

    function getRatingLabel(rating) {
        if (rating === null || rating === undefined || rating === 0) return 'Ajinsafro';
        if (rating >= 9) return 'Wonderful';
        if (rating >= 8.5) return 'Excellent';
        if (rating >= 8) return 'Very good';
        if (rating >= 7) return 'Good';
        return 'Correct';
    }

    function slugify(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    function normalizeHotel(item) {
        var hotel = item && typeof item === 'object' ? item : {};
        var normalizedType = slugify(hotel.type || hotel.category || 'hotel');
        var normalizedBoard = slugify(hotel.board || '');

        return {
            kind: 'hotel',
            id: Number(hotel.id || 0),
            name: String(hotel.name || hotel.title || ''),
            location: String(hotel.location || ''),
            type: normalizedType || 'hotel',
            typeLabel: String(hotel.category || hotel.type || 'Hotel'),
            stars: Number(hotel.stars || 0),
            rating: hotel.rating !== null && hotel.rating !== undefined && hotel.rating !== '' ? Number(hotel.rating) : null,
            reviews: Number(hotel.reviews || 0),
            price: hotel.price !== null && hotel.price !== undefined && hotel.price !== '' ? Number(hotel.price) : null,
            oldPrice: hotel.oldPrice !== null && hotel.oldPrice !== undefined && hotel.oldPrice !== '' ? Number(hotel.oldPrice) : null,
            discount: Number(hotel.discount || 0),
            image: String(hotel.image || hotel.image_url || ''),
            amenities: Array.isArray(hotel.amenities) ? hotel.amenities : [],
            board: normalizedBoard,
            boardLabel: boardLabels[normalizedBoard] || String(hotel.board || 'Sans repas'),
            description: String(hotel.description || hotel.excerpt || ''),
            popular: !!hotel.popular,
            available: hotel.available !== false,
            url: String(hotel.url || '#')
        };
    }

    var hotels = Array.isArray(config.hotels) ? config.hotels.map(normalizeHotel) : [];
    var packs = Array.isArray(config.packs) ? config.packs : [];

    var filterHTML = '\
      <details class="accordion" open>\
        <summary>Type d\\\'offre</summary>\
        <div class="filter-body">\
          <label class="check-row"><input type="checkbox" name="offerType" value="pack"> Packs hebergement</label>\
          <label class="check-row"><input type="checkbox" name="offerType" value="hotel"> Hebergements a la carte</label>\
        </div>\
      </details>\
      <details class="accordion" open>\
        <summary>Recherche</summary>\
        <div class="filter-body">\
          <input class="filter-search" data-ajhb="nameFilter" type="text" placeholder="Nom, ville, quartier...">\
        </div>\
      </details>\
      <details class="accordion" open>\
        <summary>Budget</summary>\
        <div class="filter-body">\
          <div class="mini-inputs">\
            <input data-ajhb="minPrice" type="number" placeholder="Min ' + currency + '">\
            <input data-ajhb="maxPrice" type="number" placeholder="Max ' + currency + '">\
          </div>\
        </div>\
      </details>\
      <details class="accordion" open>\
        <summary>Pension</summary>\
        <div class="filter-body">\
          <label class="check-row"><input type="checkbox" name="board" value="room_only"> Sans repas</label>\
          <label class="check-row"><input type="checkbox" name="board" value="breakfast"> Petit-dejeuner</label>\
          <label class="check-row"><input type="checkbox" name="board" value="half_board"> Demi-pension</label>\
          <label class="check-row"><input type="checkbox" name="board" value="full_board"> Pension complete</label>\
        </div>\
      </details>\
      <details class="accordion" open>\
        <summary>Type</summary>\
        <div class="filter-body">\
          <label class="check-row"><input type="checkbox" name="type" value="hotel"> Hotel</label>\
          <label class="check-row"><input type="checkbox" name="type" value="riad"> Riad</label>\
          <label class="check-row"><input type="checkbox" name="type" value="apartment"> Appartement</label>\
          <label class="check-row"><input type="checkbox" name="type" value="villa"> Villa</label>\
          <label class="check-row"><input type="checkbox" name="type" value="guest-house"> Maison d\\\'hotes</label>\
          <label class="check-row"><input type="checkbox" name="type" value="resort"> Resort</label>\
        </div>\
      </details>\
      <details class="accordion" open>\
        <summary>Disponibilite</summary>\
        <div class="filter-body">\
          <label class="check-row"><input type="checkbox" name="popular" value="true"> Selection Ajinsafro</label>\
          <label class="check-row"><input type="checkbox" name="discount" value="true"> Promotions</label>\
          <label class="check-row"><input type="checkbox" name="available" value="true"> Disponible uniquement</label>\
        </div>\
      </details>\
      <details class="accordion" open>\
        <summary>Note client</summary>\
        <div class="filter-body">\
          <label class="radio-row"><input type="radio" name="rating" value="" checked> Toutes</label>\
          <label class="radio-row"><input type="radio" name="rating" value="9"> Wonderful 9+</label>\
          <label class="radio-row"><input type="radio" name="rating" value="8"> Very good 8+</label>\
          <label class="radio-row"><input type="radio" name="rating" value="7"> Good 7+</label>\
        </div>\
      </details>';

    var desktopFilters = root.querySelector('#ajhb-filters-content');
    var mobileFilters = root.querySelector('#ajhb-mobile-filters-content');
    var packList = root.querySelector('#ajhb-pack-list');
    var hotelList = root.querySelector('#ajhb-hotel-list');
    var packSection = root.querySelector('#ajhb-pack-section');
    var staySection = root.querySelector('#ajhb-stay-section');
    var packCountEl = root.querySelector('#ajhb-pack-count');
    var stayCountEl = root.querySelector('#ajhb-stay-count');
    var countEl = root.querySelector('#ajhb-count');
    var emptyState = root.querySelector('#ajhb-empty-state');
    var chipsEl = root.querySelector('#ajhb-active-chips');
    var sortSelect = root.querySelector('#ajhb-sort-select');
    var destinationInput = root.querySelector('#ajhb-destination');
    var budgetInput = root.querySelector('#ajhb-budget');
    var searchForm = root.querySelector('#ajhb-search-form');
    var drawer = root.querySelector('#ajhb-mobile-drawer');
    var backdrop = root.querySelector('#ajhb-drawer-backdrop');
    var openFiltersBtn = root.querySelector('#ajhb-open-filters');
    var closeFiltersBtn = root.querySelector('#ajhb-close-filters');
    var applyMobileFiltersBtn = root.querySelector('#ajhb-apply-mobile-filters');

    if (!desktopFilters || !mobileFilters || !packList || !hotelList || !packSection || !staySection || !packCountEl || !stayCountEl || !countEl || !emptyState || !chipsEl || !sortSelect || !destinationInput || !budgetInput || !searchForm || !drawer || !backdrop || !openFiltersBtn || !closeFiltersBtn || !applyMobileFiltersBtn) {
        return;
    }

    desktopFilters.innerHTML = filterHTML;
    mobileFilters.innerHTML = filterHTML;

    function readFilters(container) {
        var nameInput = container.querySelector('[data-ajhb="nameFilter"]');
        var minPriceInput = container.querySelector('[data-ajhb="minPrice"]');
        var maxPriceInput = container.querySelector('[data-ajhb="maxPrice"]');

        return {
            destination: destinationInput.value.trim().toLowerCase(),
            budgetMax: Number(budgetInput.value || 0),
            name: nameInput ? nameInput.value.trim().toLowerCase() : '',
            minPrice: Number(minPriceInput ? minPriceInput.value : 0),
            maxPrice: Number(maxPriceInput ? maxPriceInput.value : 0),
            minRating: Number(((container.querySelector('input[name="rating"]:checked') || {}).value) || 0),
            offerTypes: Array.prototype.slice.call(container.querySelectorAll('input[name="offerType"]:checked')).map(function (input) { return input.value; }),
            boards: Array.prototype.slice.call(container.querySelectorAll('input[name="board"]:checked')).map(function (input) { return input.value; }),
            propertyTypes: Array.prototype.slice.call(container.querySelectorAll('input[name="type"]:checked')).map(function (input) { return input.value; }),
            popular: !!container.querySelector('input[name="popular"]:checked'),
            discountOnly: !!container.querySelector('input[name="discount"]:checked'),
            availableOnly: !!container.querySelector('input[name="available"]:checked')
        };
    }

    function matchesOffer(item, filters) {
        var searchable = [
            item.title || item.name || '',
            item.city || item.location || '',
            item.description || '',
            item.typeLabel || '',
            item.pensionLabel || item.boardLabel || ''
        ].join(' ').toLowerCase();

        if (filters.destination && searchable.indexOf(filters.destination) === -1) return false;
        if (filters.name && searchable.indexOf(filters.name) === -1) return false;
        if (filters.offerTypes.length && filters.offerTypes.indexOf(item.kind) === -1) return false;
        if (filters.minPrice && (item.price === null || item.price < filters.minPrice)) return false;
        if (filters.maxPrice && (item.price === null || item.price > filters.maxPrice)) return false;
        if (filters.budgetMax && (item.price === null || item.price > filters.budgetMax)) return false;
        if (filters.minRating && item.kind === 'hotel' && (item.rating === null || item.rating < filters.minRating)) return false;
        if (filters.boards.length) {
            var boardValue = item.kind === 'pack' ? item.pension : item.board;
            if (filters.boards.indexOf(boardValue) === -1) return false;
        }
        if (filters.propertyTypes.length && filters.propertyTypes.indexOf(item.type) === -1) return false;
        if (filters.popular && !item.popular) return false;
        if (filters.discountOnly && !(item.discount || item.oldPrice)) return false;
        if (filters.availableOnly && !item.available) return false;

        return true;
    }

    function sortItems(list, mode) {
        var sorted = list.slice();

        if (mode === 'price-asc') sorted.sort(function (a, b) { return (a.price || 0) - (b.price || 0); });
        if (mode === 'price-desc') sorted.sort(function (a, b) { return (b.price || 0) - (a.price || 0); });
        if (mode === 'rating-desc') sorted.sort(function (a, b) { return (b.rating || 0) - (a.rating || 0); });
        if (mode === 'stars-desc') sorted.sort(function (a, b) { return (b.stars || 0) - (a.stars || 0); });
        if (mode === 'discount-desc') sorted.sort(function (a, b) { return (b.discount || 0) - (a.discount || 0); });
        if (mode === 'recommended') {
            sorted.sort(function (a, b) {
                return (Number(!!b.popular) - Number(!!a.popular))
                    || ((b.rating || 0) - (a.rating || 0))
                    || ((b.stars || 0) - (a.stars || 0))
                    || ((a.price || 0) - (b.price || 0));
            });
        }

        return sorted;
    }

    function renderIncludes(list) {
        return list.map(function (item) {
            return '<span class="amenity amenity--subtle amenity--with-icon"><span class="amenity__icon">•</span>' + escapeHtml(amenityLabels[item] || item) + '</span>';
        }).join('');
    }

    function renderPackCard(pack) {
        var imageMarkup = pack.image
            ? '<img src="' + escapeHtml(pack.image) + '" alt="' + escapeHtml(pack.title) + '" loading="lazy">'
            : '<div class="photo-placeholder">Aucune photo</div>';

        return '' +
            '<article class="hotel-card hotel-card--pack" data-id="' + escapeHtml(pack.id) + '">' +
                '<div class="photo-wrap' + (pack.image ? '' : ' photo-wrap--placeholder') + '">' +
                    '<a class="photo-link" href="' + escapeHtml(pack.url) + '">' + imageMarkup + '</a>' +
                    '<button class="fav" type="button" aria-label="Ajouter aux favoris">♡</button>' +
                    '<div class="photo-badges">' +
                        pack.badges.map(function (badge, index) {
                            var modifier = index === 0 ? '' : (badge.toLowerCase().indexOf('promo') !== -1 ? ' photo-badge--promo' : ' photo-badge--type');
                            return '<span class="photo-badge' + modifier + '">' + escapeHtml(badge) + '</span>';
                        }).join('') +
                    '</div>' +
                '</div>' +
                '<div class="hotel-main">' +
                    '<div class="meta meta--caps meta--compact"><span>Pack hebergement</span></div>' +
                    '<h3><a href="' + escapeHtml(pack.url) + '">' + escapeHtml(pack.title) + '</a></h3>' +
                    '<div class="location location--primary"><span>' + escapeHtml(pack.city) + '</span><span>' + escapeHtml(pack.duration) + '</span></div>' +
                    '<div class="meta-grid">' +
                        '<div class="meta-item"><span class="meta-item__label">Duree</span><strong>' + escapeHtml(pack.duration) + '</strong></div>' +
                        '<div class="meta-item"><span class="meta-item__label">Pension</span><strong>' + escapeHtml(pack.pensionLabel) + '</strong></div>' +
                        '<div class="meta-item"><span class="meta-item__label">Ville</span><strong>' + escapeHtml(pack.city) + '</strong></div>' +
                        '<div class="meta-item"><span class="meta-item__label">Hebergement</span><strong>' + escapeHtml(pack.typeLabel) + '</strong></div>' +
                    '</div>' +
                    '<p class="description">' + escapeHtml(pack.description) + '</p>' +
                    '<div class="pack-includes"><strong>Inclus dans ce pack</strong><div class="amenities">' + renderIncludes(pack.includes) + '</div></div>' +
                    '<div class="good-note">Disponibilites verifiees · Reservation rapide</div>' +
                '</div>' +
                '<aside class="hotel-side">' +
                    '<div class="rating-box">' +
                        '<div class="rating-text"><strong>Pack Ajinsafro</strong><span>' + escapeHtml(pack.highlights.join(' · ')) + '</span></div>' +
                        '<div class="rating-score">' + pack.nights + 'N</div>' +
                    '</div>' +
                    '<div class="price-area">' +
                        '<small>A partir de</small>' +
                        '<div>' + (pack.oldPrice ? '<span class="old-price">' + formatPrice(pack.oldPrice) + '</span>' : '') + '<span class="price">' + formatPrice(pack.price) + '</span></div>' +
                        '<div class="tax">pour le sejour</div>' +
                    '</div>' +
                    '<div class="card-actions">' +
                        '<a class="secondary-btn" href="' + escapeHtml(pack.url) + '">Voir le pack</a>' +
                        '<a class="primary-btn" href="' + escapeHtml(pack.url) + '">Reserver ce pack</a>' +
                    '</div>' +
                '</aside>' +
            '</article>';
    }

    function renderHotelCard(hotel) {
        var imageUrl = hotel.image && hotel.image.trim() ? hotel.image : '';
        var imageMarkup = imageUrl
            ? '<img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(hotel.name || 'Hebergement Ajinsafro') + '" loading="lazy">'
            : '<div class="photo-placeholder">Aucune photo</div>';
        var amenities = hotel.amenities.slice(0, 4).map(function (key) {
            return '<span class="amenity">' + escapeHtml(amenityLabels[key] || key) + '</span>';
        }).join('');
        var stars = hotel.stars > 0 ? hotel.stars + ' etoiles' : 'Type libre';
        var ratingMarkup = hotel.rating
            ? '<div class="rating-box"><div class="rating-text"><strong>' + getRatingLabel(hotel.rating) + '</strong><span>' + escapeHtml(hotel.reviews > 0 ? hotel.reviews.toLocaleString('fr-FR') + ' avis' : hotel.typeLabel) + '</span></div><div class="rating-score">' + hotel.rating.toFixed(1) + '</div></div>'
            : '<div class="rating-box"><div class="rating-text"><strong>' + escapeHtml(hotel.typeLabel) + '</strong><span>' + escapeHtml(stars) + '</span></div><div class="rating-score">' + (hotel.stars > 0 ? hotel.stars : '•') + '</div></div>';

        return '' +
            '<article class="hotel-card" data-id="' + hotel.id + '">' +
                '<div class="photo-wrap' + (imageUrl ? '' : ' photo-wrap--placeholder') + '">' +
                    '<a class="photo-link" href="' + escapeHtml(hotel.url) + '">' + imageMarkup + '</a>' +
                    '<button class="fav" type="button" aria-label="Ajouter aux favoris">♡</button>' +
                    '<div class="photo-badges">' +
                        (hotel.popular ? '<span class="photo-badge">' + escapeHtml(strings.recommended || 'Recommande') + '</span>' : '') +
                        '<span class="photo-badge photo-badge--type">' + escapeHtml(hotel.typeLabel) + '</span>' +
                        (hotel.discount ? '<span class="photo-badge photo-badge--promo">Promo ' + escapeHtml(String(hotel.discount)) + '%</span>' : '') +
                    '</div>' +
                '</div>' +
                '<div class="hotel-main">' +
                    '<div class="meta meta--caps meta--compact"><span>Hebergement a la carte</span></div>' +
                    '<h3><a href="' + escapeHtml(hotel.url) + '">' + escapeHtml(hotel.name) + '</a></h3>' +
                    '<div class="location location--primary"><span>' + escapeHtml(hotel.location || 'Localisation non renseignee') + '</span></div>' +
                    '<div class="meta-grid">' +
                        '<div class="meta-item"><span class="meta-item__label">Destination</span><strong>' + escapeHtml(hotel.location || 'A confirmer') + '</strong></div>' +
                        '<div class="meta-item"><span class="meta-item__label">Type</span><strong>' + escapeHtml(hotel.typeLabel) + '</strong></div>' +
                        '<div class="meta-item"><span class="meta-item__label">Pension</span><strong>' + escapeHtml(hotel.boardLabel) + '</strong></div>' +
                        '<div class="meta-item"><span class="meta-item__label">Standing</span><strong>' + escapeHtml(stars) + '</strong></div>' +
                    '</div>' +
                    '<p class="description">' + escapeHtml(hotel.description || 'Hebergement Ajinsafro disponible dans notre catalogue.') + '</p>' +
                    '<div class="amenities">' + amenities + '</div>' +
                    '<div class="good-note">Disponibilites verifiees · Reservation rapide</div>' +
                '</div>' +
                '<aside class="hotel-side">' +
                    '<div>' + ratingMarkup + '</div>' +
                    '<div class="price-area">' +
                        '<small>' + (strings.from_price || 'A partir de') + '</small>' +
                        '<div>' + (hotel.oldPrice ? '<span class="old-price">' + formatPrice(hotel.oldPrice) + '</span>' : '') + '<span class="price">' + formatPrice(hotel.price) + '</span></div>' +
                        '<div class="tax">' + (strings.per_night || 'par nuit') + '</div>' +
                    '</div>' +
                    '<div class="card-actions">' +
                        '<a class="secondary-btn" href="' + escapeHtml(hotel.url) + '">Voir l\'hebergement</a>' +
                        '<a class="primary-btn" href="' + escapeHtml(hotel.url) + '">Reserver</a>' +
                    '</div>' +
                '</aside>' +
            '</article>';
    }

    function renderChips(filters) {
        var chips = [];

        if (filters.destination) chips.push('Destination: ' + filters.destination);
        if (filters.name) chips.push('Recherche: ' + filters.name);
        if (filters.minPrice) chips.push('Min ' + filters.minPrice + ' ' + currency);
        if (filters.maxPrice) chips.push('Max ' + filters.maxPrice + ' ' + currency);
        if (filters.budgetMax) chips.push('Budget max ' + filters.budgetMax + ' ' + currency);
        filters.offerTypes.forEach(function (type) {
            chips.push(type === 'pack' ? 'Packs hebergement' : 'Hebergements a la carte');
        });
        filters.boards.forEach(function (board) {
            chips.push(boardLabels[board] || board);
        });
        filters.propertyTypes.forEach(function (type) {
            chips.push('Type: ' + type);
        });
        if (filters.popular) chips.push('Selection Ajinsafro');
        if (filters.discountOnly) chips.push('Promotions');
        if (filters.availableOnly) chips.push('Disponible');
        if (filters.minRating) chips.push('Note ' + filters.minRating + '+');

        chipsEl.innerHTML = chips.map(function (label) {
            return '<span class="chip">' + escapeHtml(label) + '<button type="button" data-ajhb-action="reset">×</button></span>';
        }).join('');
    }

    function syncFilterValues(fromContainer, toContainer) {
        Array.prototype.slice.call(fromContainer.querySelectorAll('input, select')).forEach(function (input) {
            var selector = '';

            if (input.dataset.ajhb) {
                selector = '[data-ajhb="' + input.dataset.ajhb + '"]';
            } else if (input.name) {
                selector = input.type === 'radio' || input.type === 'checkbox'
                    ? 'input[name="' + input.name + '"][value="' + input.value + '"]'
                    : '[name="' + input.name + '"]';
            }

            if (!selector) {
                return;
            }

            var target = toContainer.querySelector(selector);
            if (!target) {
                return;
            }

            if (input.type === 'checkbox' || input.type === 'radio') {
                target.checked = input.checked;
            } else {
                target.value = input.value;
            }
        });
    }

    function applyFilters(sourceContainer) {
        var filters = readFilters(sourceContainer || desktopFilters);
        var sortedPacks = sortItems(packs.filter(function (item) { return matchesOffer(item, filters); }), sortSelect.value);
        var sortedHotels = sortItems(hotels.filter(function (item) { return matchesOffer(item, filters); }), sortSelect.value);
        var total = sortedPacks.length + sortedHotels.length;

        if (sourceContainer === mobileFilters) {
            syncFilterValues(mobileFilters, desktopFilters);
        } else {
            syncFilterValues(desktopFilters, mobileFilters);
        }

        packList.innerHTML = sortedPacks.map(renderPackCard).join('');
        hotelList.innerHTML = sortedHotels.map(renderHotelCard).join('');

        packCountEl.textContent = sortedPacks.length + ' pack' + (sortedPacks.length > 1 ? 's' : '');
        stayCountEl.textContent = sortedHotels.length + ' hebergement' + (sortedHotels.length > 1 ? 's' : '');
        countEl.textContent = String(total);

        packSection.style.display = sortedPacks.length ? '' : 'none';
        staySection.style.display = sortedHotels.length ? '' : 'none';
        emptyState.style.display = total ? 'none' : 'block';

        renderChips(filters);
    }

    function resetFilters() {
        [desktopFilters, mobileFilters].forEach(function (container) {
            Array.prototype.slice.call(container.querySelectorAll('input[type="text"], input[type="number"]')).forEach(function (input) {
                input.value = '';
            });
            Array.prototype.slice.call(container.querySelectorAll('input[type="checkbox"]')).forEach(function (input) {
                input.checked = false;
            });
            Array.prototype.slice.call(container.querySelectorAll('input[type="radio"]')).forEach(function (input) {
                input.checked = input.value === '';
            });
        });

        destinationInput.value = '';
        budgetInput.value = '';
        sortSelect.value = 'recommended';
        applyFilters(desktopFilters);
    }

    function closeDrawer() {
        drawer.classList.remove('active');
        backdrop.classList.remove('active');
        document.body.style.overflow = '';
    }

    root.addEventListener('input', function (event) {
        if (event.target.closest('#ajhb-filters-content')) {
            applyFilters(desktopFilters);
        }
    });

    root.addEventListener('change', function (event) {
        if (event.target.closest('#ajhb-filters-content')) {
            applyFilters(desktopFilters);
        }

        if (event.target === sortSelect) {
            applyFilters(desktopFilters);
        }
    });

    root.addEventListener('click', function (event) {
        var resetTrigger = event.target.closest('[data-ajhb-action="reset"]');
        if (resetTrigger) {
            resetFilters();
        }
    });

    searchForm.addEventListener('submit', function (event) {
        event.preventDefault();
        applyFilters(desktopFilters);
    });

    openFiltersBtn.addEventListener('click', function () {
        syncFilterValues(desktopFilters, mobileFilters);
        drawer.classList.add('active');
        backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    });

    closeFiltersBtn.addEventListener('click', closeDrawer);
    backdrop.addEventListener('click', closeDrawer);
    applyMobileFiltersBtn.addEventListener('click', function () {
        applyFilters(mobileFilters);
        closeDrawer();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && drawer.classList.contains('active')) {
            closeDrawer();
        }
    });

    applyFilters(desktopFilters);
})();
