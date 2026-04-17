/**
 * Modal « Gestion du stock chambres » sur la page d’édition voyage.
 * Dépend de Bootstrap 5 (modal) et du layout master (meta csrf-token).
 */
(function () {
  var modalEl = document.getElementById('voyageRoomAvailabilityModal');
  if (!modalEl) return;

  var departuresUrl = '';
  var syncDeparturesUrl = '';
  var panelBase = '';
  var selectEl = document.getElementById('ra-departure-select');
  var contentEl = document.getElementById('ra-departure-content');
  var loadingEl = document.getElementById('ra-departure-loading');
  var badgesEl = document.getElementById('ra-departure-badges');
  var alertEl = document.getElementById('ra-modal-alert');
  var syncHintEl = document.getElementById('ra-sync-hint');
  var syncHintResyncEl = document.getElementById('ra-sync-hint-resync');
  var tableWrapEl = document.getElementById('ra-departure-table-wrap');
  var tableBodyEl = document.getElementById('ra-departure-table-body');

  function readEndpointsFromModal() {
    departuresUrl = modalEl.getAttribute('data-departures-url') || '';
    syncDeparturesUrl = modalEl.getAttribute('data-sync-departures-url') || '';
    panelBase = modalEl.getAttribute('data-panel-base') || '';
  }

  readEndpointsFromModal();

  function csrfToken() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') || '' : '';
  }

  function showAlert(msg) {
    alertEl.textContent = msg || '';
    alertEl.classList.toggle('d-none', !msg);
  }

  function setLoading(on) {
    loadingEl.classList.toggle('d-none', !on);
    contentEl.classList.toggle('d-none', on);
  }

  function placeholderHtml() {
    return (
      '<div class="text-center text-muted py-5" id="ra-departure-placeholder">' +
      '<i class="bx bx-calendar-event display-6 d-block mb-2 opacity-50"></i>' +
      'Sélectionnez un départ pour afficher les hôtels et le stock.' +
      '</div>'
    );
  }

  function loadDepartures() {
    if (!departuresUrl) {
      return Promise.reject(new Error('URL des départs manquante.'));
    }

    return fetch(departuresUrl, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    }).then(function (res) {
      if (!res.ok) throw new Error('Liste des départs indisponible.');
      return res.json();
    });
  }

  function getDomTravelDatesStats() {
    var rows = document.querySelectorAll('#travel-dates-container .travel-date-row');
    var activeRows = 0;
    rows.forEach(function (row) {
      var activeInput = row.querySelector('input[name*="[is_active]"]');
      if (!activeInput || activeInput.checked) activeRows += 1;
    });

    return {
      totalRows: rows.length,
      activeRows: activeRows,
      serverWpRows: parseInt(modalEl.getAttribute('data-server-wp-travel-dates-count') || '0', 10) || 0,
      serverLaravelRows: parseInt(modalEl.getAttribute('data-server-laravel-departures-count') || '0', 10) || 0,
    };
  }

  function applyDeparturesEmptyState(departures) {
    if (!syncHintEl) return;
    var count = (departures || []).length;
    var stats = getDomTravelDatesStats();

    if (count > 0) {
      syncHintEl.classList.add('d-none');
      if (syncHintResyncEl) {
        syncHintResyncEl.classList.add('d-none');
        syncHintResyncEl.textContent = '';
      }
      selectEl.disabled = false;
      return;
    }

    syncHintEl.classList.remove('d-none');
    selectEl.disabled = true;

    var hasUnsavedDatesInForm = stats.activeRows > 0 && stats.activeRows > stats.serverWpRows;
    var msg = hasUnsavedDatesInForm
      ? 'Les dates de départ visibles dans le formulaire doivent être enregistrées pour générer les départs Laravel utilisables dans la gestion du stock.'
      : 'Aucun départ synchronisé disponible. Vérifiez que des dates actives existent dans « Dates disponibles (Travelling on) », puis enregistrez le voyage.';

    if (syncHintResyncEl) {
      if (msg) {
        syncHintResyncEl.textContent = msg;
        syncHintResyncEl.classList.remove('d-none');
      } else {
        syncHintResyncEl.classList.add('d-none');
        syncHintResyncEl.textContent = '';
      }
    }
  }

  function syncDeparturesFromWp() {
    if (!syncDeparturesUrl) return Promise.resolve({ success: true });

    return fetch(syncDeparturesUrl, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken(),
      },
      credentials: 'same-origin',
    }).then(function (res) {
      if (!res.ok) {
        throw new Error('Synchronisation des départs impossible.');
      }
      return res.json();
    });
  }

  function fillDepartureSelect(departures) {
    var sel = selectEl;
    sel.innerHTML = '<option value="">— Sélectionnez une date de départ —</option>';
    departures.forEach(function (d) {
      var opt = document.createElement('option');
      opt.value = String(d.id);
      var label =
        (d.start_date || '') +
        (d.end_date ? ' → ' + d.end_date : '') +
        ' · ' +
        (d.status_label || d.status);
      opt.textContent = label;
      opt.dataset.summary = JSON.stringify(d);
      sel.appendChild(opt);
    });
    applyDeparturesEmptyState(departures);
    renderDepartureTable(departures || []);
  }

  function renderDepartureTable(departures) {
    if (!tableWrapEl || !tableBodyEl) return;

    tableBodyEl.innerHTML = '';
    if (!departures.length) {
      tableWrapEl.classList.add('d-none');
      return;
    }

    tableWrapEl.classList.remove('d-none');
    departures.forEach(function (d) {
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td>' + (d.start_date || '—') + '</td>' +
        '<td>' + (d.end_date || '—') + '</td>' +
        '<td>' + (d.total_capacity != null ? d.total_capacity : 0) + '</td>' +
        '<td>' + (d.reserved_capacity != null ? d.reserved_capacity : 0) + '</td>' +
        '<td><strong>' + (d.available_capacity != null ? d.available_capacity : 0) + '</strong></td>' +
        '<td>' + (d.status_label || d.status || '') + '</td>' +
        '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-primary" data-ra-choose="' + d.id + '">Choisir</button></td>';
      tableBodyEl.appendChild(tr);
    });

    tableBodyEl.querySelectorAll('[data-ra-choose]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = btn.getAttribute('data-ra-choose');
        if (!id) return;
        selectEl.value = id;
        selectEl.dispatchEvent(new Event('change'));
      });
    });
  }

  function updateBadgesFromOption(opt) {
    badgesEl.innerHTML = '';
    if (!opt || !opt.value) return;
    var d;
    try {
      d = JSON.parse(opt.dataset.summary || '{}');
    } catch (e) {
      return;
    }
    var b1 = document.createElement('span');
    b1.className = 'badge bg-secondary';
    b1.textContent = d.status_label || d.status || '';
    var b2 = document.createElement('span');
    b2.className = 'badge bg-light text-dark border';
    b2.textContent = 'Restant : ' + (d.available_capacity != null ? d.available_capacity : 0);
    badgesEl.appendChild(b1);
    badgesEl.appendChild(b2);
  }

  function initRoomStockScripts(container) {
    container.querySelectorAll('[data-departure-hotel-section]').forEach(function (section) {
      function recalcPlaces(rowId) {
        var manual = document.getElementById('manual-' + rowId);
        if (manual && manual.checked) return;
        var row = document.getElementById('room-row-' + rowId);
        if (!row) return;
        var rooms = parseInt(row.querySelector('.js-total-rooms-input') && row.querySelector('.js-total-rooms-input').value ? row.querySelector('.js-total-rooms-input').value : '0', 10) || 0;
        var cap = parseInt(row.querySelector('.js-capacity-input') && row.querySelector('.js-capacity-input').value ? row.querySelector('.js-capacity-input').value : '1', 10) || 1;
        var placesEl = document.getElementById('places-' + rowId);
        if (placesEl) placesEl.value = String(rooms * cap);
      }
      section.querySelectorAll('.room-stock-row').forEach(function (tr) {
        var id = tr.id.replace('room-row-', '');
        tr.querySelectorAll('.js-total-rooms-input, .js-capacity-input').forEach(function (inp) {
          inp.addEventListener('input', function () {
            recalcPlaces(id);
          });
        });
        var manual = document.getElementById('manual-' + id);
        if (manual) {
          manual.addEventListener('change', function () {
            if (!this.checked) recalcPlaces(id);
          });
        }
      });
      var addRooms = section.querySelector('.js-add-rooms');
      var addCap = section.querySelector('.js-add-cap');
      var addPlaces = section.querySelector('.js-add-places');
      var addManual = section.querySelector('[id^="manual_new_"]');
      function recalcAdd() {
        if (addManual && addManual.checked) return;
        var r = addRooms ? parseInt(addRooms.value || '0', 10) || 0 : 0;
        var c = addCap ? parseInt(addCap.value || '1', 10) || 1 : 1;
        if (addPlaces) addPlaces.placeholder = String(r * c);
      }
      if (addRooms) addRooms.addEventListener('input', recalcAdd);
      if (addCap) addCap.addEventListener('input', recalcAdd);
      if (addManual) addManual.addEventListener('change', recalcAdd);
    });
  }

  function loadPanel(departureId) {
    if (!departureId) {
      contentEl.innerHTML = placeholderHtml();
      return Promise.resolve();
    }
    setLoading(true);
    showAlert('');
    if (!panelBase) {
      setLoading(false);
      return Promise.reject(new Error('URL du panneau départ manquante.'));
    }
    var url = panelBase + '/' + departureId + '/panel';
    return fetch(url, {
      headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
      .then(function (res) {
        if (!res.ok) throw new Error('Impossible de charger le départ.');
        return res.text();
      })
      .then(function (html) {
        contentEl.innerHTML = html;
        setLoading(false);
        initRoomStockScripts(contentEl);
      })
      .catch(function (err) {
        setLoading(false);
        throw err;
      });
  }

  function submitFormAjax(form) {
    var fd = new FormData(form);
    return fetch(form.action, {
      method: 'POST',
      body: fd,
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken(),
      },
      credentials: 'same-origin',
    }).then(function (res) {
      if (res.status === 422) {
        return res.json().then(function (data) {
          var msg =
            data.message ||
            (data.errors
              ? Object.keys(data.errors)
                  .map(function (k) {
                    return data.errors[k].join(' ');
                  })
                  .join(' ')
              : 'Validation impossible.');
          throw new Error(msg);
        });
      }
      if (!res.ok) throw new Error('Erreur serveur (' + res.status + ').');
      var ct = res.headers.get('content-type') || '';
      if (ct.indexOf('application/json') !== -1) {
        return res.json();
      }
      return { ok: true };
    });
  }

  modalEl.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.tagName !== 'FORM') return;
    if (!form.querySelector('input[name="modal_ajax"]')) return;
    e.preventDefault();
    var cmsg = form.getAttribute('data-confirm-msg');
    if (cmsg && !window.confirm(cmsg)) return;
    showAlert('');
    submitFormAjax(form)
      .then(function (data) {
        var toast = data.message || 'Enregistré.';
        if (window.toastr && window.toastr.success) {
          window.toastr.success(toast);
        }
        var depId = selectEl.value;
        return loadPanel(depId).then(function () {
          return loadDepartures();
        });
      })
      .then(function (payload) {
        if (payload && payload.departures) {
          var current = selectEl.value;
          fillDepartureSelect(payload.departures);
          selectEl.value = current;
          updateBadgesFromOption(selectEl.selectedOptions[0]);
        }
      })
      .catch(function (err) {
        showAlert(err.message || 'Erreur');
      });
  });

  var pendingDep = null;
  function applyContextFromTrigger(btn) {
    if (!btn) return;

    var depUrl = btn.getAttribute('data-ra-departures-url');
    var syncUrl = btn.getAttribute('data-ra-sync-url');
    var panel = btn.getAttribute('data-ra-panel-base');
    var voyageId = btn.getAttribute('data-ra-voyage-id');
    var preselect = btn.getAttribute('data-ra-select-departure');

    if (depUrl) modalEl.setAttribute('data-departures-url', depUrl);
    if (syncUrl) modalEl.setAttribute('data-sync-departures-url', syncUrl);
    if (panel) modalEl.setAttribute('data-panel-base', panel);
    if (voyageId) modalEl.setAttribute('data-laravel-voyage-id', voyageId);

    readEndpointsFromModal();

    if (preselect) {
      pendingDep = preselect;
    }
  }

  document.addEventListener('click', function (e) {
    var target = e.target;
    if (!target || typeof target.closest !== 'function') return;
    var btn = target.closest('[data-ra-open-modal], [data-ra-select-departure]');
    if (!btn) return;
    applyContextFromTrigger(btn);
    var id = btn.getAttribute('data-ra-select-departure');
    if (id) pendingDep = id;
  });

  modalEl.addEventListener('show.bs.modal', function () {
    readEndpointsFromModal();
    showAlert('');
    setLoading(true);
    if (typeof console !== 'undefined' && console.info) {
      var stats = getDomTravelDatesStats();
      console.info('[room-stock-modal]', {
        laravelVoyageId: modalEl.getAttribute('data-laravel-voyage-id'),
        wpTourPostId: modalEl.getAttribute('data-wp-tour-post-id'),
        departuresUrl: departuresUrl,
        syncDeparturesUrl: syncDeparturesUrl,
        panelBase: panelBase,
        loadMethod: 'AJAX GET JSON',
        serverWpTravelDatesCount: modalEl.getAttribute('data-server-wp-travel-dates-count'),
        serverLaravelDeparturesCount: modalEl.getAttribute('data-server-laravel-departures-count'),
        domTravelDatesCount: stats.totalRows,
        domActiveTravelDatesCount: stats.activeRows,
      });
    }

    syncDeparturesFromWp()
      .catch(function () {
        showAlert('Synchronisation WordPress indisponible, affichage des départs existants.');
      })
      .then(function () {
        return loadDepartures();
      })
      .then(function (data) {
        var list = data.departures || [];
        if (typeof console !== 'undefined' && console.info) {
          console.info('[room-stock-modal] departures response', { count: list.length, ids: list.map(function (d) { return d.id; }) });
        }
        fillDepartureSelect(list);

        var selectedId = '';
        if (pendingDep) {
          selectedId = String(pendingDep);
          pendingDep = null;
        } else if (list.length > 0) {
          selectedId = String(list[0].id);
        }

        if (selectedId) {
          selectEl.value = selectedId;
          updateBadgesFromOption(selectEl.selectedOptions[0]);
          return loadPanel(selectedId);
        }

        contentEl.innerHTML = placeholderHtml();
        setLoading(false);
        return Promise.resolve();
      })
      .catch(function (e) {
        setLoading(false);
        showAlert(e.message || 'Impossible de charger les départs.');
      });
  });

  selectEl.addEventListener('change', function () {
    var id = this.value;
    updateBadgesFromOption(this.selectedOptions[0]);
    if (!id) {
      contentEl.innerHTML = placeholderHtml();
      return;
    }
    loadPanel(id).catch(function (e) {
      showAlert(e.message || 'Erreur de chargement');
      setLoading(false);
    });
  });
})();
