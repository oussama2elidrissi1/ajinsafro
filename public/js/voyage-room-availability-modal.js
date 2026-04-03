/**
 * Modal « Gestion du stock chambres » sur la page d’édition voyage.
 * Dépend de Bootstrap 5 (modal) et du layout master (meta csrf-token).
 */
(function () {
  var modalEl = document.getElementById('voyageRoomAvailabilityModal');
  if (!modalEl) return;

  var departuresUrl = modalEl.getAttribute('data-departures-url');
  var panelBase = modalEl.getAttribute('data-panel-base');
  var selectEl = document.getElementById('ra-departure-select');
  var contentEl = document.getElementById('ra-departure-content');
  var loadingEl = document.getElementById('ra-departure-loading');
  var badgesEl = document.getElementById('ra-departure-badges');
  var alertEl = document.getElementById('ra-modal-alert');
  var syncHintEl = document.getElementById('ra-sync-hint');
  var syncHintResyncEl = document.getElementById('ra-sync-hint-resync');

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
    return fetch(departuresUrl, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    }).then(function (res) {
      if (!res.ok) throw new Error('Liste des départs indisponible.');
      return res.json();
    });
  }

  function applyDeparturesEmptyState(departures) {
    if (!syncHintEl) return;
    var count = (departures || []).length;
    var serverWp = parseInt(modalEl.getAttribute('data-server-wp-travel-dates-count') || '0', 10);
    var domRows = document.querySelectorAll('#travel-dates-container .travel-date-row').length;

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

    var msg = '';
    if (domRows > serverWp) {
      msg =
        'Vous avez ajouté ou modifié des dates sans enregistrer le voyage : enregistrez d’abord pour que les départs Laravel soient créés ou mis à jour.';
    } else if (serverWp > 0) {
      msg =
        'Des dates existent dans WordPress mais aucun départ Laravel n’a été trouvé pour ce voyage. Enregistrez à nouveau le voyage pour relancer la synchronisation, ou consultez les journaux (AVAILABILITY_SYNC_CHECK, ROOM_STOCK_MODAL_DEPARTURES).';
    }
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
  document.querySelectorAll('[data-ra-select-departure]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-ra-select-departure');
      if (id) pendingDep = id;
    });
  });

  modalEl.addEventListener('show.bs.modal', function () {
    showAlert('');
    if (typeof console !== 'undefined' && console.info) {
      console.info('[room-stock-modal]', {
        laravelVoyageId: modalEl.getAttribute('data-laravel-voyage-id'),
        wpTourPostId: modalEl.getAttribute('data-wp-tour-post-id'),
        departuresUrl: departuresUrl,
        panelBase: panelBase,
        loadMethod: 'AJAX GET JSON',
        serverWpTravelDatesCount: modalEl.getAttribute('data-server-wp-travel-dates-count'),
        serverLaravelDeparturesCount: modalEl.getAttribute('data-server-laravel-departures-count'),
      });
    }
    loadDepartures()
      .then(function (data) {
        var list = data.departures || [];
        if (typeof console !== 'undefined' && console.info) {
          console.info('[room-stock-modal] departures response', { count: list.length, ids: list.map(function (d) { return d.id; }) });
        }
        fillDepartureSelect(list);
        if (pendingDep) {
          selectEl.value = pendingDep;
          pendingDep = null;
          updateBadgesFromOption(selectEl.selectedOptions[0]);
          return loadPanel(selectEl.value);
        }
      })
      .catch(function (e) {
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
