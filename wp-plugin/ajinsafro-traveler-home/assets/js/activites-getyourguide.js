(function () {
  const root = document.getElementById('aj-activities-static');
  if (!root) {
    return;
  }

  const activities = [
    {
      id: 1,
      title: "Depuis Marrakech : excursion dans le desert d'Agafay avec diner spectacle",
      city: 'Marrakech',
      category: 'desert',
      image: 'https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?auto=format&fit=crop&w=900&q=80',
      rating: 4.8,
      reviews: 1260,
      duration: 6,
      price: 29,
      oldPrice: 42,
      discount: 31,
      badges: ['Best seller'],
      languages: ['fr', 'en'],
      features: ['pickup', 'free_cancel', 'instant'],
      description: "Profitez du desert d'Agafay, coucher de soleil, diner marocain et animation locale."
    },
    {
      id: 2,
      title: 'Marrakech : aventure en quad dans la Palmeraie avec pause the',
      city: 'Marrakech',
      category: 'quad',
      image: 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=80',
      rating: 4.7,
      reviews: 842,
      duration: 3,
      price: 35,
      oldPrice: null,
      discount: 0,
      badges: ['Populaire'],
      languages: ['fr', 'en', 'ar'],
      features: ['pickup', 'free_cancel'],
      description: 'Traversez les pistes de la Palmeraie en quad avec guide, equipement inclus et pause the traditionnelle.'
    },
    {
      id: 3,
      title: 'Marrakech : vol en montgolfiere au lever du soleil avec petit-dejeuner',
      city: 'Marrakech',
      category: 'balloon',
      image: 'https://images.unsplash.com/photo-1507608616759-54f48f0af0ee?auto=format&fit=crop&w=900&q=80',
      rating: 4.9,
      reviews: 3104,
      duration: 4,
      price: 159,
      oldPrice: 190,
      discount: 16,
      badges: ['Top rated'],
      languages: ['fr', 'en'],
      features: ['pickup', 'free_cancel', 'instant'],
      description: 'Survolez les paysages autour de Marrakech au lever du soleil avec transfert et petit-dejeuner berbere.'
    },
    {
      id: 4,
      title: 'Chefchaouen : visite guidee de la medina bleue et points panoramiques',
      city: 'Chefchaouen',
      category: 'cultural',
      image: 'https://images.unsplash.com/photo-1548018560-c7196548e84d?auto=format&fit=crop&w=900&q=80',
      rating: 4.6,
      reviews: 530,
      duration: 5,
      price: 22,
      oldPrice: null,
      discount: 0,
      badges: [],
      languages: ['fr', 'en', 'es'],
      features: ['free_cancel'],
      description: "Decouvrez les ruelles bleues, l'histoire locale et les meilleurs spots photo."
    },
    {
      id: 5,
      title: 'Agadir : balade a dos de chameau avec barbecue et coucher de soleil',
      city: 'Agadir',
      category: 'desert',
      image: 'https://images.unsplash.com/photo-1609151376730-f246ec0b99e6?auto=format&fit=crop&w=900&q=80',
      rating: 4.5,
      reviews: 712,
      duration: 3,
      price: 27,
      oldPrice: 35,
      discount: 23,
      badges: ['Offre speciale'],
      languages: ['fr', 'en'],
      features: ['pickup', 'free_cancel'],
      description: 'Balade au coucher du soleil avec transfert hotel et barbecue dans une ambiance conviviale.'
    },
    {
      id: 6,
      title: "Essaouira : sortie en bateau et decouverte de la cote Atlantique",
      city: 'Essaouira',
      category: 'water',
      image: 'https://images.unsplash.com/photo-1528137871618-79d2761e3fd5?auto=format&fit=crop&w=900&q=80',
      rating: 4.4,
      reviews: 390,
      duration: 2,
      price: 31,
      oldPrice: null,
      discount: 0,
      badges: [],
      languages: ['fr', 'en'],
      features: ['free_cancel', 'instant'],
      description: "Decouvrez la baie d'Essaouira depuis la mer avec un equipage local."
    },
    {
      id: 7,
      title: 'Fes : visite privee de la medina, tanneries et monuments historiques',
      city: 'Fes',
      category: 'cultural',
      image: 'https://images.unsplash.com/photo-1575936123452-b67c3203c357?auto=format&fit=crop&w=900&q=80',
      rating: 4.8,
      reviews: 960,
      duration: 6,
      price: 44,
      oldPrice: null,
      discount: 0,
      badges: ['Guide expert'],
      languages: ['fr', 'en', 'ar'],
      features: ['pickup', 'free_cancel'],
      description: 'Explorez la plus ancienne medina imperiale avec un guide local, souks et patrimoine.'
    },
    {
      id: 8,
      title: "Ourika : excursion dans l'Atlas avec cascades et dejeuner berbere",
      city: 'Marrakech',
      category: 'nature',
      image: 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=900&q=80',
      rating: 4.6,
      reviews: 1480,
      duration: 8,
      price: 24,
      oldPrice: 32,
      discount: 25,
      badges: ['Best seller'],
      languages: ['fr', 'en'],
      features: ['pickup', 'free_cancel'],
      description: "Une journee nature dans l'Atlas avec villages berberes, cascades et paysages."
    },
    {
      id: 9,
      title: "Tanger : visite de la ville, grottes d'Hercule et cap Spartel",
      city: 'Tanger',
      category: 'cultural',
      image: 'https://images.unsplash.com/photo-1539650116574-75c0c6d73f6e?auto=format&fit=crop&w=900&q=80',
      rating: 4.7,
      reviews: 680,
      duration: 5,
      price: 39,
      oldPrice: null,
      discount: 0,
      badges: ['Nouveau'],
      languages: ['fr', 'en', 'es'],
      features: ['pickup', 'instant'],
      description: "Explorez Tanger entre medina, corniche, grottes d'Hercule et vue sur la rencontre des deux mers."
    },
    {
      id: 10,
      title: 'Dakhla : initiation kitesurf avec moniteur et equipement complet',
      city: 'Dakhla',
      category: 'water',
      image: 'https://images.unsplash.com/photo-1500375592092-40eb2168fd21?auto=format&fit=crop&w=900&q=80',
      rating: 4.9,
      reviews: 214,
      duration: 2,
      price: 68,
      oldPrice: 80,
      discount: 15,
      badges: ['Sport'],
      languages: ['fr', 'en'],
      features: ['free_cancel'],
      description: 'Session kitesurf dans la lagune de Dakhla avec moniteur qualifie et materiel inclus.'
    },
    {
      id: 11,
      title: 'Merzouga : bivouac dans le Sahara, dromadaire et nuit sous les etoiles',
      city: 'Merzouga',
      category: 'desert',
      image: 'https://images.unsplash.com/photo-1549144511-f099e773c147?auto=format&fit=crop&w=900&q=80',
      rating: 4.9,
      reviews: 1905,
      duration: 24,
      price: 89,
      oldPrice: 120,
      discount: 26,
      badges: ['Inoubliable'],
      languages: ['fr', 'en', 'ar'],
      features: ['free_cancel', 'instant'],
      description: 'Experience complete au Sahara avec dromadaire, diner, campement, musique et lever de soleil.'
    },
    {
      id: 12,
      title: 'Casablanca : visite de la Mosquee Hassan II et corniche',
      city: 'Casablanca',
      category: 'cultural',
      image: 'https://images.unsplash.com/photo-1553729459-efe14ef6055d?auto=format&fit=crop&w=900&q=80',
      rating: 4.5,
      reviews: 431,
      duration: 4,
      price: 33,
      oldPrice: null,
      discount: 0,
      badges: [],
      languages: ['fr', 'en'],
      features: ['pickup', 'free_cancel'],
      description: 'Decouverte de Casablanca moderne et historique, incluant la Mosquee Hassan II.'
    }
  ];

  const labels = {
    desert: 'Desert',
    quad: 'Quad & buggy',
    balloon: 'Montgolfiere',
    cultural: 'Culture & ville',
    water: 'Mer & bateau',
    nature: 'Nature & Atlas',
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

  const filterMarkup = `
    <div class="filter-group">
      <h3>Rechercher par nom</h3>
      <input class="side-search" id="ajas-name-filter" type="text" placeholder="ex: quad, desert, Fes...">
    </div>

    <div class="filter-group">
      <h3>Categorie</h3>
      <div class="filter-list">
        <label class="filter-row"><input type="checkbox" name="category" value="desert"> Desert</label>
        <label class="filter-row"><input type="checkbox" name="category" value="quad"> Quad & buggy</label>
        <label class="filter-row"><input type="checkbox" name="category" value="balloon"> Montgolfiere</label>
        <label class="filter-row"><input type="checkbox" name="category" value="cultural"> Culture & ville</label>
        <label class="filter-row"><input type="checkbox" name="category" value="water"> Mer & bateau</label>
        <label class="filter-row"><input type="checkbox" name="category" value="nature"> Nature & Atlas</label>
      </div>
    </div>

    <div class="filter-group">
      <h3>Prix</h3>
      <div class="price-grid">
        <input id="ajas-min-price" type="number" placeholder="Min">
        <input id="ajas-max-price" type="number" placeholder="Max">
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
        <label class="filter-row"><input type="radio" name="rating" value="4.5"> <span class="rating-stars">?????</span> 4.5 et plus</label>
        <label class="filter-row"><input type="radio" name="rating" value="4.7"> <span class="rating-stars">?????</span> 4.7 et plus</label>
        <label class="filter-row"><input type="radio" name="rating" value="4.9"> <span class="rating-stars">?????</span> 4.9 et plus</label>
      </div>
    </div>

    <div class="filter-group">
      <h3>Options</h3>
      <div class="filter-list">
        <label class="filter-row"><input type="checkbox" name="feature" value="pickup"> Prise en charge incluse</label>
        <label class="filter-row"><input type="checkbox" name="feature" value="free_cancel"> Annulation gratuite</label>
        <label class="filter-row"><input type="checkbox" name="feature" value="instant"> Confirmation instantanee</label>
        <label class="filter-row"><input type="checkbox" id="ajas-discount-only"> Promotions uniquement</label>
      </div>
    </div>

    <div class="filter-group">
      <h3>Langue du guide</h3>
      <div class="filter-list">
        <label class="filter-row"><input type="checkbox" name="language" value="fr"> Francais</label>
        <label class="filter-row"><input type="checkbox" name="language" value="en"> Anglais</label>
        <label class="filter-row"><input type="checkbox" name="language" value="ar"> Arabe</label>
        <label class="filter-row"><input type="checkbox" name="language" value="es"> Espagnol</label>
      </div>
    </div>
  `;

  desktopFilters.innerHTML = filterMarkup;
  mobileFilters.innerHTML = filterMarkup;

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
    const haystack = `${activity.title} ${activity.city} ${activity.description}`.toLowerCase();

    if (filters.destination && !haystack.includes(filters.destination)) return false;
    if (filters.q && !haystack.includes(filters.q)) return false;
    if (filters.categoryTop && activity.category !== filters.categoryTop) return false;
    if (filters.categories.length && !filters.categories.includes(activity.category)) return false;
    if (filters.minPrice && activity.price < filters.minPrice) return false;
    if (filters.maxPrice && activity.price > filters.maxPrice) return false;
    if (filters.duration) {
      if (filters.duration < 24 && activity.duration > filters.duration) return false;
      if (filters.duration >= 24 && activity.duration < 24) return false;
    }
    if (filters.rating && activity.rating < filters.rating) return false;
    if (filters.features.length && !filters.features.every((item) => activity.features.includes(item))) return false;
    if (filters.languages.length && !filters.languages.every((item) => activity.languages.includes(item))) return false;
    if (filters.discountOnly && !activity.discount) return false;

    return true;
  }

  function sortItems(items) {
    const mode = sortSelect.value;
    const sorted = [...items];

    if (mode === 'recommended') sorted.sort((a, b) => (b.rating - a.rating) || (b.reviews - a.reviews));
    if (mode === 'price-asc') sorted.sort((a, b) => a.price - b.price);
    if (mode === 'price-desc') sorted.sort((a, b) => b.price - a.price);
    if (mode === 'rating-desc') sorted.sort((a, b) => b.rating - a.rating);
    if (mode === 'duration-asc') sorted.sort((a, b) => a.duration - b.duration);
    if (mode === 'discount-desc') sorted.sort((a, b) => b.discount - a.discount);

    return sorted;
  }

  function durationText(hours) {
    if (hours < 24) return `${hours} h`;
    if (hours === 24) return '1 jour';
    return `${Math.round(hours / 24)} jours`;
  }

  function renderCard(activity, index) {
    const featureText = activity.features.map((key) => labels[key]).filter(Boolean).slice(0, 3).join(' � ');
    const languageText = activity.languages.map((key) => labels[key]).filter(Boolean).join(', ');
    const badgeMarkup = activity.badges[0] ? `<span class="activity-badge">${activity.badges[0]}</span>` : '';
    const oldPriceMarkup = activity.oldPrice ? `<span class="old-price">${activity.oldPrice} EUR</span>` : '';
    const availabilityMarkup = activity.discount
      ? `<span class="availability">-${activity.discount}% aujourd'hui</span>`
      : '<span class="availability">Disponible</span>';
    const promoMarkup = index > 0 && index % 4 === 0
      ? '<div class="promo-strip">OFFRES AJINSAFRO � Reservez votre prochaine activite</div>'
      : '';

    return `${promoMarkup}
      <article class="activity-card">
        <div class="activity-image">
          <img src="${activity.image}" alt="${activity.title}" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1524492412937-b28074a5d7da?auto=format&fit=crop&w=700&q=80'">
          <button class="heart" type="button" aria-label="Ajouter aux favoris">?</button>
          ${badgeMarkup}
        </div>
        <div class="activity-content">
          <div class="activity-kind">${activity.city} � ${labels[activity.category] || 'Activite'}</div>
          <h3>${activity.title}</h3>
          <div class="rating-line">
            <span class="stars">?????</span>
            <span class="score">${activity.rating.toFixed(1)}</span>
            <span class="reviews">(${activity.reviews.toLocaleString('fr-FR')} avis)</span>
          </div>
          <div class="details">
            <span>Duree : ${durationText(activity.duration)}</span>
            <span>Guide : ${languageText}</span>
            <span>${featureText}</span>
          </div>
          <div class="green-note">Reservation simple � Support Ajinsafro</div>
        </div>
        <aside class="activity-side">
          ${availabilityMarkup}
          <div class="price-block">
            <small>A partir de</small>
            <div>${oldPriceMarkup}<span class="price">${activity.price} EUR</span></div>
            <div class="price-sub">par personne</div>
          </div>
          <button class="book-btn" type="button">Voir l'offre</button>
        </aside>
      </article>`;
  }

  function renderChips(filters) {
    const chips = [];
    if (filters.destination) chips.push(`Recherche: ${filters.destination}`);
    if (filters.q) chips.push(`Nom: ${filters.q}`);
    if (filters.categoryTop) chips.push(labels[filters.categoryTop] || filters.categoryTop);
    filters.categories.forEach((value) => chips.push(labels[value] || value));
    if (filters.minPrice) chips.push(`Min ${filters.minPrice} EUR`);
    if (filters.maxPrice) chips.push(`Max ${filters.maxPrice} EUR`);
    if (filters.duration) chips.push(filters.duration >= 24 ? '1 jour et plus' : `= ${filters.duration}h`);
    if (filters.rating) chips.push(`Note ${filters.rating}+`);
    filters.features.forEach((value) => chips.push(labels[value] || value));
    filters.languages.forEach((value) => chips.push(labels[value] || value));
    if (filters.discountOnly) chips.push('Promotions');

    activeChips.innerHTML = chips.map((chip) => `<span class="chip">${chip}<button type="button" data-ajas-reset>x</button></span>`).join('');
  }

  function applyFilters(container) {
    const filters = readFilters(container);
    const items = sortItems(activities.filter((activity) => matches(activity, filters)));

    activityList.innerHTML = items.map(renderCard).join('');
    resultCount.textContent = String(items.length);
    emptyState.style.display = items.length ? 'none' : 'block';
    renderChips(filters);
  }

  function resetContainer(container) {
    container.querySelectorAll('input[type="text"], input[type="number"]').forEach((input) => {
      input.value = '';
    });
    container.querySelectorAll('input[type="checkbox"]').forEach((input) => {
      input.checked = false;
    });
    container.querySelectorAll('input[type="radio"]').forEach((input) => {
      input.checked = input.value === '';
    });
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
