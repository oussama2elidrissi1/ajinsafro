/* Native interactions for the public Hajj & Omra detail page. */
(function () {
  'use strict';

  function init() {
    var root = document.querySelector('[data-ajod]');
    if (!root) return;

    var departure = root.querySelector('#ajho-departure');
    var room = root.querySelector('#ajho-room-type');
    var adults = root.querySelector('#ajho-adults');
    var children = root.querySelector('#ajho-children');
    var choices = Array.from(root.querySelectorAll('[data-departure-choice]'));
    var estimate = root.querySelector('[data-estimate]');
    var estimateNote = root.querySelector('[data-estimate-note]');
    var currency = root.dataset.currency || 'DH';
    var translations = JSON.parse(root.querySelector('[data-translations-json]')?.textContent || '{}');
    var t = function (text) { return translations[text] || text; };
    var formulas = JSON.parse(root.querySelector('[data-formulas-json]')?.textContent || '[]');
    var formulaSelect = root.querySelector('#ajho-formula');
    var tariffId = root.querySelector('[name="tariff_id"]');
    var departureId = root.querySelector('[name="departure_id"]');
    function currentFormula() { return formulas.find(function (item) { return String(item.id) === formulaSelect?.value; }); }
    function localized(item, field) { return root.lang === 'ar' ? item[field + '_ar'] || item[field + '_fr'] || item[field] : item[field + '_fr'] || item[field] || item[field + '_ar']; }
    var numberFormat = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 });

    function amount(value) {
      if (value === undefined || value === null || value === '') return null;
      var parsed = Number(value);
      return Number.isFinite(parsed) && parsed >= 0 ? parsed : null;
    }

    function money(value) {
      return value === null ? t('Sur demande') : numberFormat.format(value) + ' ' + currency;
    }

    function option(select) {
      return select.options[select.selectedIndex] || { dataset: {} };
    }

    function updateEstimate() {
      if (tariffId) tariffId.value = option(room).dataset.tariffId || '';
      var adultCount = Number(adults.value);
      var childCount = Number(children.value || 0);
      if (!adults.value || !Number.isInteger(adultCount) || adultCount < 1 || adultCount > 20 ||
          !Number.isInteger(childCount) || childCount < 0 || childCount > 20) {
        estimate.textContent = t('À confirmer');
        estimateNote.textContent = t('Renseignez de 1 à 20 adultes et de 0 à 20 enfants.');
        return;
      }
      var rate = amount(option(room).dataset.price);
      if (!formulaSelect && rate === null) rate = amount(option(departure).dataset.price);
      if (!formulaSelect && rate === null) rate = amount(root.dataset.basePrice);
      if (formulaSelect) {
        room.required = true;
        room.setCustomValidity(rate === null ? t('Choisissez une formule et un tarif disponibles.') : '');
        var hasDates = Array.from(departure.options).some(function (item) { return !!item.value; });
        departure.required = hasDates;
        departure.setCustomValidity(hasDates && (!departure.value || option(departure).disabled) ? t('Choisir un départ') : '');
      }
      var childRate = amount(root.dataset.childPrice);
      var total = rate === null ? null : rate * adultCount + (childRate === null ? 0 : childRate * childCount);
      estimate.textContent = money(total);
      estimateNote.textContent = (childCount > 0 && childRate === null ? t('Tarif enfants à confirmer en complément. ') : '') +
        t('Estimation indicative, confirmée par votre conseiller.');
    }

    function updateDeparture() {
      var selected = option(departure);
      if (departureId) departureId.value = selected.dataset.id || '';
      choices.forEach(function (choice) { choice.checked = !choice.disabled && !!departure.value && choice.value === departure.value; });
      root.querySelector('[data-summary-date]').textContent = selected.dataset.label || t('Date sur demande');
      root.querySelector('[data-summary-seats]').textContent = selected.dataset.seats || '0';
      var price = formulaSelect ? amount(root.dataset.basePrice) : amount(selected.dataset.price);
      root.querySelector('[data-summary-price]').textContent = money(price === null ? amount(root.dataset.basePrice) : price);
      updateEstimate();
    }

    function updateFormula() {
      var formula = currentFormula();
      var previous = tariffId?.value;
      room.replaceChildren(new Option(t('Choisir une chambre'), ''));
      Object.values(formula?.prices || {}).forEach(function (price) {
        var item = new Option(localized(price, 'room_type_label') + ' — ' + money(Number(price.price)), price.room_type);
        item.dataset.price = price.price;
        item.dataset.tariffId = price.tariff_id;
        item.disabled = Number(price.stock) <= 0;
        room.appendChild(item);
        if (String(price.tariff_id) === previous && !item.disabled) item.selected = true;
      });
      if (!room.value) {
        var cheapest = Array.from(room.options).filter(function (item) { return item.value && !item.disabled; }).sort(function (a, b) { return Number(a.dataset.price) - Number(b.dataset.price); })[0];
        if (cheapest) cheapest.selected = true;
      }
      Array.from(departure.options).forEach(function (item) {
        item.disabled = !!(formula?.departure_id && String(formula.departure_id) !== item.dataset.id);
      });
      if (formula?.departure_id || option(departure).disabled) {
        var first = Array.from(departure.options).find(function (item) { return item.value && !item.disabled; });
        departure.value = first?.value || '';
      }
      choices.forEach(function (choice) {
        if (choice.dataset.originalDisabled === undefined) choice.dataset.originalDisabled = String(choice.disabled);
        var matching = Array.from(departure.options).find(function (item) { return item.value === choice.value; });
        choice.disabled = choice.dataset.originalDisabled === 'true' || !matching || matching.disabled;
      });
      updateDeparture();
    }

    choices.forEach(function (choice) {
      choice.addEventListener('change', function () {
        if (choice.checked && !choice.disabled) {
          departure.value = choice.value;
          updateDeparture();
        }
      });
    });
    departure.addEventListener('change', updateDeparture);
    room.addEventListener('change', updateEstimate);
    adults.addEventListener('input', updateEstimate);
    children.addEventListener('input', updateEstimate);
    updateDeparture();
    if (formulaSelect) { formulaSelect.addEventListener('change', updateFormula); updateFormula(); }

    root.querySelectorAll('img[data-fallback]').forEach(function (img) {
      function fallback() {
        if (img.getAttribute('src') !== img.dataset.fallback) img.src = img.dataset.fallback;
      }
      img.addEventListener('error', fallback);
      if (img.complete && img.naturalWidth === 0) fallback();
    });

    var galleryMain = root.querySelector('[data-gallery-main]');
    var galleryCount = root.querySelector('[data-gallery-count]');
    var thumbnails = Array.from(root.querySelectorAll('[data-gallery-image]'));
    thumbnails.forEach(function (button) {
      button.addEventListener('click', function () {
        galleryMain.src = button.dataset.galleryImage;
        galleryMain.alt = button.querySelector('img').alt;
        galleryMain.closest('a').href = button.dataset.galleryImage;
        galleryCount.textContent = button.dataset.galleryIndex + ' / ' + thumbnails.length;
        thumbnails.forEach(function (thumb) { thumb.setAttribute('aria-pressed', String(thumb === button)); });
      });
    });

    root.querySelectorAll('[data-ajho-share]').forEach(function (button) {
      button.addEventListener('click', async function () {
        var url = button.dataset.shareUrl || window.location.href;
        var title = button.dataset.shareTitle || document.title;
        if (navigator.share) {
          try {
            await navigator.share({ title: title, url: url });
            return;
          } catch (error) {
            if (error.name === 'AbortError') return;
          }
        }
        try {
          await navigator.clipboard.writeText(url);
          root.querySelector('[data-share-status]').textContent = t('Lien copié dans le presse-papiers.');
          button.textContent = t('Lien copié');
          window.setTimeout(function () { button.textContent = t('Partager'); }, 2000);
        } catch (error) {
          window.prompt('Copiez ce lien :', url);
        }
      });
    });

    var nav = document.querySelector('.aj-navbar');
    var topbar = document.querySelector('.aj-topbar');
    var adminbar = document.querySelector('#wpadminbar');
    var sidebar = root.querySelector('.ajod-sidebar');
    var tabs = root.querySelector('.ajod-tabs');
    var links = Array.from(tabs.querySelectorAll('a'));
    var sections = links.map(function (link) { return document.getElementById(link.hash.slice(1)); });
    var scrollPending = false;

    function updateActiveSection() {
      scrollPending = false;
      var threshold = tabs.getBoundingClientRect().bottom + 24;
      var active = 0;
      sections.forEach(function (section, index) {
        if (section && section.getBoundingClientRect().top <= threshold) active = index;
      });
      links.forEach(function (link, index) {
        if (index === active) link.setAttribute('aria-current', 'location');
        else link.removeAttribute('aria-current');
      });
    }

    function measure() {
      var navHeight = nav ? Math.round(nav.getBoundingClientRect().height) : 0;
      var adminHeight = adminbar && getComputedStyle(adminbar).position === 'fixed' ? Math.round(adminbar.getBoundingClientRect().height) : 0;
      document.body.style.setProperty('--ajod-header-height', navHeight + 'px');
      document.body.style.setProperty('--ajod-topbar-height', (topbar ? Math.round(topbar.getBoundingClientRect().height) : 0) + 'px');
      document.body.style.setProperty('--ajod-admin-height', adminHeight + 'px');
      // Keep every form field reachable when the sidebar is taller than the viewport.
      sidebar.classList.toggle('is-tall', sidebar.offsetHeight > window.innerHeight - navHeight - adminHeight - 80);
      updateActiveSection();
    }

    window.addEventListener('scroll', function () {
      if (!scrollPending) {
        scrollPending = true;
        window.requestAnimationFrame(updateActiveSection);
      }
    }, { passive: true });
    window.addEventListener('resize', measure);
    if (window.ResizeObserver) {
      var observer = new ResizeObserver(measure);
      [nav, topbar, adminbar, sidebar].filter(Boolean).forEach(function (element) { observer.observe(element); });
    }
    measure();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
}());
