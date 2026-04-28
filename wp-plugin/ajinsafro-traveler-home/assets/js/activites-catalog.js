(function () {
  'use strict';

  const root = document.getElementById('aj-activities-static');
  if (!root) {
    return;
  }

  const config = typeof window.ajthActivitiesConfig === 'object' && window.ajthActivitiesConfig
    ? window.ajthActivitiesConfig
    : {};

  const rawActivities = Array.isArray(config.activities) ? config.activities : [];
  if (!rawActivities.length) {
    return;
  }

  const els = {
    featuredGrid: root.querySelector('#aj-featured-grid'),
    activitiesGrid: root.querySelector('#aj-activities-grid'),
    resultsCount: root.querySelector('#aj-results-count'),
    activeFilters: root.querySelector('#aj-active-filters'),
    emptyState: root.querySelector('#aj-empty-state'),
    emptyReset: root.querySelector('#aj-empty-reset'),
    sortSelect: root.querySelector('#aj-sort-select'),
    searchForm: root.querySelector('#aj-activity-search-form'),
    heroCountry: root.querySelector('#aj-hero-country'),
    heroCity: root.querySelector('#aj-hero-city'),
    heroDate: root.querySelector('#aj-hero-date'),
    heroCategory: root.querySelector('#aj-hero-category'),
    heroBudget: root.querySelector('#aj-hero-budget'),
    filterCountry: root.querySelector('#aj-filter-country'),
    filterCity: root.querySelector('#aj-filter-city'),
    filterCategory: root.querySelector('#aj-filter-category'),
    filterPriceMin: root.querySelector('#aj-filter-price-min'),
    filterPriceMax: root.querySelector('#aj-filter-price-max'),
    filterDuration: root.querySelector('#aj-filter-duration'),
    filterAvailableToday: root.querySelector('#aj-filter-available-today'),
    filterInstantBooking: root.querySelector('#aj-filter-instant-booking'),
    filterWithGuide: root.querySelector('#aj-filter-with-guide'),
    filterTransport: root.querySelector('#aj-filter-transport'),
    resetFilters: root.querySelector('#aj-reset-filters'),
    mobileCountry: root.querySelector('#aj-mobile-country'),
    mobileCity: root.querySelector('#aj-mobile-city'),
    mobileCategory: root.querySelector('#aj-mobile-category'),
    mobilePriceMin: root.querySelector('#aj-mobile-price-min'),
    mobilePriceMax: root.querySelector('#aj-mobile-price-max'),
    mobileDuration: root.querySelector('#aj-mobile-duration'),
    mobileAvailableToday: root.querySelector('#aj-mobile-available-today'),
    mobileInstantBooking: root.querySelector('#aj-mobile-instant-booking'),
    mobileWithGuide: root.querySelector('#aj-mobile-with-guide'),
    mobileTransport: root.querySelector('#aj-mobile-transport'),
    openMobileFilters: root.querySelector('#aj-open-mobile-filters'),
    closeMobileFilters: root.querySelector('#aj-close-mobile-filters'),
    applyMobileFilters: root.querySelector('#aj-apply-mobile-filters'),
    resetMobileFilters: root.querySelector('#aj-reset-mobile-filters'),
    mobilePanel: root.querySelector('#aj-mobile-panel'),
    mobileBackdrop: root.querySelector('#aj-mobile-backdrop')
  };

  const activities = rawActivities.map(normalizeActivity);
  const countries = ['Maroc', 'Espagne', 'Turquie', 'France', 'Emirats Arabes Unis', 'Italie']
    .filter((country, index, array) => array.indexOf(country) === index);
  const categories = Array.from(new Set(activities.map((item) => item.category))).sort(collatorCompare);
  const cityMap = buildCityMap(activities);

  const state = {
    country: '',
    city: '',
    date: '',
    category: '',
    budget: '',
    minPrice: '',
    maxPrice: '',
    duration: '',
    availableToday: false,
    instantBooking: false,
    withGuide: false,
    transportIncluded: false,
    sort: 'featured'
  };

  hydrateStaticOptions();
  syncAllControlsFromState();
  renderFeaturedCards(activities.filter((item) => item.featured).slice(0, 4));
  renderCatalog();
  bindEvents();

  function normalizeActivity(item) {
    const includes = Array.isArray(item.includes) ? item.includes : [];

    return {
      id: Number(item.id || 0),
      title: String(item.title || ''),
      country: String(item.country || ''),
      city: String(item.city || ''),
      category: String(item.category || ''),
      durationHours: Number(item.duration_hours || 0),
      durationLabel: String(item.duration_label || ''),
      price: Number(item.price || 0),
      image: String(item.image || ''),
      featured: Boolean(item.featured),
      rating: Number(item.rating || 0),
      reviews: Number(item.reviews || 0),
      includes,
      availability: String(item.availability || 'Disponible'),
      availableToday: Boolean(item.available_today),
      instantBooking: Boolean(item.instant_booking),
      withGuide: Boolean(item.with_guide),
      transportIncluded: Boolean(item.transport_included),
      badge: String(item.badge || ''),
      url: String(item.url || '#'),
      bookingUrl: String(item.booking_url || item.url || '#')
    };
  }

  function buildCityMap(items) {
    const map = {
      '': [],
      'Maroc': ['Marrakech', 'Dakhla', 'Tanger', 'Casablanca', 'Agadir', 'Fes', 'Chefchaouen'],
      'Turquie': ['Istanbul', 'Antalya', 'Cappadoce'],
      'Espagne': ['Barcelone', 'Madrid', 'Seville'],
      'France': ['Paris', 'Nice', 'Lyon'],
      'Emirats Arabes Unis': ['Dubai', 'Abu Dhabi'],
      'Italie': ['Rome', 'Milan', 'Venise']
    };

    items.forEach((item) => {
      if (!map[item.country]) {
        map[item.country] = [];
      }
      if (!map[item.country].includes(item.city)) {
        map[item.country].push(item.city);
      }
    });

    Object.keys(map).forEach((key) => {
      map[key] = map[key].sort(collatorCompare);
    });

    return map;
  }

  function collatorCompare(a, b) {
    return String(a).localeCompare(String(b), 'fr', { sensitivity: 'base' });
  }

  function hydrateStaticOptions() {
    fillSelect(els.heroCountry, countries, 'Tous les pays');
    fillSelect(els.filterCountry, countries, 'Tous les pays');
    fillSelect(els.mobileCountry, countries, 'Tous les pays');

    fillSelect(els.heroCategory, categories, 'Toutes les categories');
    fillSelect(els.filterCategory, categories, 'Toutes les categories');
    fillSelect(els.mobileCategory, categories, 'Toutes les categories');

    updateCityOptions('hero', state.country, state.city);
    updateCityOptions('desktop', state.country, state.city);
    updateCityOptions('mobile', state.country, state.city);
  }

  function fillSelect(select, values, placeholder) {
    if (!select) {
      return;
    }

    const options = [`<option value="">${escapeHtml(placeholder)}</option>`]
      .concat(values.map((value) => `<option value="${escapeHtml(value)}">${escapeHtml(value)}</option>`));

    select.innerHTML = options.join('');
  }

  function updateCityOptions(scope, country, selectedCity) {
    const select = scope === 'hero'
      ? els.heroCity
      : scope === 'desktop'
        ? els.filterCity
        : els.mobileCity;

    if (!select) {
      return;
    }

    const values = country && cityMap[country] ? cityMap[country] : [];
    const label = country ? 'Toutes les villes' : 'Toutes les villes';
    fillSelect(select, values, label);
    select.value = values.includes(selectedCity) ? selectedCity : '';
  }

  function syncAllControlsFromState() {
    els.heroCountry.value = state.country;
    els.heroCategory.value = state.category;
    els.heroDate.value = state.date;
    els.heroBudget.value = state.budget;
    updateCityOptions('hero', state.country, state.city);

    els.filterCountry.value = state.country;
    els.filterCategory.value = state.category;
    els.filterPriceMin.value = state.minPrice;
    els.filterPriceMax.value = state.maxPrice;
    els.filterDuration.value = state.duration;
    els.filterAvailableToday.checked = state.availableToday;
    els.filterInstantBooking.checked = state.instantBooking;
    els.filterWithGuide.checked = state.withGuide;
    els.filterTransport.checked = state.transportIncluded;
    els.sortSelect.value = state.sort;
    updateCityOptions('desktop', state.country, state.city);

    syncMobileControls();
  }

  function syncMobileControls() {
    els.mobileCountry.value = state.country;
    els.mobileCategory.value = state.category;
    els.mobilePriceMin.value = state.minPrice;
    els.mobilePriceMax.value = state.maxPrice;
    els.mobileDuration.value = state.duration;
    els.mobileAvailableToday.checked = state.availableToday;
    els.mobileInstantBooking.checked = state.instantBooking;
    els.mobileWithGuide.checked = state.withGuide;
    els.mobileTransport.checked = state.transportIncluded;
    updateCityOptions('mobile', state.country, state.city);
  }

  function bindEvents() {
    els.searchForm.addEventListener('submit', function (event) {
      event.preventDefault();
      state.country = els.heroCountry.value;
      state.city = els.heroCity.value;
      state.date = els.heroDate.value;
      state.category = els.heroCategory.value;
      state.budget = els.heroBudget.value;
      applyBudgetToState(state.budget);
      syncAllControlsFromState();
      renderCatalog();
    });

    els.heroCountry.addEventListener('change', function () {
      state.country = els.heroCountry.value;
      state.city = '';
      updateCityOptions('hero', state.country, state.city);
    });

    els.filterCountry.addEventListener('change', function () {
      state.country = els.filterCountry.value;
      state.city = '';
      syncAllControlsFromState();
      renderCatalog();
    });

    els.filterCity.addEventListener('change', function () {
      state.city = els.filterCity.value;
      syncAllControlsFromState();
      renderCatalog();
    });

    els.filterCategory.addEventListener('change', function () {
      state.category = els.filterCategory.value;
      syncAllControlsFromState();
      renderCatalog();
    });

    els.filterPriceMin.addEventListener('input', function () {
      state.minPrice = els.filterPriceMin.value;
      state.budget = '';
      syncAllControlsFromState();
      renderCatalog();
    });

    els.filterPriceMax.addEventListener('input', function () {
      state.maxPrice = els.filterPriceMax.value;
      state.budget = '';
      syncAllControlsFromState();
      renderCatalog();
    });

    els.filterDuration.addEventListener('change', function () {
      state.duration = els.filterDuration.value;
      syncAllControlsFromState();
      renderCatalog();
    });

    els.filterAvailableToday.addEventListener('change', function () {
      state.availableToday = els.filterAvailableToday.checked;
      syncAllControlsFromState();
      renderCatalog();
    });

    els.filterInstantBooking.addEventListener('change', function () {
      state.instantBooking = els.filterInstantBooking.checked;
      syncAllControlsFromState();
      renderCatalog();
    });

    els.filterWithGuide.addEventListener('change', function () {
      state.withGuide = els.filterWithGuide.checked;
      syncAllControlsFromState();
      renderCatalog();
    });

    els.filterTransport.addEventListener('change', function () {
      state.transportIncluded = els.filterTransport.checked;
      syncAllControlsFromState();
      renderCatalog();
    });

    els.sortSelect.addEventListener('change', function () {
      state.sort = els.sortSelect.value;
      renderCatalog();
    });

    els.resetFilters.addEventListener('click', resetState);
    els.emptyReset.addEventListener('click', resetState);

    els.openMobileFilters.addEventListener('click', openMobileFilters);
    els.closeMobileFilters.addEventListener('click', closeMobileFilters);
    els.mobileBackdrop.addEventListener('click', closeMobileFilters);

    els.mobileCountry.addEventListener('change', function () {
      updateCityOptions('mobile', els.mobileCountry.value, '');
    });

    els.applyMobileFilters.addEventListener('click', function () {
      state.country = els.mobileCountry.value;
      state.city = els.mobileCity.value;
      state.category = els.mobileCategory.value;
      state.minPrice = els.mobilePriceMin.value;
      state.maxPrice = els.mobilePriceMax.value;
      state.duration = els.mobileDuration.value;
      state.availableToday = els.mobileAvailableToday.checked;
      state.instantBooking = els.mobileInstantBooking.checked;
      state.withGuide = els.mobileWithGuide.checked;
      state.transportIncluded = els.mobileTransport.checked;
      state.budget = '';
      syncAllControlsFromState();
      renderCatalog();
      closeMobileFilters();
    });

    els.resetMobileFilters.addEventListener('click', function () {
      resetState();
      closeMobileFilters();
    });
  }

  function applyBudgetToState(budget) {
    if (!budget) {
      state.minPrice = '';
      state.maxPrice = '';
      return;
    }

    const parts = budget.split('-');
    state.minPrice = parts[0] || '';
    state.maxPrice = parts[1] || '';
  }

  function resetState() {
    state.country = '';
    state.city = '';
    state.date = '';
    state.category = '';
    state.budget = '';
    state.minPrice = '';
    state.maxPrice = '';
    state.duration = '';
    state.availableToday = false;
    state.instantBooking = false;
    state.withGuide = false;
    state.transportIncluded = false;
    state.sort = 'featured';
    syncAllControlsFromState();
    renderCatalog();
  }

  function openMobileFilters() {
    syncMobileControls();
    els.mobilePanel.classList.add('is-active');
    els.mobileBackdrop.classList.add('is-active');
    document.body.classList.add('aj-mobile-filters-open');
  }

  function closeMobileFilters() {
    els.mobilePanel.classList.remove('is-active');
    els.mobileBackdrop.classList.remove('is-active');
    document.body.classList.remove('aj-mobile-filters-open');
  }

  function renderFeaturedCards(items) {
    const markup = items.map(renderFeaturedCard).join('');
    els.featuredGrid.innerHTML = markup;
  }

  function renderCatalog() {
    const filtered = activities.filter(matchesFilters);
    const sorted = sortActivities(filtered);

    els.resultsCount.textContent = String(sorted.length);
    els.activeFilters.innerHTML = renderActiveFilterChips();
    els.activitiesGrid.innerHTML = sorted.map(renderActivityCard).join('');
    els.emptyState.hidden = sorted.length > 0;
  }

  function matchesFilters(activity) {
    if (state.country && activity.country !== state.country) {
      return false;
    }

    if (state.city && activity.city !== state.city) {
      return false;
    }

    if (state.category && activity.category !== state.category) {
      return false;
    }

    if (state.minPrice && activity.price < Number(state.minPrice)) {
      return false;
    }

    if (state.maxPrice && activity.price > Number(state.maxPrice)) {
      return false;
    }

    if (state.duration && !matchesDurationBucket(activity.durationHours, state.duration)) {
      return false;
    }

    if (state.availableToday && !activity.availableToday) {
      return false;
    }

    if (state.instantBooking && !activity.instantBooking) {
      return false;
    }

    if (state.withGuide && !activity.withGuide) {
      return false;
    }

    if (state.transportIncluded && !activity.transportIncluded) {
      return false;
    }

    return true;
  }

  function matchesDurationBucket(hours, bucket) {
    if (bucket === 'lt2') {
      return hours <= 2;
    }
    if (bucket === 'half') {
      return hours > 2 && hours <= 6;
    }
    if (bucket === 'full') {
      return hours > 6 && hours < 24;
    }
    if (bucket === 'multi') {
      return hours >= 24;
    }
    return true;
  }

  function sortActivities(items) {
    const sorted = items.slice();

    if (state.sort === 'featured') {
      sorted.sort((a, b) => Number(b.featured) - Number(a.featured) || b.rating - a.rating || a.price - b.price);
    }
    if (state.sort === 'price-asc') {
      sorted.sort((a, b) => a.price - b.price);
    }
    if (state.sort === 'price-desc') {
      sorted.sort((a, b) => b.price - a.price);
    }
    if (state.sort === 'rating-desc') {
      sorted.sort((a, b) => b.rating - a.rating);
    }
    if (state.sort === 'duration-asc') {
      sorted.sort((a, b) => a.durationHours - b.durationHours);
    }

    return sorted;
  }

  function renderFeaturedCard(activity) {
    return `
      <article class="aj-featured-card">
        <div class="aj-featured-visual">
          <img src="${escapeHtml(activity.image)}" alt="${escapeHtml(activity.title)}" loading="lazy">
          <span class="aj-badge">${escapeHtml(activity.badge || 'A la une')}</span>
          <div class="aj-featured-price">
            <small>A partir de</small>
            ${escapeHtml(formatPrice(activity.price))}
          </div>
        </div>
        <div class="aj-featured-content">
          <div class="aj-inline-meta">
            <span>${escapeHtml(activity.city)}</span>
            <span>${escapeHtml(activity.durationLabel)}</span>
          </div>
          <h3>${escapeHtml(activity.title)}</h3>
          <div class="aj-rating">
            <strong>${activity.rating.toFixed(1)}</strong>
            <span>${escapeHtml(activity.reviews.toLocaleString('fr-FR'))} avis</span>
          </div>
          <a class="aj-featured-link" href="${escapeHtml(activity.url)}">Voir l activite</a>
        </div>
      </article>
    `;
  }

  function renderActivityCard(activity) {
    return `
      <article class="aj-activity-card">
        <div class="aj-card-media">
          <img src="${escapeHtml(activity.image)}" alt="${escapeHtml(activity.title)}" loading="lazy">
          <div class="aj-card-badges">
            <span class="aj-category-badge">${escapeHtml(activity.category)}</span>
            <span class="aj-status-badge">${escapeHtml(activity.availability)}</span>
          </div>
        </div>
        <div class="aj-card-body">
          <div class="aj-card-meta">
            <span class="aj-card-location">${escapeHtml(activity.country)} · ${escapeHtml(activity.city)}</span>
            <span>${escapeHtml(activity.durationLabel)}</span>
            <span>${activity.rating.toFixed(1)} / 5</span>
          </div>
          <h3>${escapeHtml(activity.title)}</h3>
          <div class="aj-card-facts">
            ${activity.includes.slice(0, 4).map((item) => `<span class="aj-card-fact">${escapeHtml(item)}</span>`).join('')}
          </div>
          <div class="aj-card-footer">
            <div class="aj-card-price">
              <small>A partir de</small>
              <strong>${escapeHtml(formatPrice(activity.price))}</strong>
            </div>
            <div class="aj-card-actions">
              <a class="aj-card-primary" href="${escapeHtml(activity.url)}">Voir l activite</a>
              <a class="aj-card-secondary" href="${escapeHtml(activity.bookingUrl)}">Reserver</a>
            </div>
          </div>
        </div>
      </article>
    `;
  }

  function renderActiveFilterChips() {
    const chips = [];

    if (state.country) chips.push(state.country);
    if (state.city) chips.push(state.city);
    if (state.category) chips.push(state.category);
    if (state.date) chips.push(formatDateChip(state.date));
    if (state.minPrice) chips.push(`Min ${state.minPrice} DH`);
    if (state.maxPrice) chips.push(`Max ${state.maxPrice} DH`);
    if (state.duration) chips.push(durationLabel(state.duration));
    if (state.availableToday) chips.push('Disponible aujourd hui');
    if (state.instantBooking) chips.push('Reservation instantanee');
    if (state.withGuide) chips.push('Avec guide');
    if (state.transportIncluded) chips.push('Transport inclus');

    return chips.map((chip) => `<span class="aj-filter-chip">${escapeHtml(chip)}</span>`).join('');
  }

  function durationLabel(bucket) {
    if (bucket === 'lt2') return 'Moins de 2h';
    if (bucket === 'half') return 'Demi-journee';
    if (bucket === 'full') return 'Journee complete';
    if (bucket === 'multi') return '2 jours et plus';
    return '';
  }

  function formatDateChip(value) {
    const parts = value.split('-');
    if (parts.length !== 3) {
      return value;
    }
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
  }

  function formatPrice(value) {
    return `${Number(value).toLocaleString('fr-FR')} DH`;
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
})();
