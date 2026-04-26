(function () {
    'use strict';

    var root = document.getElementById('aj-hebergement-booking');
    if (!root) {
        return;
    }

    var hotels = [
        { id: 1, name: 'Travelodge Kowloon', location: 'Jordan, Hong Kong', type: 'hotel', stars: 4, rating: 8.0, reviews: 1008, price: 71, oldPrice: 118, discount: 40, image: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=900&q=80', amenities: ['wifi', 'breakfast', 'parking'], board: 'breakfast', description: 'Hotel moderne proche du metro, ideal pour un sejour pratique et confortable.', popular: true, available: true },
        { id: 2, name: 'Eaton HK', location: 'Jordan, Hong Kong', type: 'hotel', stars: 4, rating: 8.6, reviews: 936, price: 143, oldPrice: null, discount: 0, image: 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=900&q=80', amenities: ['wifi', 'pool', 'gym', 'restaurant'], board: 'room_only', description: 'Adresse urbaine avec restaurants, espace bien-etre et acces rapide aux quartiers centraux.', popular: true, available: true },
        { id: 3, name: 'Cordis Hong Kong', location: 'Mong Kok, Hong Kong', type: 'resort', stars: 5, rating: 9.2, reviews: 1701, price: 157, oldPrice: null, discount: 0, image: 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=900&q=80', amenities: ['wifi', 'pool', 'spa', 'gym', 'restaurant'], board: 'breakfast', description: 'Grand etablissement premium avec piscine, spa et excellente note client.', popular: true, available: true },
        { id: 4, name: 'InterContinental Grand Stanford', location: 'Tsim Sha Tsui, Hong Kong', type: 'hotel', stars: 5, rating: 8.8, reviews: 1521, price: 152, oldPrice: 211, discount: 28, image: 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=900&q=80', amenities: ['wifi', 'pool', 'sea_view', 'restaurant'], board: 'half_board', description: 'Vue imprenable, emplacement central et services adaptes aux voyages premium.', popular: false, available: true },
        { id: 5, name: 'Marco Polo Hongkong Hotel', location: 'Tsim Sha Tsui, Hong Kong', type: 'hotel', stars: 5, rating: 8.4, reviews: 1337, price: 134, oldPrice: null, discount: 0, image: 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=900&q=80', amenities: ['wifi', 'family', 'restaurant', 'parking'], board: 'breakfast', description: 'Hebergement familial proche des centres commerciaux et attractions principales.', popular: false, available: true },
        { id: 6, name: 'The Peninsula Hong Kong', location: 'Tsim Sha Tsui, Hong Kong', type: 'hotel', stars: 5, rating: 9.6, reviews: 912, price: 753, oldPrice: null, discount: 0, image: 'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=900&q=80', amenities: ['wifi', 'pool', 'spa', 'sea_view', 'gym'], board: 'full_board', description: 'Experience luxe iconique avec service haut de gamme et equipements premium.', popular: true, available: true },
        { id: 7, name: 'Harbour Plaza 8 Degrees', location: 'Kowloon City, Hong Kong', type: 'apartment', stars: 4, rating: 8.4, reviews: 1117, price: 94, oldPrice: null, discount: 0, image: 'https://images.unsplash.com/photo-1582719508461-905c673771fd?auto=format&fit=crop&w=900&q=80', amenities: ['wifi', 'pool', 'family', 'parking'], board: 'room_only', description: 'Bon rapport qualite-prix avec piscine et chambres familiales disponibles.', popular: false, available: true },
        { id: 8, name: 'Nathan Hotel', location: 'Jordan, Hong Kong', type: 'hotel', stars: 4, rating: 8.8, reviews: 1609, price: 134, oldPrice: 179, discount: 25, image: 'https://images.unsplash.com/photo-1564501049412-61c2a3083791?auto=format&fit=crop&w=900&q=80', amenities: ['wifi', 'restaurant', 'gym'], board: 'breakfast', description: 'Chambres confortables, emplacement pratique et promotion disponible.', popular: true, available: true },
        { id: 9, name: 'B P International', location: 'Tsim Sha Tsui, Hong Kong', type: 'hotel', stars: 3, rating: 8.4, reviews: 1918, price: 136, oldPrice: null, discount: 0, image: 'https://images.unsplash.com/photo-1561501900-3701fa6a0864?auto=format&fit=crop&w=900&q=80', amenities: ['wifi', 'restaurant'], board: 'room_only', description: 'Solution pratique pour les voyageurs qui cherchent un sejour central.', popular: false, available: true },
        { id: 10, name: 'Holiday Inn Express Mong Kok', location: 'Mong Kok, Hong Kong', type: 'hotel', stars: 3, rating: 8.6, reviews: 824, price: 104, oldPrice: null, discount: 0, image: 'https://images.unsplash.com/photo-1568495248636-6432b97bd949?auto=format&fit=crop&w=900&q=80', amenities: ['wifi', 'breakfast', 'family'], board: 'breakfast', description: 'Petit-dejeuner inclus et acces simple aux transports publics.', popular: false, available: true },
        { id: 11, name: 'Soravit on Granville', location: 'Tsim Sha Tsui, Hong Kong', type: 'riad', stars: 4, rating: 8.4, reviews: 796, price: 86, oldPrice: 107, discount: 20, image: 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&w=900&q=80', amenities: ['wifi', 'air_conditioning'], board: 'room_only', description: 'Design moderne, prix attractif et reduction disponible pour dates selectionnees.', popular: false, available: true },
        { id: 12, name: 'The Langham Hong Kong', location: 'Tsim Sha Tsui, Hong Kong', type: 'hotel', stars: 5, rating: 9.0, reviews: 1300, price: 223, oldPrice: null, discount: 0, image: 'https://images.unsplash.com/photo-1590073242678-70ee3fc28e8e?auto=format&fit=crop&w=900&q=80', amenities: ['wifi', 'spa', 'pool', 'gym', 'restaurant'], board: 'half_board', description: 'Hotel haut de gamme avec spa, gastronomie et emplacement premium.', popular: true, available: true }
    ];

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

    var filterHTML = '\
      <details class="accordion" open>\
        <summary>Search by property name</summary>\
        <div class="filter-body">\
          <input class="filter-search" data-ajhb=\"nameFilter\" type=\"text\" placeholder=\"ex: Marriott, Riad, Villa...\">\
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
            <input data-ajhb=\"minPrice\" type=\"number\" placeholder=\"Min $\">\
            <input data-ajhb=\"maxPrice\" type=\"number\" placeholder=\"Max $\">\
          </div>\
          <div class="price-histogram" aria-hidden="true">\
            <span style="height:24%"></span><span style="height:35%"></span><span style="height:48%"></span><span style="height:66%"></span><span style="height:80%"></span><span style="height:62%"></span><span style="height:54%"></span><span style="height:76%"></span><span style="height:88%"></span><span style="height:58%"></span><span style="height:44%"></span><span style="height:35%"></span><span style="height:28%"></span>\
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
      <details class="accordion">\
        <summary>Payment type</summary>\
        <div class="filter-body">\
          <label class="check-row"><input type="checkbox"> Reserve now, pay later</label>\
          <label class="check-row"><input type="checkbox"> Paiement sur place</label>\
          <label class="check-row"><input type="checkbox"> Annulation gratuite</label>\
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
      <details class="accordion">\
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

    if (!desktopFilters || !mobileFilters || !hotelList || !countEl || !emptyState || !chipsEl || !sortSelect || !destinationInput || !searchForm || !drawer || !backdrop) {
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
        var combinedText = (hotel.name + ' ' + hotel.location + ' ' + hotel.description).toLowerCase();
        if (filters.destination && combinedText.indexOf(filters.destination) === -1) return false;
        if (filters.name && combinedText.indexOf(filters.name) === -1) return false;
        if (filters.minPrice && hotel.price < filters.minPrice) return false;
        if (filters.maxPrice && hotel.price > filters.maxPrice) return false;
        if (filters.type && hotel.type !== filters.type) return false;
        if (filters.minRating && hotel.rating < filters.minRating) return false;
        if (filters.stars.length && filters.stars.indexOf(hotel.stars) === -1) return false;
        if (filters.amenities.length && !filters.amenities.every(function (item) { return hotel.amenities.indexOf(item) !== -1; })) return false;
        if (filters.boards.length && filters.boards.indexOf(hotel.board) === -1) return false;
        if (filters.popular && !hotel.popular) return false;
        if (filters.discountOnly && !hotel.discount) return false;
        if (filters.availableOnly && !hotel.available) return false;
        return true;
    }

    function sortHotels(list) {
        var mode = sortSelect.value;
        var sorted = list.slice();
        if (mode === 'price-asc') sorted.sort(function (a, b) { return a.price - b.price; });
        if (mode === 'price-desc') sorted.sort(function (a, b) { return b.price - a.price; });
        if (mode === 'rating-desc') sorted.sort(function (a, b) { return b.rating - a.rating; });
        if (mode === 'stars-desc') sorted.sort(function (a, b) { return b.stars - a.stars; });
        if (mode === 'discount-desc') sorted.sort(function (a, b) { return b.discount - a.discount; });
        if (mode === 'recommended') sorted.sort(function (a, b) { return (Number(b.popular) - Number(a.popular)) || (b.rating - a.rating); });
        return sorted;
    }

    function getRatingLabel(rating) {
        if (rating >= 9) return 'Wonderful';
        if (rating >= 8.5) return 'Excellent';
        if (rating >= 8) return 'Very good';
        if (rating >= 7) return 'Good';
        return 'Correct';
    }

    function renderHotelCard(hotel) {
        var amenities = hotel.amenities.slice(0, 4).map(function (key) {
            return '<span class="amenity">' + (amenityLabels[key] || key) + '</span>';
        }).join('');
        var stars = new Array(hotel.stars + 1).join('★');

        return '' +
            '<article class="hotel-card" data-id="' + hotel.id + '">' +
                '<div class="photo-wrap">' +
                    '<img src="' + hotel.image + '" alt="' + hotel.name + '" loading="lazy">' +
                    '<button class="fav" type="button" aria-label="Ajouter aux favoris">♡</button>' +
                    (hotel.popular ? '<span class="photo-badge">Recommande</span>' : '') +
                '</div>' +
                '<div class="hotel-main">' +
                    '<h3>' + hotel.name + '</h3>' +
                    '<div class="location">📍 ' + hotel.location + '</div>' +
                    '<div class="meta"><span class="stars">' + stars + '</span><span>' + (boardLabels[hotel.board] || 'Formule flexible') + '</span></div>' +
                    '<p class="description">' + hotel.description + '</p>' +
                    '<div class="amenities">' + amenities + '</div>' +
                    '<div class="good-note">✓ Confirmation rapide · Support Ajinsafro</div>' +
                '</div>' +
                '<aside class="hotel-side">' +
                    '<div>' +
                        (hotel.discount ? '<span class="discount">' + hotel.discount + '% off</span>' : '') +
                        '<div class="rating-box" style="margin-top:8px;">' +
                            '<div class="rating-text"><strong>' + getRatingLabel(hotel.rating) + '</strong><span>' + hotel.reviews.toLocaleString('fr-FR') + ' avis</span></div>' +
                            '<div class="rating-score">' + hotel.rating.toFixed(1) + '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="price-area">' +
                        '<small>A partir de</small>' +
                        '<div>' + (hotel.oldPrice ? '<span class="old-price">$' + hotel.oldPrice + '</span>' : '') + '<span class="price">$' + hotel.price + '</span></div>' +
                        '<div class="tax">par nuit · taxes incluses selon offre</div>' +
                    '</div>' +
                    '<div class="card-actions">' +
                        '<button class="primary-btn" type="button">Voir l\'offre</button>' +
                        '<button class="secondary-btn" type="button">Demander disponibilite</button>' +
                    '</div>' +
                '</aside>' +
            '</article>';
    }

    function renderChips(filters) {
        var chips = [];
        if (filters.destination) chips.push('Destination: ' + filters.destination);
        if (filters.name) chips.push('Recherche: ' + filters.name);
        if (filters.minPrice) chips.push('Min $' + filters.minPrice);
        if (filters.maxPrice) chips.push('Max $' + filters.maxPrice);
        if (filters.type) chips.push('Type: ' + filters.type);
        if (filters.minRating) chips.push('Note ' + filters.minRating + '+');
        filters.stars.forEach(function (star) { chips.push(star + ' etoiles'); });
        filters.amenities.forEach(function (key) { chips.push(amenityLabels[key] || key); });
        filters.boards.forEach(function (key) { chips.push(boardLabels[key] || key); });
        if (filters.popular) chips.push('A la une');
        if (filters.discountOnly) chips.push('Promotions');
        if (filters.availableOnly) chips.push('Disponible');

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

    root.querySelector('#ajhb-open-filters').addEventListener('click', function () {
        drawer.classList.add('active');
        backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    });

    root.querySelector('#ajhb-close-filters').addEventListener('click', closeDrawer);
    backdrop.addEventListener('click', closeDrawer);
    root.querySelector('#ajhb-apply-mobile-filters').addEventListener('click', function () {
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
