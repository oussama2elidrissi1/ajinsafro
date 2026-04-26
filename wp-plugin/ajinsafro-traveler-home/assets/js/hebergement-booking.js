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
    var hotels = Array.isArray(config.hotels) ? config.hotels.map(normalizeHotel) : [];

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
        family: 'Chambre familiale'
    };

    var boardLabels = {
        room_only: 'Sans repas',
        breakfast: 'Petit-dejeuner inclus',
        half_board: 'Demi-pension',
        full_board: 'Pension complete',
        all_inclusive: 'All inclusive'
    };

    function normalizeHotel(item) {
        var hotel = item && typeof item === 'object' ? item : {};
        return {
            id: Number(hotel.id || 0),
            name: String(hotel.name || hotel.title || ''),
            title: String(hotel.title || hotel.name || ''),
            location: String(hotel.location || ''),
            type: String(hotel.type || 'hotel'),
            stars: Number(hotel.stars || 0),
            rating: hotel.rating !== null && hotel.rating !== undefined && hotel.rating !== '' ? Number(hotel.rating) : null,
            reviews: Number(hotel.reviews || 0),
            price: hotel.price !== null && hotel.price !== undefined && hotel.price !== '' ? Number(hotel.price) : null,
            oldPrice: hotel.oldPrice !== null && hotel.oldPrice !== undefined && hotel.oldPrice !== '' ? Number(hotel.oldPrice) : null,
            discount: Number(hotel.discount || 0),
            image: String(hotel.image || hotel.image_url || ''),
            amenities: Array.isArray(hotel.amenities) ? hotel.amenities : [],
            board: String(hotel.board || ''),
            description: String(hotel.description || hotel.excerpt || ''),
            popular: !!hotel.popular,
            available: hotel.available !== false,
            url: String(hotel.url || '#'),
            category: String(hotel.category || 'Hotel')
        };
    }

    var filterHTML = '\
      <details class="accordion" open>\
        <summary>Search by property name</summary>\
        <div class="filter-body">\
          <input class="filter-search" data-ajhb="nameFilter" type="text" placeholder="ex: Marriott, Riad, Villa...">\
        </div>\
      </details>\
      <details class="accordion" open>\
        <summary>Popular filters</summary>\
        <div class="filter-body">\
          <label class="check-row"><input type="checkbox" name="popular" value="true"> A la une uniquement</label>\
          <label class="check-row"><input type="checkbox" name="discount" value="true"> Promotions</label>\
          <label class="check-row"><input type="checkbox" name="available" value="true"> Disponible uniquement</label>\
          <label class="check-row"><input type="checkbox" name="amenity" value="breakfast"> Petit-dejeuner inclus</label>\
          <label class="check-row"><input type="checkbox" name="amenity" value="pool"> Piscine</label>\
        </div>\
      </details>\
      <details class="accordion" open>\
        <summary>Nightly price</summary>\
        <div class="filter-body">\
          <div class="mini-inputs">\
            <input data-ajhb="minPrice" type="number" placeholder="Min DH">\
            <input data-ajhb="maxPrice" type="number" placeholder="Max DH">\
          </div>\
        </div>\
      </details>\
      <details class="accordion" open>\
        <summary>Stay options</summary>\
        <div class="filter-body">\
          <label class="radio-row"><input type="radio" name="type" value="" checked> Tous</label>\
          <label class="radio-row"><input type="radio" name="type" value="hotel"> Hotels</label>\
          <label class="radio-row"><input type="radio" name="type" value="riad"> Riads</label>\
          <label class="radio-row"><input type="radio" name="type" value="apartment"> Appartements</label>\
          <label class="radio-row"><input type="radio" name="type" value="resort"> Resorts</label>\
        </div>\
      </details>\
      <details class="accordion" open>\
        <summary>Star rating</summary>\
        <div class="filter-body">\
          <label class="check-row"><input type="checkbox" name="stars" value="5"> 5 stars</label>\
          <label class="check-row"><input type="checkbox" name="stars" value="4"> 4 stars</label>\
          <label class="check-row"><input type="checkbox" name="stars" value="3"> 3 stars</label>\
          <label class="check-row"><input type="checkbox" name="stars" value="2"> 2 stars</label>\
          <label class="check-row"><input type="checkbox" name="stars" value="1"> 1 star</label>\
        </div>\
      </details>\
      <details class="accordion" open>\
        <summary>Guest rating</summary>\
        <div class="filter-body">\
          <label class="radio-row"><input type="radio" name="rating" value="" checked> Tous</label>\
          <label class="radio-row"><input type="radio" name="rating" value="9"> Wonderful 9+</label>\
          <label class="radio-row"><input type="radio" name="rating" value="8"> Very good 8+</label>\
          <label class="radio-row"><input type="radio" name="rating" value="7"> Good 7+</label>\
        </div>\
      </details>\
      <details class="accordion" open>\
        <summary>Property amenities</summary>\
        <div class="filter-body">' +
            Object.keys(amenityLabels).map(function (key) {
                return '<label class="check-row"><input type="checkbox" name="amenity" value="' + key + '"> ' + amenityLabels[key] + '</label>';
            }).join('') +
        '</div>\
      </details>\
      <details class="accordion" open>\
        <summary>Meal plans available</summary>\
        <div class="filter-body">' +
            Object.keys(boardLabels).map(function (key) {
                return '<label class="check-row"><input type="checkbox" name="board" value="' + key + '"> ' + boardLabels[key] + '</label>';
            }).join('') +
        '</div>\
      </details>';

    var desktopFilters = root.querySelector('#ajhb-filters-content');
    var mobileFilters = root.querySelector('#ajhb-mobile-filters-content');
    var hotelList = root.querySelector('#ajhb-hotel-list');
    var countEl = root.querySelector('#ajhb-count');
    var emptyState = root.querySelector('#ajhb-empty-state');
    var chipsEl = root.querySelector('#ajhb-active-chips');
    var sortSelect = root.querySelector('#ajhb-sort-select');
    var destinationInput = root.querySelector('#ajhb-destination');
    var searchForm = root.querySelector('#ajhb-search-form');
    var drawer = root.querySelector('#ajhb-mobile-drawer');
    var backdrop = root.querySelector('#ajhb-drawer-backdrop');
    var openFiltersBtn = root.querySelector('#ajhb-open-filters');
    var closeFiltersBtn = root.querySelector('#ajhb-close-filters');
    var applyMobileFiltersBtn = root.querySelector('#ajhb-apply-mobile-filters');

    if (!desktopFilters || !mobileFilters || !hotelList || !countEl || !emptyState || !chipsEl || !sortSelect || !destinationInput || !searchForm || !drawer || !backdrop || !openFiltersBtn || !closeFiltersBtn || !applyMobileFiltersBtn) {
        return;
    }

    desktopFilters.innerHTML = filterHTML;
    mobileFilters.innerHTML = filterHTML;

    function readFilters(container) {
        var nameInput = container.querySelector('[data-ajhb="nameFilter"]');
        var minPriceInput = container.querySelector('[data-ajhb="minPrice"]');
        var maxPriceInput = container.querySelector('[data-ajhb="maxPrice"]');

        return {
            name: nameInput ? nameInput.value.trim().toLowerCase() : '',
            minPrice: Number(minPriceInput ? minPriceInput.value : 0),
            maxPrice: Number(maxPriceInput ? maxPriceInput.value : 0),
            type: (container.querySelector('input[name="type"]:checked') || {}).value || '',
            minRating: Number(((container.querySelector('input[name="rating"]:checked') || {}).value) || 0),
            stars: Array.prototype.slice.call(container.querySelectorAll('input[name="stars"]:checked')).map(function (input) { return Number(input.value); }),
            amenities: Array.prototype.slice.call(container.querySelectorAll('input[name="amenity"]:checked')).map(function (input) { return input.value; }),
            boards: Array.prototype.slice.call(container.querySelectorAll('input[name="board"]:checked')).map(function (input) { return input.value; }),
            popular: !!container.querySelector('input[name="popular"]:checked'),
            discountOnly: !!container.querySelector('input[name="discount"]:checked'),
            availableOnly: !!container.querySelector('input[name="available"]:checked'),
            destination: destinationInput.value.trim().toLowerCase()
        };
    }

    function matchHotel(hotel, filters) {
        var combinedText = (hotel.name + ' ' + hotel.location + ' ' + hotel.description + ' ' + hotel.category).toLowerCase();
        if (filters.destination && combinedText.indexOf(filters.destination) === -1) return false;
        if (filters.name && combinedText.indexOf(filters.name) === -1) return false;
        if (filters.minPrice && (hotel.price === null || hotel.price < filters.minPrice)) return false;
        if (filters.maxPrice && (hotel.price === null || hotel.price > filters.maxPrice)) return false;
        if (filters.type && hotel.type !== filters.type) return false;
        if (filters.minRating && (hotel.rating === null || hotel.rating < filters.minRating)) return false;
        if (filters.stars.length && filters.stars.indexOf(hotel.stars) === -1) return false;
        if (filters.amenities.length && !filters.amenities.every(function (item) { return hotel.amenities.indexOf(item) !== -1; })) return false;
        if (filters.boards.length && (!hotel.board || filters.boards.indexOf(hotel.board) === -1)) return false;
        if (filters.popular && !hotel.popular) return false;
        if (filters.discountOnly && !hotel.discount) return false;
        if (filters.availableOnly && !hotel.available) return false;
        return true;
    }

    function sortHotels(list) {
        var mode = sortSelect.value;
        var sorted = list.slice();
        if (mode === 'price-asc') sorted.sort(function (a, b) { return (a.price || 0) - (b.price || 0); });
        if (mode === 'price-desc') sorted.sort(function (a, b) { return (b.price || 0) - (a.price || 0); });
        if (mode === 'rating-desc') sorted.sort(function (a, b) { return (b.rating || 0) - (a.rating || 0); });
        if (mode === 'stars-desc') sorted.sort(function (a, b) { return (b.stars || 0) - (a.stars || 0); });
        if (mode === 'discount-desc') sorted.sort(function (a, b) { return (b.discount || 0) - (a.discount || 0); });
        if (mode === 'recommended') sorted.sort(function (a, b) { return (Number(!!b.popular) - Number(!!a.popular)) || ((b.stars || 0) - (a.stars || 0)) || ((a.price || 0) - (b.price || 0)); });
        return sorted;
    }

    function getRatingLabel(rating) {
        if (rating === null || rating === undefined || rating === 0) return 'Ajinsafro';
        if (rating >= 9) return 'Wonderful';
        if (rating >= 8.5) return 'Excellent';
        if (rating >= 8) return 'Very good';
        if (rating >= 7) return 'Good';
        return 'Correct';
    }

    function formatPrice(value) {
        if (value === null || value === undefined || value === '' || Number.isNaN(Number(value))) {
            return 'Sur demande';
        }
        return Number(value).toLocaleString('fr-FR') + ' ' + (config.currency || 'DH');
    }

    function renderHotelCard(hotel) {
        var amenities = hotel.amenities.slice(0, 4).map(function (key) {
            return '<span class="amenity">' + (amenityLabels[key] || key) + '</span>';
        }).join('');
        var stars = hotel.stars > 0 ? new Array(hotel.stars + 1).join('★') : 'Type libre';
        var ratingMarkup = hotel.rating
            ? '<div class="rating-box" style="margin-top:8px;">' +
                '<div class="rating-text"><strong>' + getRatingLabel(hotel.rating) + '</strong><span>' + (hotel.reviews > 0 ? hotel.reviews.toLocaleString('fr-FR') + ' avis' : hotel.category) + '</span></div>' +
                '<div class="rating-score">' + hotel.rating.toFixed(1) + '</div>' +
              '</div>'
            : '<div class="rating-box" style="margin-top:8px;">' +
                '<div class="rating-text"><strong>' + hotel.category + '</strong><span>' + (hotel.stars > 0 ? hotel.stars + ' etoiles' : 'Hebergement') + '</span></div>' +
                '<div class="rating-score">' + (hotel.stars > 0 ? hotel.stars : '•') + '</div>' +
              '</div>';

        return '' +
            '<article class="hotel-card" data-id="' + hotel.id + '">' +
                '<div class="photo-wrap">' +
                    '<img src="' + hotel.image + '" alt="' + hotel.name + '" loading="lazy">' +
                    '<button class="fav" type="button" aria-label="Ajouter aux favoris">♡</button>' +
                    (hotel.popular ? '<span class="photo-badge">' + (strings.recommended || 'Recommande') + '</span>' : '') +
                '</div>' +
                '<div class="hotel-main">' +
                    '<h3>' + hotel.name + '</h3>' +
                    '<div class="location">📍 ' + (hotel.location || 'Localisation non renseignee') + '</div>' +
                    '<div class="meta"><span class="stars">' + stars + '</span><span>' + (boardLabels[hotel.board] || hotel.category || 'Formule flexible') + '</span></div>' +
                    '<p class="description">' + (hotel.description || 'Hebergement Ajinsafro disponible dans notre catalogue.') + '</p>' +
                    '<div class="amenities">' + amenities + '</div>' +
                    '<div class="good-note">✓ ' + (strings.support_note || 'Confirmation rapide · Support Ajinsafro') + '</div>' +
                '</div>' +
                '<aside class="hotel-side">' +
                    '<div>' +
                        (hotel.discount ? '<span class="discount">' + hotel.discount + '% off</span>' : '') +
                        ratingMarkup +
                    '</div>' +
                    '<div class="price-area">' +
                        '<small>' + (strings.from_price || 'A partir de') + '</small>' +
                        '<div>' + (hotel.oldPrice ? '<span class="old-price">' + formatPrice(hotel.oldPrice) + '</span>' : '') + '<span class="price">' + formatPrice(hotel.price) + '</span></div>' +
                        '<div class="tax">' + (strings.per_night || 'par nuit') + '</div>' +
                    '</div>' +
                    '<div class="card-actions">' +
                        '<a class="primary-btn" href="' + hotel.url + '">' + (strings.see_offer || 'Voir l\'offre') + '</a>' +
                        '<a class="secondary-btn" href="' + hotel.url + '">' + (strings.ask_availability || 'Demander disponibilite') + '</a>' +
                    '</div>' +
                '</aside>' +
            '</article>';
    }

    function renderChips(filters) {
        var chips = [];
        if (filters.destination) chips.push('Destination: ' + filters.destination);
        if (filters.name) chips.push('Recherche: ' + filters.name);
        if (filters.minPrice) chips.push('Min ' + filters.minPrice + ' ' + (config.currency || 'DH'));
        if (filters.maxPrice) chips.push('Max ' + filters.maxPrice + ' ' + (config.currency || 'DH'));
        if (filters.type) chips.push('Type: ' + filters.type);
        if (filters.minRating) chips.push('Note ' + filters.minRating + '+');
        filters.stars.forEach(function (star) { chips.push(star + ' etoiles'); });
        filters.amenities.forEach(function (key) { chips.push(amenityLabels[key] || key); });
        filters.boards.forEach(function (key) { chips.push(boardLabels[key] || key); });
        if (filters.popular) chips.push('A la une');
        if (filters.discountOnly) chips.push('Promotions');
        if (filters.availableOnly) chips.push(strings.available || 'Disponible');

        chipsEl.innerHTML = chips.map(function (label) {
            return '<span class="chip">' + label + '<button type="button" data-ajhb-action="reset">×</button></span>';
        }).join('');
    }

    function applyFilters(container) {
        var filters = readFilters(container || desktopFilters);
        var filtered = sortHotels(hotels.filter(function (hotel) { return matchHotel(hotel, filters); }));
        hotelList.innerHTML = filtered.map(renderHotelCard).join('');
        countEl.textContent = String(filtered.length);
        emptyState.style.display = filtered.length ? 'none' : 'block';
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
