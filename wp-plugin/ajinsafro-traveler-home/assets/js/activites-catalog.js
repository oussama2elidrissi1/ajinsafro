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
    filterName: root.querySelector('#aj-filter-name'),
    filterCategories: root.querySelector('#aj-filter-categories'),
    filterCountry: root.querySelector('#aj-filter-country'),
    filterCity: root.querySelector('#aj-filter-city'),
    filterPriceMin: root.querySelector('#aj-filter-price-min'),
    filterPriceMax: root.querySelector('#aj-filter-price-max'),
    filterDuration: root.querySelector('#aj-filter-duration'),
    filterPromo: root.querySelector('#aj-filter-promo'),
    filterAvailableToday: root.querySelector('#aj-filter-available-today'),
    filterInstantBooking: root.querySelector('#aj-filter-instant-booking'),
    filterWithGuide: root.querySelector('#aj-filter-with-guide'),
    filterTransport: root.querySelector('#aj-filter-transport'),
    resetFilters: root.querySelector('#aj-reset-filters'),
    mobileName: root.querySelector('#aj-mobile-name'),
    mobileCategories: root.querySelector('#aj-mobile-categories'),
    mobileCountry: root.querySelector('#aj-mobile-country'),
    mobileCity: root.querySelector('#aj-mobile-city'),
    mobilePriceMin: root.querySelector('#aj-mobile-price-min'),
    mobilePriceMax: root.querySelector('#aj-mobile-price-max'),
    mobileDuration: root.querySelector('#aj-mobile-duration'),
    mobilePromo: root.querySelector('#aj-mobile-promo'),
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

  if (!rawActivities.length) {
    if (config.isAdmin && els.emptyState && els.activitiesGrid) {
      els.activitiesGrid.innerHTML = '';
      els.emptyState.hidden = false;
      els.emptyState.innerHTML = `
        <h3>Aucune activite disponible</h3>
        <p>${escapeHtml(config.adminEmptyMessage || 'API Laravel connectee mais aucune activite trouvee. Verifiez les seeders.')}</p>
      `;
    }
    return;
  }

  const activities = rawActivities.map(normalizeActivity);
  const countries = Array.from(new Set(activities.map((item) => item.country))).sort(collatorCompare);
  const categories = Array.from(new Set(activities.map((item) => item.category))).sort(collatorCompare);
  const cityMap = buildCityMap(activities);

  const state = {
    country: '',
    city: '',
    date: '',
    categories: [],
    budget: '',
    minPrice: '',
    maxPrice: '',
    duration: '',
    nameQuery: '',
    promoOnly: false,
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
    const safeUrl = resolveNavUrl(item.url, item.slug, 'activity');
    const safeBookingUrl = resolveBookingUrl(item.booking_url || item.bookingUrl, safeUrl);

    return {
      id: Number(item.id || 0),
      title: String(item.title || ''),
      slug: String(item.slug || ''),
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
      shortDescription: String(item.short_description || ''),
      includes,
      availability: String(item.availability || 'Disponible'),
      availableToday: Boolean(item.available_today),
      instantBooking: Boolean(item.instant_booking),
      withGuide: Boolean(item.with_guide),
      transportIncluded: Boolean(item.transport_included),
      badge: String(item.badge || ''),
      url: safeUrl,
      bookingUrl: safeBookingUrl
    };
  }

  function resolveNavUrl(value, slug, kind) {
    const raw = String(value || '').trim();
    if (raw && raw !== '#' && raw.toLowerCase() !== 'javascript:void(0)') {
      return raw;
    }

    const base = kind === 'activity' ? '/activites/' : '/';
    const key = String(slug || '').trim();
    if (key) {
      return kind === 'activity'
        ? `${base.replace(/\/?$/, '/')}activite/${encodeURIComponent(key)}/`
        : `${base}?activity=${encodeURIComponent(key)}`;
    }

    return base;
  }

  function resolveBookingUrl(value, fallbackUrl) {
    const raw = String(value || '').trim();
    if (raw && raw !== '#' && raw.toLowerCase() !== 'javascript:void(0)') {
      return raw;
    }

    return fallbackUrl;
  }

  function buildCityMap(items) {
    const map = {
      '': []
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
    renderCategoryCheckboxes(els.filterCategories, 'aj-filter-cat');
    renderCategoryCheckboxes(els.mobileCategories, 'aj-mobile-cat');

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

  function renderCategoryCheckboxes(container, inputName) {
    if (!container) {
      return;
    }
    const markup = categories.map((cat) => `
      <label class="aj-check">
        <input type="checkbox" name="${inputName}" value="${escapeHtml(cat)}">
        ${escapeHtml(cat)}
      </label>
    `).join('');
    container.innerHTML = markup;
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
    els.heroCategory.value = state.categories[0] || '';
    els.heroDate.value = state.date;
    els.heroBudget.value = state.budget;
    updateCityOptions('hero', state.country, state.city);

    if (els.filterName) els.filterName.value = state.nameQuery;
    if (els.filterCategories) {
      els.filterCategories.querySelectorAll('input').forEach((input) => {
        input.checked = state.categories.includes(input.value);
      });
    }
    els.filterCountry.value = state.country;
    els.filterPriceMin.value = state.minPrice;
    els.filterPriceMax.value = state.maxPrice;
    els.filterDuration.value = state.duration;
    if (els.filterPromo) els.filterPromo.checked = state.promoOnly;
    els.filterAvailableToday.checked = state.availableToday;
    els.filterInstantBooking.checked = state.instantBooking;
    els.filterWithGuide.checked = state.withGuide;
    els.filterTransport.checked = state.transportIncluded;
    els.sortSelect.value = state.sort;
    updateCityOptions('desktop', state.country, state.city);

    syncMobileControls();
  }

  function syncMobileControls() {
    if (els.mobileName) els.mobileName.value = state.nameQuery;
    if (els.mobileCategories) {
      els.mobileCategories.querySelectorAll('input').forEach((input) => {
        input.checked = state.categories.includes(input.value);
      });
    }
    els.mobileCountry.value = state.country;
    els.mobilePriceMin.value = state.minPrice;
    els.mobilePriceMax.value = state.maxPrice;
    els.mobileDuration.value = state.duration;
    if (els.mobilePromo) els.mobilePromo.checked = state.promoOnly;
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
      state.categories = els.heroCategory.value ? [els.heroCategory.value] : [];
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

    if (els.filterCategories) {
      els.filterCategories.addEventListener('change', function () {
        state.categories = Array.from(els.filterCategories.querySelectorAll('input:checked')).map((i) => i.value);
        syncAllControlsFromState();
        renderCatalog();
      });
    }

    if (els.filterName) {
      els.filterName.addEventListener('input', function () {
        state.nameQuery = els.filterName.value;
        syncAllControlsFromState();
        renderCatalog();
      });
    }

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

    if (els.filterPromo) {
      els.filterPromo.addEventListener('change', function () {
        state.promoOnly = els.filterPromo.checked;
        syncAllControlsFromState();
        renderCatalog();
      });
    }

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
      state.nameQuery = els.mobileName ? els.mobileName.value : '';
      state.categories = els.mobileCategories
        ? Array.from(els.mobileCategories.querySelectorAll('input:checked')).map((i) => i.value)
        : [];
      state.country = els.mobileCountry.value;
      state.city = els.mobileCity.value;
      state.minPrice = els.mobilePriceMin.value;
      state.maxPrice = els.mobilePriceMax.value;
      state.duration = els.mobileDuration.value;
      state.promoOnly = els.mobilePromo ? els.mobilePromo.checked : false;
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
    state.categories = [];
    state.budget = '';
    state.minPrice = '';
    state.maxPrice = '';
    state.duration = '';
    state.nameQuery = '';
    state.promoOnly = false;
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

    if (state.categories.length && !state.categories.includes(activity.category)) {
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

    if (state.nameQuery) {
      const text = `${activity.title} ${activity.city} ${activity.country} ${activity.shortDescription}`.toLowerCase();
      if (!text.includes(state.nameQuery.toLowerCase())) {
        return false;
      }
    }

    if (state.promoOnly && !activity.badge) {
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
        <a class="aj-featured-visual aj-activity-visual-link" href="${escapeHtml(activity.url)}">
          <img src="${escapeHtml(activity.image)}" alt="${escapeHtml(activity.title)}" loading="lazy">
          <span class="aj-badge">${escapeHtml(activity.badge || 'A la une')}</span>
          <div class="aj-featured-price">
            <small>A partir de</small>
            ${escapeHtml(formatPrice(activity.price))}
          </div>
        </a>
        <div class="aj-featured-content">
          <div class="aj-inline-meta">
            <span>${escapeHtml(activity.city)}</span>
            <span>${escapeHtml(activity.durationLabel)}</span>
          </div>
          <h3><a class="aj-featured-title-link" href="${escapeHtml(activity.url)}">${escapeHtml(activity.title)}</a></h3>
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
        <a class="aj-card-media aj-activity-visual-link" href="${escapeHtml(activity.url)}">
          <img src="${escapeHtml(activity.image)}" alt="${escapeHtml(activity.title)}" loading="lazy">
          <div class="aj-card-badges">
            <span class="aj-category-badge">${escapeHtml(activity.category)}</span>
            <span class="aj-status-badge">${escapeHtml(activity.availability)}</span>
          </div>
        </a>
        <div class="aj-card-body">
          <div class="aj-card-meta">
            <span class="aj-card-location">${escapeHtml(activity.country)} · ${escapeHtml(activity.city)}</span>
            <span>${escapeHtml(activity.durationLabel)}</span>
            <span>${activity.rating.toFixed(1)} / 5</span>
          </div>
          <h3><a class="aj-featured-title-link" href="${escapeHtml(activity.url)}">${escapeHtml(activity.title)}</a></h3>
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

    if (state.nameQuery) chips.push(`Recherche: ${state.nameQuery}`);
    if (state.country) chips.push(state.country);
    if (state.city) chips.push(state.city);
    state.categories.forEach((c) => chips.push(c));
    if (state.date) chips.push(formatDateChip(state.date));
    if (state.minPrice) chips.push(`Min ${state.minPrice} DH`);
    if (state.maxPrice) chips.push(`Max ${state.maxPrice} DH`);
    if (state.duration) chips.push(durationLabel(state.duration));
    if (state.promoOnly) chips.push('Promotions');
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
