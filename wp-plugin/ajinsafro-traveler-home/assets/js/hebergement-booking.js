(function () {
  'use strict';

  var root = document.getElementById('aj-hebergement-booking');
  if (!root) {
    return;
  }

  var config = typeof window.ajthHebergementConfig === 'object' && window.ajthHebergementConfig
    ? window.ajthHebergementConfig
    : {};

  var currency = config.currency || 'DH';

  var amenityLabels = {
    wifi: 'Wi-Fi',
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

  var packAmenityMap = {
    hebergement: null,
    breakfast: 'breakfast',
    'petit-dejeuner': 'breakfast',
    'petit-dejeuner-inclus': 'breakfast',
    'demi-pension': 'half_board',
    'pension-complete': 'full_board',
    transfert: 'transfer',
    'transfert-optionnel': 'transfer',
    'activite-optionnelle': 'activity',
    'offre-famille': 'family',
    'conseils-locaux': 'assistance',
    'guide-optionnel': 'assistance',
    'assistance-ajinsafro': 'assistance',
    'support-reservation': 'assistance'
  };

  function escapeHtml(value) {
    return String(value === null || value === undefined ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function normalizeTerm(value) {
    return String(value || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .trim();
  }

  function formatPrice(value) {
    if (value === null || value === undefined || value === '' || Number.isNaN(Number(value))) {
      return 'Sur demande';
    }
    return Number(value).toLocaleString('fr-FR') + ' ' + currency;
  }

  function renderStars(count) {
    var c = Number(count || 0);
    if (c <= 0) {
      return '<span class="aj-star-line aj-star-line--empty">Type libre</span>';
    }
    var html = '<span class="aj-star-line" aria-label="' + c + ' etoiles">';
    for (var i = 0; i < c; i++) {
      html += '<span aria-hidden="true">★</span>';
    }
    html += '</span>';
    return html;
  }

  function truncateText(value, maxLength) {
    var text = String(value || '').replace(/\s+/g, ' ').trim();
    if (!text || text.length <= maxLength) {
      return text;
    }
    return text.slice(0, maxLength).replace(/[.,;:!?-]?\s+\S*$/, '').trim() + '...';
  }

  function readCheckedValues(container) {
    if (!container) {
      return [];
    }
    var values = [];
    container.querySelectorAll('input[type="checkbox"]').forEach(function (input) {
      if (input.checked) {
        values.push(input.value);
      }
    });
    return values;
  }

  function readServices(container) {
    return readCheckedValues(container).filter(Boolean);
  }

  function buildLocation(item) {
    var values = [item.city, item.destination, item.country].filter(Boolean);
    return values.filter(function (value, index, arr) {
      return arr.indexOf(value) === index;
    }).join(', ') || item.address || '';
  }

  function resolveNavUrl(value, fallbackPath) {
    var raw = String(value || '').trim();
    if (raw && raw !== '#' && raw.toLowerCase() !== 'javascript:void(0)') {
      return raw;
    }
    return fallbackPath || '/hebergement/';
  }

  function normalizeItem(raw) {
    var item = raw && typeof raw === 'object' ? raw : {};
    var isPack = item.kind === 'pack';
    var city = String(item.city || '');
    var destination = String(item.destination || '');
    var country = String(item.country || '');
    var address = String(item.address || item.location || '');
    var amenities = [];

    if (isPack && Array.isArray(item.includes)) {
      item.includes.forEach(function (inc) {
        var key = packAmenityMap[String(inc).toLowerCase().trim()];
        if (key && !amenities.includes(key)) {
          amenities.push(key);
        }
      });
    } else if (Array.isArray(item.amenities)) {
      amenities = item.amenities.filter(Boolean);
    }

    var description = String(item.description || item.excerpt || item.short_description || '');
    if (!description && isPack) {
      description = 'Sejour Ajinsafro pret a reserver avec les essentiels deja inclus.';
    } else if (!description) {
      description = 'Hebergement Ajinsafro disponible dans notre catalogue.';
    }

    function makeSlug(value) {
      return String(value || '')
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');
    }

    var normalized = {
      isPack: isPack,
      id: Number(item.id || 0),
      slug: isPack ? makeSlug(item.id || item.slug || item.name || item.title || '') : '',
      title: String(item.title || item.name || ''),
      url: resolveNavUrl(item.url, isPack ? '/hebergement/' : '/hebergement/'),
      image: String(item.image || item.image_url || ''),
      city: city,
      destination: destination,
      country: country,
      address: address,
      category: String(isPack ? (item.typeLabel || 'Hotel') : (item.category || item.type_label || 'Hotel')),
      type: String(item.type || 'hotel'),
      typeLabel: String(item.type_label || item.typeLabel || item.category || item.type || ''),
      stars: Number(isPack ? 0 : (item.stars || 0)),
      price: item.price !== null && item.price !== undefined && item.price !== '' ? Number(item.price) : null,
      oldPrice: item.oldPrice !== null && item.oldPrice !== undefined && item.oldPrice !== '' ? Number(item.oldPrice) : null,
      description: description,
      amenities: amenities,
      popular: Boolean(item.popular || item.is_featured || item.featured),
      available: item.available !== false,
      availabilityLabel: String(item.availability_label || (item.available !== false ? 'Disponible' : 'Complet')),
      discount: Number(item.discount || 0),
      rating: item.rating !== null && item.rating !== undefined && item.rating !== '' ? Number(item.rating) : null,
      reviews: Number(item.reviews || 0),
      badge: String(isPack ? (item.badges && item.badges[0] ? item.badges[0] : '') : (item.badge || '')),
      duration: String(isPack ? (item.duration || '') : ''),
      boardLabel: String(isPack ? (item.pensionLabel || '') : (item.boardLabel || item.board || '')),
      order: Number(item.order || 0)
    };

    normalized.location = isPack
      ? [city, country].filter(Boolean).join(', ') || 'Maroc'
      : buildLocation(normalized);

    normalized.searchText = normalizeTerm([
      normalized.title,
      normalized.location,
      normalized.city,
      normalized.destination,
      normalized.country,
      normalized.address,
      normalized.category,
      normalized.type,
      normalized.typeLabel,
      normalized.description
    ].join(' '));

    return normalized;
  }

  var hotelItems = (Array.isArray(config.hotels) ? config.hotels : [])
    .map(normalizeItem)
    .filter(function (item) { return !item.isPack; });

  var packItems = (Array.isArray(config.packs) ? config.packs : [])
    .map(normalizeItem)
    .filter(function (item) { return item.isPack; });

  var destinations = Array.from(new Set(hotelItems.map(function (item) {
    return item.location;
  }).filter(Boolean))).sort(function (a, b) {
    return a.localeCompare(b, 'fr', { sensitivity: 'base' });
  });

  var types = Array.from(new Set(hotelItems.map(function (item) {
    return item.category;
  }).filter(Boolean))).sort(function (a, b) {
    return a.localeCompare(b, 'fr', { sensitivity: 'base' });
  });

  var state = {
    hasSearched: false,
    selectedPackSlug: '',
    destination: '',
    date: '',
    type: '',
    stars: [],
    nameQuery: '',
    filterDestination: '',
    filterType: '',
    minPrice: '',
    maxPrice: '',
    popularOnly: false,
    availableOnly: false,
    promoOnly: false,
    services: [],
    sort: 'recommended',
    view: 'list'
  };

  var els = {
    featuredSection: root.querySelector('#ajhb-featured-section'),
    featuredGrid: root.querySelector('#ajhb-featured-grid'),
    packDetailSection: root.querySelector('#ajhb-pack-detail-section'),
    packDetail: root.querySelector('#ajhb-pack-detail'),
    catalogSection: root.querySelector('#ajhb-catalog-section'),
    resultsGrid: root.querySelector('#ajhb-results-grid'),
    resultsCount: root.querySelector('#ajhb-count'),
    activeFilters: root.querySelector('#ajhb-active-filters'),
    emptyState: root.querySelector('#ajhb-empty-state'),
    emptyReset: root.querySelector('#ajhb-empty-reset'),
    sortSelect: root.querySelector('#ajhb-sort-select'),
    viewToggle: root.querySelector('#ajhb-view-toggle'),
    viewList: root.querySelector('#ajhb-view-list'),
    viewGrid: root.querySelector('#ajhb-view-grid'),
    searchForm: root.querySelector('#ajhb-search-form'),
    heroDestination: root.querySelector('#ajhb-destination'),
    heroDate: root.querySelector('#ajhb-date'),
    heroType: root.querySelector('#ajhb-type'),
    heroStars: root.querySelector('#ajhb-stars'),
    desktopFilters: root.querySelector('#ajhb-desktop-filters'),
    filterName: root.querySelector('#ajhb-filter-name'),
    filterDestination: root.querySelector('#ajhb-filter-destination'),
    filterType: root.querySelector('#ajhb-filter-type'),
    filterStars: root.querySelector('#ajhb-filter-stars'),
    filterPriceMin: root.querySelector('#ajhb-filter-price-min'),
    filterPriceMax: root.querySelector('#ajhb-filter-price-max'),
    filterPopular: root.querySelector('#ajhb-filter-popular'),
    filterAvailable: root.querySelector('#ajhb-filter-available'),
    filterPromo: root.querySelector('#ajhb-filter-promo'),
    servicesGroup: root.querySelector('#ajhb-filter-ac') ? root.querySelector('#ajhb-filter-ac').closest('.aj-filter-group') : null,
    resetFilters: root.querySelector('#ajhb-reset-filters'),
    mobileName: root.querySelector('#ajhb-mobile-name'),
    mobileDestination: root.querySelector('#ajhb-mobile-destination'),
    mobileType: root.querySelector('#ajhb-mobile-type'),
    mobileStars: root.querySelector('#ajhb-mobile-stars'),
    mobilePriceMin: root.querySelector('#ajhb-mobile-price-min'),
    mobilePriceMax: root.querySelector('#ajhb-mobile-price-max'),
    mobilePopular: root.querySelector('#ajhb-mobile-popular'),
    mobileAvailable: root.querySelector('#ajhb-mobile-available'),
    mobilePromo: root.querySelector('#ajhb-mobile-promo'),
    mobilePanel: root.querySelector('#ajhb-mobile-panel'),
    openMobileFilters: root.querySelector('#ajhb-open-filters'),
    closeMobileFilters: root.querySelector('#ajhb-close-mobile-filters'),
    applyMobileFilters: root.querySelector('#ajhb-apply-mobile-filters'),
    resetMobileFilters: root.querySelector('#ajhb-reset-mobile-filters'),
    mobileBackdrop: root.querySelector('#ajhb-mobile-backdrop')
  };

  if (!els.featuredGrid) {
    return;
  }

  function fillSelect(select, values, placeholder) {
    if (!select) {
      return;
    }
    var options = ['<option value="">' + escapeHtml(placeholder) + '</option>'];
    values.forEach(function (value) {
      options.push('<option value="' + escapeHtml(value) + '">' + escapeHtml(value) + '</option>');
    });
    select.innerHTML = options.join('');
  }

  fillSelect(els.filterDestination, destinations, 'Toutes les destinations');
  fillSelect(els.mobileDestination, destinations, 'Toutes les destinations');
  fillSelect(els.filterType, types, 'Tous les types');
  fillSelect(els.mobileType, types, 'Tous les types');

  function getStorageView() {
    try {
      return window.localStorage ? window.localStorage.getItem('ajhb_view_mode') : '';
    } catch (error) {
      return '';
    }
  }

  function persistView(view) {
    try {
      if (window.localStorage) {
        window.localStorage.setItem('ajhb_view_mode', view);
      }
    } catch (error) {
      // Ignore storage access issues.
    }
  }

  function updateViewUrl(view) {
    if (!window.history || !window.history.replaceState) {
      return;
    }

    var url = new URL(window.location.href);
    url.searchParams.set('view', view);
    window.history.replaceState({}, '', url.toString());
  }

  function initFromUrl() {
    var params = new URLSearchParams(window.location.search);
    var destination = params.get('destination') || '';
    var date = params.get('date') || '';
    var type = params.get('type') || '';
    var stars = params.get('stars') || '';
    var view = params.get('view') || getStorageView() || 'list';

    state.view = view === 'grid' ? 'grid' : 'list';

    if (destination || date || type || stars) {
      state.hasSearched = true;
      state.destination = destination;
      state.date = date;
      state.type = type;
      state.stars = stars ? [stars] : [];
    }
  }

  function syncDesktopControls() {
    if (els.heroDestination) els.heroDestination.value = state.destination;
    if (els.heroDate) els.heroDate.value = state.date;
    if (els.heroType) els.heroType.value = state.type;
    if (els.heroStars) els.heroStars.value = state.stars[0] || '';
    if (els.filterName) els.filterName.value = state.nameQuery;
    if (els.filterDestination) els.filterDestination.value = state.filterDestination;
    if (els.filterType) els.filterType.value = state.filterType;
    if (els.filterPriceMin) els.filterPriceMin.value = state.minPrice;
    if (els.filterPriceMax) els.filterPriceMax.value = state.maxPrice;
    if (els.filterPopular) els.filterPopular.checked = state.popularOnly;
    if (els.filterAvailable) els.filterAvailable.checked = state.availableOnly;
    if (els.filterPromo) els.filterPromo.checked = state.promoOnly;

    if (els.filterStars) {
      els.filterStars.querySelectorAll('input[type="checkbox"]').forEach(function (input) {
        input.checked = state.stars.includes(input.value);
      });
    }

    if (els.servicesGroup) {
      els.servicesGroup.querySelectorAll('input[type="checkbox"]').forEach(function (input) {
        input.checked = state.services.includes(input.value);
      });
    }
  }

  function syncMobileControls() {
    if (els.mobileName) els.mobileName.value = state.nameQuery;
    if (els.mobileDestination) els.mobileDestination.value = state.filterDestination;
    if (els.mobileType) els.mobileType.value = state.filterType;
    if (els.mobilePriceMin) els.mobilePriceMin.value = state.minPrice;
    if (els.mobilePriceMax) els.mobilePriceMax.value = state.maxPrice;
    if (els.mobilePopular) els.mobilePopular.checked = state.popularOnly;
    if (els.mobileAvailable) els.mobileAvailable.checked = state.availableOnly;
    if (els.mobilePromo) els.mobilePromo.checked = state.promoOnly;

    if (els.mobileStars) {
      els.mobileStars.querySelectorAll('input[type="checkbox"]').forEach(function (input) {
        input.checked = state.stars.includes(input.value);
      });
    }

    if (els.mobilePanel) {
      els.mobilePanel.querySelectorAll('.aj-filter-group input[type="checkbox"][value]').forEach(function (input) {
        if (['wifi', 'pool', 'parking', 'breakfast', 'air_conditioning'].includes(input.value)) {
          input.checked = state.services.includes(input.value);
        }
      });
    }
  }

  function syncAllControls() {
    syncDesktopControls();
    syncMobileControls();
    if (els.sortSelect) {
      els.sortSelect.value = state.sort;
    }
    if (els.resultsGrid) {
      els.resultsGrid.dataset.view = state.view;
      els.resultsGrid.classList.toggle('is-grid-view', state.view === 'grid');
      els.resultsGrid.classList.toggle('is-list-view', state.view !== 'grid');
    }
    if (els.viewList) {
      var isList = state.view === 'list';
      els.viewList.classList.toggle('is-active', isList);
      els.viewList.setAttribute('aria-pressed', isList ? 'true' : 'false');
    }
    if (els.viewGrid) {
      var isGrid = state.view === 'grid';
      els.viewGrid.classList.toggle('is-active', isGrid);
      els.viewGrid.setAttribute('aria-pressed', isGrid ? 'true' : 'false');
    }
  }

  function matchesFilters(item) {
    if (state.destination && item.searchText.indexOf(normalizeTerm(state.destination)) === -1) {
      return false;
    }

    if (state.filterDestination && item.location !== state.filterDestination) {
      return false;
    }

    if (state.nameQuery && item.searchText.indexOf(normalizeTerm(state.nameQuery)) === -1) {
      return false;
    }

    if (state.type) {
      var requestedType = normalizeTerm(state.type);
      if (
        normalizeTerm(item.type) !== requestedType &&
        normalizeTerm(item.category) !== requestedType &&
        normalizeTerm(item.typeLabel) !== requestedType
      ) {
        return false;
      }
    }

    if (state.filterType) {
      var sidebarType = normalizeTerm(state.filterType);
      if (
        normalizeTerm(item.type) !== sidebarType &&
        normalizeTerm(item.category) !== sidebarType &&
        normalizeTerm(item.typeLabel) !== sidebarType
      ) {
        return false;
      }
    }

    if (state.stars.length && state.stars.indexOf(String(item.stars)) === -1) {
      return false;
    }

    if (state.minPrice && (item.price === null || item.price < Number(state.minPrice))) {
      return false;
    }

    if (state.maxPrice && (item.price === null || item.price > Number(state.maxPrice))) {
      return false;
    }

    if (state.popularOnly && !item.popular) {
      return false;
    }

    if (state.availableOnly && !item.available) {
      return false;
    }

    if (state.promoOnly && !(item.discount || item.oldPrice)) {
      return false;
    }

    if (state.services.length) {
      for (var i = 0; i < state.services.length; i++) {
        if (item.amenities.indexOf(state.services[i]) === -1) {
          return false;
        }
      }
    }

    return true;
  }

  function sortItems(items) {
    var sorted = items.slice();

    if (state.sort === 'price-asc') {
      sorted.sort(function (a, b) { return (a.price || 0) - (b.price || 0); });
    } else if (state.sort === 'price-desc') {
      sorted.sort(function (a, b) { return (b.price || 0) - (a.price || 0); });
    } else if (state.sort === 'rating-desc') {
      sorted.sort(function (a, b) { return (b.rating || 0) - (a.rating || 0); });
    } else if (state.sort === 'stars-desc') {
      sorted.sort(function (a, b) { return (b.stars || 0) - (a.stars || 0); });
    } else {
      sorted.sort(function (a, b) {
        return (Number(!!b.popular) - Number(!!a.popular))
          || ((b.rating || 0) - (a.rating || 0))
          || ((b.stars || 0) - (a.stars || 0))
          || ((a.price || 0) - (b.price || 0))
          || a.title.localeCompare(b.title, 'fr', { sensitivity: 'base' });
      });
    }

    return sorted;
  }

  function renderFeaturedCard(item) {
    var image = item.image
      ? '<img src="' + escapeHtml(item.image) + '" alt="' + escapeHtml(item.title) + '" loading="lazy">'
      : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#d9e9ff,#f4f8ff);color:#67809a;font-size:13px;font-weight:600;">Ajinsafro</div>';

    var priceHtml = item.price !== null
      ? '<div class="aj-featured-price"><small>A partir de</small>' + escapeHtml(formatPrice(item.price)) + '</div>'
      : '';

    var isSelected = state.selectedPackSlug && state.selectedPackSlug === item.slug;

    return '' +
      '<article class="aj-featured-card' + (isSelected ? ' is-selected is-highlighted' : '') + '" data-pack-slug="' + escapeHtml(item.slug) + '">' +
        '<a class="aj-featured-visual aj-featured-visual-link" href="' + escapeHtml(item.url) + '">' +
          image +
          '<span class="aj-badge">Pack</span>' +
          priceHtml +
        '</a>' +
        '<div class="aj-featured-content">' +
          '<div class="aj-inline-meta">' +
            '<span>' + escapeHtml(item.location || 'Maroc') + '</span>' +
            '<span>' + escapeHtml(item.category || 'Hebergement') + '</span>' +
          '</div>' +
          '<h3><a class="aj-featured-title-link" href="' + escapeHtml(item.url) + '">' + escapeHtml(item.title) + '</a></h3>' +
          '<p style="margin:0;color:var(--aj-muted);font-size:13px;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">' + escapeHtml(truncateText(item.description, 90)) + '</p>' +
          '<a class="aj-featured-link" href="' + escapeHtml(item.url) + '">Voir le pack</a>' +
        '</div>' +
      '</article>';
  }

  function renderPackDetail(item) {
    if (!els.packDetailSection || !els.packDetail) {
      return;
    }

    if (!item) {
      els.packDetailSection.hidden = true;
      els.packDetail.innerHTML = '';
      return;
    }

    var image = item.image
      ? '<img src="' + escapeHtml(item.image) + '" alt="' + escapeHtml(item.title) + '" loading="eager">'
      : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#d9e9ff,#f4f8ff);color:#67809a;font-size:13px;font-weight:600;">Ajinsafro</div>';

    var serviceLabels = (item.amenities || []).slice(0, 6).map(function (amenity) {
      return amenityLabels[amenity] || amenity;
    });

    var includedHtml = serviceLabels.length
      ? '<ul class="aj-pack-detail-list">' + serviceLabels.map(function (label) {
          return '<li>' + escapeHtml(label) + '</li>';
        }).join('') + '</ul>'
      : '';

    var description = item.description || item.excerpt || 'Pack hébergement prêt à réserver avec informations complètes.';
    var price = item.price !== null ? formatPrice(item.price) : 'Sur demande';

    els.packDetail.innerHTML = '' +
      '<div class="aj-pack-detail-media">' +
        image +
      '</div>' +
      '<div class="aj-pack-detail-body">' +
        '<div class="aj-pack-detail-topline">' +
          '<span class="aj-category-badge">Pack hébergement</span>' +
          '<span class="aj-status-badge' + (item.available ? '' : ' aj-status-badge--unavailable') + '">' + escapeHtml(item.availabilityLabel) + '</span>' +
          (item.duration ? '<span class="aj-category-badge">' + escapeHtml(item.duration) + '</span>' : '') +
        '</div>' +
        '<h3 class="aj-pack-detail-title">' + escapeHtml(item.title) + '</h3>' +
        '<div class="aj-pack-detail-price"><small>À partir de</small> ' + escapeHtml(price) + '</div>' +
        '<div class="aj-pack-detail-meta">' + escapeHtml(item.location || 'Maroc') + (item.boardLabel ? ' · ' + escapeHtml(item.boardLabel) : '') + '</div>' +
        '<div class="aj-pack-detail-summary">' + escapeHtml(description) + '</div>' +
        (includedHtml ? '<div><strong>Services inclus</strong></div>' + includedHtml : '') +
        '<div class="aj-pack-detail-actions">' +
          '<a class="aj-pack-reserve" href="' + escapeHtml(item.url) + '" target="_blank" rel="noopener">Réserver ce pack</a>' +
          '<button type="button" class="aj-pack-secondary" data-clear-pack>Désélectionner</button>' +
        '</div>' +
      '</div>';

    els.packDetailSection.hidden = false;
  }

  function getPackBySlug(slug) {
    var normalized = String(slug || '').trim();
    if (!normalized) {
      return null;
    }

    var candidate = packItems.find(function (item) {
      return item.slug === normalized;
    });

    if (candidate) {
      return candidate;
    }

    var lower = normalized.toLowerCase();
    return packItems.find(function (item) {
      return String(item.slug || '').toLowerCase() === lower;
    }) || null;
  }

  function clearSelectedPack() {
    state.selectedPackSlug = '';
    renderPackDetail(null);
    renderFeaturedCards();
  }

  function selectPack(slug, options) {
    var item = getPackBySlug(slug);
    var opts = options || {};

    state.selectedPackSlug = item ? item.slug : String(slug || '');
    renderFeaturedCards();

    if (!item) {
      renderPackDetail(null);
      if (!opts.silent) {
        var container = root.querySelector('.aj-hebergement-container') || document.querySelector('main') || document.body;
        if (container && !container.querySelector('.pack-not-found-alert')) {
          var msg = document.createElement('div');
          msg.className = 'pack-not-found-alert';
          msg.innerHTML = 'Pack introuvable ou indisponible.';
          container.prepend(msg);
        }
      }
      return false;
    }

    renderPackDetail(item);

    if (opts.scroll !== false) {
      window.setTimeout(function () {
        if (els.packDetailSection) {
          els.packDetailSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }, 50);
    }

    return true;
  }

  function renderResultCard(item) {
    var image = item.image
      ? '<img src="' + escapeHtml(item.image) + '" alt="' + escapeHtml(item.title) + '" loading="lazy">'
      : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#d9e9ff,#f4f8ff);color:#67809a;font-size:13px;font-weight:600;">Ajinsafro</div>';

    var facts = [];
    if (item.city || item.destination) {
      facts.push(item.city || item.destination);
    }
    if (item.typeLabel || item.category) {
      facts.push(item.typeLabel || item.category);
    }
    if (item.boardLabel) {
      facts.push(item.boardLabel);
    }
    item.amenities.slice(0, 3).forEach(function (amenity) {
      var label = amenityLabels[amenity] || amenity;
      if (label) {
        facts.push(label);
      }
    });

    var factsHtml = facts.length
      ? '<div class="aj-result-facts">' + facts.map(function (fact) {
          return '<span class="aj-result-fact">' + escapeHtml(fact) + '</span>';
        }).join('') + '</div>'
      : '';

    var priceHtml = item.price !== null
      ? '<div class="aj-result-price"><small>A partir de</small><div>' +
          (item.oldPrice ? '<span class="aj-old-price">' + escapeHtml(formatPrice(item.oldPrice)) + '</span>' : '') +
          '<strong>' + escapeHtml(formatPrice(item.price)) + '</strong>' +
        '</div></div>'
      : '<div class="aj-result-price"><strong>Sur demande</strong></div>';

    return '' +
      '<article class="aj-result-card">' +
        '<div class="aj-result-media">' +
          image +
          '<div class="aj-card-badges">' +
            '<span class="aj-category-badge">' + escapeHtml(item.typeLabel || item.category || 'Hebergement') + '</span>' +
            '<span class="aj-status-badge' + (item.available ? '' : ' aj-status-badge--unavailable') + '">' + escapeHtml(item.availabilityLabel) + '</span>' +
          '</div>' +
        '</div>' +
        '<div class="aj-result-body">' +
          '<div class="aj-result-meta">' +
            escapeHtml(item.location || item.address || 'Maroc') +
            ' · ' + renderStars(item.stars) +
            (item.rating ? ' · <span class="aj-rating"><strong>' + item.rating.toFixed(1) + '</strong></span>' : '') +
          '</div>' +
          '<h3><a href="' + escapeHtml(item.url) + '">' + escapeHtml(item.title) + '</a></h3>' +
          '<p class="aj-result-desc">' + escapeHtml(truncateText(item.description, 140)) + '</p>' +
          factsHtml +
          '<div class="aj-result-footer">' +
            priceHtml +
            '<a class="aj-result-btn" href="' + escapeHtml(item.url) + '">Voir l\'hebergement</a>' +
          '</div>' +
        '</div>' +
      '</article>';
  }

  function renderActiveFilterChips() {
    if (!els.activeFilters) {
      return;
    }

    var chips = [];
    if (state.destination) chips.push('Destination: ' + state.destination);
    if (state.nameQuery) chips.push('Recherche: ' + state.nameQuery);
    if (state.type) chips.push('Type: ' + state.type);
    if (state.filterDestination) chips.push(state.filterDestination);
    if (state.filterType) chips.push(state.filterType);
    state.stars.forEach(function (star) {
      chips.push(star + ' etoile' + (star === '1' ? '' : 's'));
    });
    if (state.minPrice) chips.push('Min ' + state.minPrice + ' ' + currency);
    if (state.maxPrice) chips.push('Max ' + state.maxPrice + ' ' + currency);
    if (state.popularOnly) chips.push('Selection Ajinsafro');
    if (state.availableOnly) chips.push('Disponible');
    if (state.promoOnly) chips.push('Promotions');
    state.services.forEach(function (service) {
      chips.push(amenityLabels[service] || service);
    });

    els.activeFilters.innerHTML = chips.map(function (chip) {
      return '<span class="aj-filter-chip">' + escapeHtml(chip) + '<button type="button" data-ajhb-remove="all">×</button></span>';
    }).join('');
  }

  function renderFeaturedCards() {
    var featured = packItems.filter(function (item) { return item.popular; });
    featured.sort(function (a, b) { return (a.order || 99) - (b.order || 99); });

    if (!featured.length) {
      if (els.featuredSection) els.featuredSection.hidden = true;
      els.featuredGrid.innerHTML = '';
      return;
    }

    if (els.featuredSection) els.featuredSection.hidden = false;
    els.featuredGrid.innerHTML = featured.map(renderFeaturedCard).join('');
  }

  function renderCatalog() {
    if (!state.hasSearched) {
      if (els.featuredSection) els.featuredSection.hidden = false;
      if (els.catalogSection) els.catalogSection.hidden = true;
      return;
    }

    if (els.featuredSection) els.featuredSection.hidden = !state.selectedPackSlug;
    if (els.catalogSection) els.catalogSection.hidden = false;

    var results = sortItems(hotelItems.filter(matchesFilters));

    if (els.resultsCount) {
      els.resultsCount.textContent = String(results.length);
    }

    renderActiveFilterChips();

    if (els.resultsGrid) {
      els.resultsGrid.innerHTML = results.map(renderResultCard).join('');
    }

    if (els.emptyState) {
      els.emptyState.hidden = results.length > 0;
    }
  }

  function readDesktopFilters() {
    state.nameQuery = els.filterName ? els.filterName.value.trim() : '';
    state.filterDestination = els.filterDestination ? els.filterDestination.value : '';
    state.filterType = els.filterType ? els.filterType.value : '';
    state.stars = readCheckedValues(els.filterStars);
    state.minPrice = els.filterPriceMin ? els.filterPriceMin.value : '';
    state.maxPrice = els.filterPriceMax ? els.filterPriceMax.value : '';
    state.popularOnly = els.filterPopular ? els.filterPopular.checked : false;
    state.availableOnly = els.filterAvailable ? els.filterAvailable.checked : false;
    state.promoOnly = els.filterPromo ? els.filterPromo.checked : false;
    state.services = readServices(els.servicesGroup);
  }

  function readMobileFilters() {
    state.nameQuery = els.mobileName ? els.mobileName.value.trim() : '';
    state.filterDestination = els.mobileDestination ? els.mobileDestination.value : '';
    state.filterType = els.mobileType ? els.mobileType.value : '';
    state.stars = readCheckedValues(els.mobileStars);
    state.minPrice = els.mobilePriceMin ? els.mobilePriceMin.value : '';
    state.maxPrice = els.mobilePriceMax ? els.mobilePriceMax.value : '';
    state.popularOnly = els.mobilePopular ? els.mobilePopular.checked : false;
    state.availableOnly = els.mobileAvailable ? els.mobileAvailable.checked : false;
    state.promoOnly = els.mobilePromo ? els.mobilePromo.checked : false;
    state.services = els.mobilePanel ? readServices(els.mobilePanel) : [];
    state.services = state.services.filter(function (value) {
      return ['wifi', 'pool', 'parking', 'breakfast', 'air_conditioning'].includes(value);
    });
  }

  function applyHeroSearch() {
    state.hasSearched = true;
    state.destination = els.heroDestination ? els.heroDestination.value.trim() : '';
    state.date = els.heroDate ? els.heroDate.value : '';
    state.type = els.heroType ? els.heroType.value : '';
    state.stars = els.heroStars && els.heroStars.value ? [els.heroStars.value] : [];
    syncAllControls();
    renderCatalog();
  }

  function setView(view) {
    state.view = view === 'grid' ? 'grid' : 'list';
    persistView(state.view);
    updateViewUrl(state.view);
    syncAllControls();
    renderCatalog();
  }

  function applyDesktopFilters() {
    state.hasSearched = true;
    readDesktopFilters();
    syncAllControls();
    renderCatalog();
  }

  function applyMobileFilters() {
    state.hasSearched = true;
    readMobileFilters();
    syncAllControls();
    renderCatalog();
    closeMobileFilters();
  }

  function resetState() {
    state.hasSearched = false;
    state.selectedPackSlug = '';
    state.destination = '';
    state.date = '';
    state.type = '';
    state.stars = [];
    state.nameQuery = '';
    state.filterDestination = '';
    state.filterType = '';
    state.minPrice = '';
    state.maxPrice = '';
    state.popularOnly = false;
    state.availableOnly = false;
    state.promoOnly = false;
    state.services = [];
    state.sort = 'recommended';
    syncAllControls();
    renderPackDetail(null);
    renderCatalog();
  }

  function openMobileFilters() {
    syncMobileControls();
    if (els.mobilePanel) els.mobilePanel.classList.add('is-active');
    if (els.mobileBackdrop) els.mobileBackdrop.classList.add('is-active');
    document.body.classList.add('aj-mobile-filters-open');
  }

  function closeMobileFilters() {
    if (els.mobilePanel) els.mobilePanel.classList.remove('is-active');
    if (els.mobileBackdrop) els.mobileBackdrop.classList.remove('is-active');
    document.body.classList.remove('aj-mobile-filters-open');
  }

  function bindEvents() {
    if (els.searchForm) {
      els.searchForm.addEventListener('submit', function (event) {
        event.preventDefault();
        applyHeroSearch();
      });
    }

    if (els.sortSelect) {
      els.sortSelect.addEventListener('change', function () {
        state.sort = els.sortSelect.value;
        renderCatalog();
      });
    }

    if (els.viewToggle) {
      els.viewToggle.addEventListener('click', function (event) {
        var button = event.target.closest('[data-view]');
        if (!button) {
          return;
        }
        setView(button.getAttribute('data-view') || 'list');
      });
    }

    if (els.desktopFilters) {
      els.desktopFilters.addEventListener('input', function () {
        applyDesktopFilters();
      });
      els.desktopFilters.addEventListener('change', function () {
        applyDesktopFilters();
      });
    }

    if (els.resetFilters) {
      els.resetFilters.addEventListener('click', resetState);
    }

    if (els.emptyReset) {
      els.emptyReset.addEventListener('click', resetState);
    }

    if (els.openMobileFilters) {
      els.openMobileFilters.addEventListener('click', openMobileFilters);
    }

    if (els.closeMobileFilters) {
      els.closeMobileFilters.addEventListener('click', closeMobileFilters);
    }

    if (els.mobileBackdrop) {
      els.mobileBackdrop.addEventListener('click', closeMobileFilters);
    }

    if (els.applyMobileFilters) {
      els.applyMobileFilters.addEventListener('click', applyMobileFilters);
    }

    if (els.resetMobileFilters) {
      els.resetMobileFilters.addEventListener('click', function () {
        resetState();
        closeMobileFilters();
      });
    }

    if (els.activeFilters) {
      els.activeFilters.addEventListener('click', function (event) {
        if (event.target.closest('[data-ajhb-remove]')) {
          resetState();
        }
      });
    }

    root.addEventListener('click', function (event) {
      var openTrigger = event.target.closest('[data-open-pack]');
      if (openTrigger) {
        event.preventDefault();
        selectPack(openTrigger.getAttribute('data-pack-slug') || '', { scroll: true });
        return;
      }

      var clearTrigger = event.target.closest('[data-clear-pack]');
      if (clearTrigger) {
        event.preventDefault();
        clearSelectedPack();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && els.mobilePanel && els.mobilePanel.classList.contains('is-active')) {
        closeMobileFilters();
      }
    });
  }

  initFromUrl();
  syncAllControls();
  renderFeaturedCards();
  renderCatalog();
  bindEvents();
  (function openPackFromUrl() {
    try {
      var params = new URLSearchParams(window.location.search || '');
      var packSlug = params.get('pack');
      if (!packSlug) {
        return;
      }

      setTimeout(function () {
        selectPack(packSlug, { scroll: true });
      }, 500);
    } catch (e) {
      // Ignore errors silently
    }
  })();
})();
