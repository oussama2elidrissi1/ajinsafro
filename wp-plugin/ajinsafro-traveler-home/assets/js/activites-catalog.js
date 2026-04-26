(function () {
  'use strict';

  const root = document.getElementById('aj-activities-static');
  if (!root) {
    return;
  }

  const config = typeof window.ajthActivitiesConfig === 'object' && window.ajthActivitiesConfig
    ? window.ajthActivitiesConfig
    : {};

  const strings = config.strings || {};
  const activities = Array.isArray(config.activities) ? config.activities.map(normalizeActivity) : [];

  const baseLabels = {
    pickup: 'Prise en charge incluse',
    free_cancel: 'Annulation gratuite',
    instant: 'Confirmation instantanee',
    fr: 'Francais',
    en: 'Anglais',
    ar: 'Arabe',
    es: 'Espagnol'
  };

  const desktopFilters = root.querySelector('#ajas-filters-desktop');
  const mobileFilters = root.querySelector('#ajas-filters-mobile');
  const activityList = root.querySelector('#ajas-activity-list');
  const resultCount = root.querySelector('#ajas-result-count');
  const sortSelect = root.querySelector('#ajas-sort-select');
  const activeChips = root.querySelector('#ajas-active-chips');
  const emptyState = root.querySelector('#ajas-empty-state');
  const destinationInput = root.querySelector('#ajas-destination-input');
  const categoryTop = root.querySelector('#ajas-category-top');
  const searchForm = root.querySelector('#ajas-main-search-form');
  const drawer = root.querySelector('#ajas-drawer');
  const drawerBg = root.querySelector('#ajas-drawer-bg');
  const openDrawerButton = root.querySelector('#ajas-open-drawer');
  const closeDrawerButton = root.querySelector('#ajas-close-drawer');
  const applyMobileButton = root.querySelector('#ajas-apply-mobile');

  if (!desktopFilters || !mobileFilters || !activityList || !resultCount || !sortSelect || !activeChips || !emptyState || !destinationInput || !categoryTop || !searchForm || !drawer || !drawerBg || !openDrawerButton || !closeDrawerButton || !applyMobileButton) {
    return;
  }

  const categoryOptions = Array.from(new Set(activities.map((activity) => activity.category).filter(Boolean)));
  const languageOptions = Array.from(new Set(activities.flatMap((activity) => activity.languages).filter(Boolean)));
  const featureOptions = Array.from(new Set(activities.flatMap((activity) => activity.features).filter(Boolean)));

  categoryTop.innerHTML = `<option value="">Toutes</option>${categoryOptions.map((value) => `<option value="${escapeHtml(value)}">${escapeHtml(labelForCategory(value))}</option>`).join('')}`;

  const filterMarkup = buildFilterMarkup();
  desktopFilters.innerHTML = filterMarkup;
  mobileFilters.innerHTML = filterMarkup;

  function normalizeActivity(item) {
    const activity = item && typeof item === 'object' ? item : {};
    return {
      id: Number(activity.id || 0),
      title: String(activity.title || activity.name || ''),
      city: String(activity.city || activity.location || ''),
      location: String(activity.location || activity.city || ''),
      category: String(activity.category || 'activite'),
      categoryLabel: String(activity.category_label || activity.category || strings.activity || 'Activite'),
      image: String(activity.image || activity.image_url || ''),
      rating: activity.rating !== null && activity.rating !== undefined && activity.rating !== '' ? Number(activity.rating) : null,
      reviews: Number(activity.reviews || 0),
      duration: Number(activity.duration || 0),
      durationLabel: String(activity.duration_label || ''),
      price: activity.price !== null && activity.price !== undefined && activity.price !== '' ? Number(activity.price) : null,
      oldPrice: activity.oldPrice !== null && activity.oldPrice !== undefined && activity.oldPrice !== '' ? Number(activity.oldPrice) : null,
      discount: Number(activity.discount || 0),
      badges: Array.isArray(activity.badges) ? activity.badges : [],
      languages: Array.isArray(activity.languages) ? activity.languages : [],
      features: Array.isArray(activity.features) ? activity.features : [],
      description: String(activity.description || ''),
      url: String(activity.url || '#'),
      popular: !!activity.popular
    };
  }

  function buildFilterMarkup() {
    return `
      <div class="filter-group">
        <h3>Rechercher par nom</h3>
        <input class="side-search" id="ajas-name-filter" type="text" placeholder="ex: desert, quad, Marrakech...">
      </div>

      <div class="filter-group">
        <h3>Categorie</h3>
        <div class="filter-list">
          ${categoryOptions.map((value) => `<label class="filter-row"><input type="checkbox" name="category" value="${escapeHtml(value)}"> ${escapeHtml(labelForCategory(value))}</label>`).join('')}
        </div>
      </div>

      <div class="filter-group">
        <h3>Prix</h3>
        <div class="price-grid">
          <input id="ajas-min-price" type="number" placeholder="Min DH">
          <input id="ajas-max-price" type="number" placeholder="Max DH">
        </div>
      </div>

      <div class="filter-group">
        <h3>Duree</h3>
        <div class="filter-list">
          <label class="filter-row"><input type="radio" name="duration" value="" checked> Toutes</label>
          <label class="filter-row"><input type="radio" name="duration" value="3"> Jusqu'a 3 heures</label>
          <label class="filter-row"><input type="radio" name="duration" value="6"> Jusqu'a 6 heures</label>
          <label class="filter-row"><input type="radio" name="duration" value="8"> Journee complete</label>
          <label class="filter-row"><input type="radio" name="duration" value="24"> 1 jour et plus</label>
        </div>
      </div>

      <div class="filter-group">
        <h3>Note client</h3>
        <div class="filter-list">
          <label class="filter-row"><input type="radio" name="rating" value="" checked> Toutes les notes</label>
          <label class="filter-row"><input type="radio" name="rating" value="4.5"> 4.5 et plus</label>
          <label class="filter-row"><input type="radio" name="rating" value="4.7"> 4.7 et plus</label>
          <label class="filter-row"><input type="radio" name="rating" value="4.9"> 4.9 et plus</label>
        </div>
      </div>

      ${featureOptions.length ? `
      <div class="filter-group">
        <h3>Options</h3>
        <div class="filter-list">
          ${featureOptions.map((value) => `<label class="filter-row"><input type="checkbox" name="feature" value="${escapeHtml(value)}"> ${escapeHtml(baseLabels[value] || value)}</label>`).join('')}
          <label class="filter-row"><input type="checkbox" id="ajas-discount-only"> Promotions uniquement</label>
        </div>
      </div>` : `
      <div class="filter-group">
        <h3>Options</h3>
        <div class="filter-list">
          <label class="filter-row"><input type="checkbox" id="ajas-discount-only"> Promotions uniquement</label>
        </div>
      </div>`}

      ${languageOptions.length ? `
      <div class="filter-group">
        <h3>Langue du guide</h3>
        <div class="filter-list">
          ${languageOptions.map((value) => `<label class="filter-row"><input type="checkbox" name="language" value="${escapeHtml(value)}"> ${escapeHtml(baseLabels[value] || value)}</label>`).join('')}
        </div>
      </div>` : ''}
    `;
  }

  function labelForCategory(key) {
    const match = activities.find((activity) => activity.category === key && activity.categoryLabel);
    return match ? match.categoryLabel : (strings.activity || 'Activite');
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatPrice(value) {
    if (value === null || value === undefined || value === '' || Number.isNaN(Number(value))) {
      return 'Sur demande';
    }
    return `${Number(value).toLocaleString('fr-FR')} ${config.currency || 'DH'}`;
  }

  function durationText(hours) {
    if (!hours) return 'Duree sur demande';
    if (hours < 24) return `${hours} h`;
    if (hours === 24) return '1 jour';
    return `${Math.round(hours / 24)} jours`;
  }

  function readFilters(container) {
    return {
      q: (container.querySelector('#ajas-name-filter')?.value || '').trim().toLowerCase(),
      destination: (destinationInput.value || '').trim().toLowerCase(),
      categoryTop: categoryTop.value,
      categories: Array.from(container.querySelectorAll('input[name="category"]:checked')).map((input) => input.value),
      minPrice: Number(container.querySelector('#ajas-min-price')?.value || 0),
      maxPrice: Number(container.querySelector('#ajas-max-price')?.value || 0),
      duration: Number(container.querySelector('input[name="duration"]:checked')?.value || 0),
      rating: Number(container.querySelector('input[name="rating"]:checked')?.value || 0),
      features: Array.from(container.querySelectorAll('input[name="feature"]:checked')).map((input) => input.value),
      languages: Array.from(container.querySelectorAll('input[name="language"]:checked')).map((input) => input.value),
      discountOnly: Boolean(container.querySelector('#ajas-discount-only')?.checked)
    };
  }

  function matches(activity, filters) {
    const haystack = `${activity.title} ${activity.city} ${activity.location} ${activity.description} ${activity.categoryLabel}`.toLowerCase();

    if (filters.destination && !haystack.includes(filters.destination)) return false;
    if (filters.q && !haystack.includes(filters.q)) return false;
    if (filters.categoryTop && activity.category !== filters.categoryTop) return false;
    if (filters.categories.length && !filters.categories.includes(activity.category)) return false;
    if (filters.minPrice && (activity.price === null || activity.price < filters.minPrice)) return false;
    if (filters.maxPrice && (activity.price === null || activity.price > filters.maxPrice)) return false;
    if (filters.duration) {
      if (filters.duration < 24 && activity.duration > filters.duration) return false;
      if (filters.duration >= 24 && activity.duration < 24) return false;
    }
    if (filters.rating && (activity.rating === null || activity.rating < filters.rating)) return false;
    if (filters.features.length && !filters.features.every((item) => activity.features.includes(item))) return false;
    if (filters.languages.length && !filters.languages.every((item) => activity.languages.includes(item))) return false;
    if (filters.discountOnly && !activity.discount) return false;

    return true;
  }

  function sortItems(items) {
    const mode = sortSelect.value;
    const sorted = [...items];

    if (mode === 'recommended') sorted.sort((a, b) => (Number(!!b.popular) - Number(!!a.popular)) || ((b.rating || 0) - (a.rating || 0)) || ((a.price || 0) - (b.price || 0)));
    if (mode === 'price-asc') sorted.sort((a, b) => (a.price || 0) - (b.price || 0));
    if (mode === 'price-desc') sorted.sort((a, b) => (b.price || 0) - (a.price || 0));
    if (mode === 'rating-desc') sorted.sort((a, b) => (b.rating || 0) - (a.rating || 0));
    if (mode === 'duration-asc') sorted.sort((a, b) => (a.duration || 0) - (b.duration || 0));
    if (mode === 'discount-desc') sorted.sort((a, b) => b.discount - a.discount);

    return sorted;
  }

  function renderCard(activity, index) {
    const featureText = activity.features.map((key) => baseLabels[key] || key).filter(Boolean).slice(0, 3).join(' · ');
    const languageText = activity.languages.map((key) => baseLabels[key] || key).filter(Boolean).join(', ');
    const badgeMarkup = activity.badges[0] ? `<span class="activity-badge">${escapeHtml(activity.badges[0])}</span>` : (activity.popular ? `<span class="activity-badge">${strings.recommended || 'Recommande'}</span>` : '');
    const oldPriceMarkup = activity.oldPrice ? `<span class="old-price">${formatPrice(activity.oldPrice)}</span>` : '';
    const availabilityMarkup = activity.discount
      ? `<span class="availability">-${activity.discount}% aujourd'hui</span>`
      : `<span class="availability">${strings.available || 'Disponible'}</span>`;
    const promoMarkup = index > 0 && index % 4 === 0
      ? '<div class="promo-strip">OFFRES AJINSAFRO · Reservez votre prochaine activite</div>'
      : '';
    const ratingMarkup = activity.rating !== null
      ? `<span class="score">${activity.rating.toFixed(1)}</span><span class="reviews">${activity.reviews > 0 ? `(${activity.reviews.toLocaleString('fr-FR')} avis)` : `(${escapeHtml(activity.categoryLabel)})`}</span>`
      : `<span class="score">Ajinsafro</span><span class="reviews">(${escapeHtml(activity.categoryLabel)})</span>`;

    return `${promoMarkup}
      <article class="activity-card">
        <div class="activity-image">
          <img src="${activity.image}" alt="${escapeHtml(activity.title)}" loading="lazy">
          <button class="heart" type="button" aria-label="Ajouter aux favoris">?</button>
          ${badgeMarkup}
        </div>
        <div class="activity-content">
          <div class="activity-kind">${escapeHtml(activity.city || 'Maroc')} · ${escapeHtml(activity.categoryLabel || strings.activity || 'Activite')}</div>
          <h3>${escapeHtml(activity.title)}</h3>
          <div class="rating-line">
            <span class="stars">?????</span>
            ${ratingMarkup}
          </div>
          <div class="details">
            <span>Duree : ${escapeHtml(activity.durationLabel || durationText(activity.duration))}</span>
            <span>Guide : ${escapeHtml(languageText || 'Sur demande')}</span>
            <span>${escapeHtml(featureText || activity.location || 'Information disponible sur la fiche')}</span>
          </div>
          <div class="green-note">${strings.support_note || 'Reservation simple · Support Ajinsafro'}</div>
        </div>
        <aside class="activity-side">
          ${availabilityMarkup}
          <div class="price-block">
            <small>${strings.from_price || 'A partir de'}</small>
            <div>${oldPriceMarkup}<span class="price">${formatPrice(activity.price)}</span></div>
            <div class="price-sub">${strings.per_person || 'par personne'}</div>
          </div>
          <a class="book-btn" href="${activity.url}">${strings.view_offer || "Voir l'offre"}</a>
        </aside>
      </article>`;
  }

  function renderChips(filters) {
    const chips = [];
    if (filters.destination) chips.push(`Recherche: ${filters.destination}`);
    if (filters.q) chips.push(`Nom: ${filters.q}`);
    if (filters.categoryTop) chips.push(labelForCategory(filters.categoryTop));
    filters.categories.forEach((value) => chips.push(labelForCategory(value)));
    if (filters.minPrice) chips.push(`Min ${filters.minPrice} ${config.currency || 'DH'}`);
    if (filters.maxPrice) chips.push(`Max ${filters.maxPrice} ${config.currency || 'DH'}`);
    if (filters.duration) chips.push(filters.duration >= 24 ? '1 jour et plus' : `= ${filters.duration}h`);
    if (filters.rating) chips.push(`Note ${filters.rating}+`);
    filters.features.forEach((value) => chips.push(baseLabels[value] || value));
    filters.languages.forEach((value) => chips.push(baseLabels[value] || value));
    if (filters.discountOnly) chips.push('Promotions');

    activeChips.innerHTML = chips.map((chip) => `<span class="chip">${escapeHtml(chip)}<button type="button" data-ajas-reset>x</button></span>`).join('');
  }

  function applyFilters(container) {
    const filters = readFilters(container);
    const items = sortItems(activities.filter((activity) => matches(activity, filters)));

    activityList.innerHTML = items.map(renderCard).join('');
    resultCount.textContent = String(items.length);
    emptyState.style.display = items.length ? 'none' : 'block';
    renderChips(filters);
  }

  function syncFilters(source, target) {
    target.innerHTML = source.innerHTML;
  }

  function resetAll() {
    destinationInput.value = '';
    categoryTop.value = '';
    sortSelect.value = 'recommended';
    desktopFilters.innerHTML = filterMarkup;
    mobileFilters.innerHTML = filterMarkup;
    bindDesktopAutoApply();
    applyFilters(desktopFilters);
  }

  function bindDesktopAutoApply() {
    desktopFilters.querySelectorAll('input, select').forEach((element) => {
      const eventName = element.type === 'text' || element.type === 'number' ? 'input' : 'change';
      element.addEventListener(eventName, () => applyFilters(desktopFilters));
    });
  }

  function openMobileDrawer() {
    syncFilters(desktopFilters, mobileFilters);
    drawer.classList.add('active');
    drawerBg.classList.add('active');
    document.body.classList.add('aj-activities-static-drawer-open');
  }

  function closeMobileDrawer() {
    drawer.classList.remove('active');
    drawerBg.classList.remove('active');
    document.body.classList.remove('aj-activities-static-drawer-open');
  }

  root.addEventListener('click', (event) => {
    if (event.target.matches('[data-ajas-reset]')) {
      resetAll();
    }
  });

  searchForm.addEventListener('submit', (event) => {
    event.preventDefault();
    applyFilters(desktopFilters);
  });

  categoryTop.addEventListener('change', () => applyFilters(desktopFilters));
  sortSelect.addEventListener('change', () => applyFilters(desktopFilters));
  openDrawerButton.addEventListener('click', openMobileDrawer);
  closeDrawerButton.addEventListener('click', closeMobileDrawer);
  drawerBg.addEventListener('click', closeMobileDrawer);

  applyMobileButton.addEventListener('click', () => {
    syncFilters(mobileFilters, desktopFilters);
    bindDesktopAutoApply();
    applyFilters(desktopFilters);
    closeMobileDrawer();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && drawer.classList.contains('active')) {
      closeMobileDrawer();
    }
  });

  bindDesktopAutoApply();
  applyFilters(desktopFilters);
})();
