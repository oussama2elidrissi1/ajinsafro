import flatpickr from "flatpickr";
import { French } from "flatpickr/dist/l10n/fr.js";
import "flatpickr/dist/flatpickr.min.css";

function escapeHtml(s) {
  if (s == null || s === "") return "";
  const d = document.createElement("div");
  d.textContent = s;
  return d.innerHTML;
}

function pad2(n) {
  return String(n).padStart(2, "0");
}

function formatYmd(d) {
  return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
}

function parseYmd(s) {
  const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s);
  if (!m) return null;
  return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
}

/** Monday = 0 .. Sunday = 6 (ISO week: Monday first) */
function mondayIndex(jsDay) {
  return jsDay === 0 ? 6 : jsDay - 1;
}

function monthMatrix(year, month0) {
  const first = new Date(year, month0, 1);
  const startPad = mondayIndex(first.getDay());
  const daysInMonth = new Date(year, month0 + 1, 0).getDate();
  const cells = [];
  let day = 1 - startPad;
  for (let c = 0; c < 42; c++) {
    const cur = new Date(year, month0, day);
    const inMonth = day >= 1 && day <= daysInMonth;
    cells.push({ date: cur, inMonth });
    day += 1;
  }
  return cells;
}

function statusChipClass(status) {
  if (status === "VALIDEE") return "bg-green-50 text-green-700 border-green-100";
  if (status === "ANNULEE") return "bg-red-50 text-red-600 border-red-100";
  return "bg-yellow-50 text-yellow-700 border-yellow-100";
}

function init() {
  const root = document.getElementById("ajin-cal-root");
  if (!root || !window.AJIN_CALENDAR_CONFIG) return;

  const cfg = window.AJIN_CALENDAR_CONFIG;
  const eventsUrl = cfg.eventsUrl;
  const detailsUrl = cfg.detailsUrl;
  const reservationDetailsUrl = cfg.reservationDetailsUrl;

  const gridEl = document.getElementById("ajin-cal-grid");
  const titleEl = document.getElementById("ajin-cal-month-title");
  const form = document.getElementById("calendar-filters");
  const fpInput = document.getElementById("ajin-cal-range");
  const hiddenFrom = document.getElementById("ajin-cal-date-from");
  const hiddenTo = document.getElementById("ajin-cal-date-to");

  let viewYear = cfg.initialYear;
  let viewMonth = cfg.initialMonth0;
  let eventsCache = [];

  let fp = null;
  if (fpInput) {
    fp = flatpickr(fpInput, {
      mode: "range",
      locale: French,
      dateFormat: "Y-m-d",
      allowInput: true,
      defaultDate:
        cfg.dateFrom && cfg.dateTo
          ? [cfg.dateFrom, cfg.dateTo]
          : undefined,
      onChange(selected) {
        if (selected.length === 2) {
          hiddenFrom.value = formatYmd(selected[0]);
          hiddenTo.value = formatYmd(selected[1]);
        } else if (selected.length === 0) {
          hiddenFrom.value = "";
          hiddenTo.value = "";
        }
      },
    });
  }

  function getFilterParams() {
    if (!form) return {};
    const fd = new FormData(form);
    const o = {};
    fd.forEach((v, k) => {
      if (v !== "") o[k] = v;
    });
    o.month = `${viewYear}-${pad2(viewMonth + 1)}`;
    if (hiddenFrom && hiddenTo && hiddenFrom.value && hiddenTo.value) {
      o.date_from = hiddenFrom.value;
      o.date_to = hiddenTo.value;
    } else {
      delete o.date_from;
      delete o.date_to;
    }
    return o;
  }

  function setTitle() {
    const d = new Date(viewYear, viewMonth, 1);
    if (titleEl) {
      titleEl.textContent = d.toLocaleDateString("fr-FR", {
        month: "long",
        year: "numeric",
      });
    }
  }

  function eventsForDay(ymd) {
    return eventsCache.filter((e) => e.start === ymd);
  }

  function renderGrid() {
    if (!gridEl) return;
    setTitle();
    const cells = monthMatrix(viewYear, viewMonth);
    gridEl.innerHTML = "";

    cells.forEach(({ date, inMonth }) => {
      const ymd = formatYmd(date);
      const dayNum = date.getDate();
      const isWeekend = date.getDay() === 0 || date.getDay() === 6;
      const list = eventsForDay(ymd);

      const cell = document.createElement("div");
      cell.className = [
        "ajin-cal-day min-h-[100px] sm:min-h-[120px] p-1.5 sm:p-2 border border-gray-100 flex flex-col relative group",
        inMonth ? "bg-white" : "bg-gray-50 cursor-not-allowed",
        inMonth ? "hover:bg-[#e6f3fa]/30 cursor-pointer" : "",
      ].join(" ");
      cell.dataset.date = ymd;

      const head = document.createElement("div");
      head.className = "flex justify-between items-start mb-1 shrink-0";
      if (inMonth) {
        head.innerHTML = `<span class="opacity-0 group-hover:opacity-100 transition-opacity text-[#0083c4] text-[10px] font-bold"><i class="fas fa-plus"></i></span>
          <span class="text-xs font-bold ${isWeekend ? "text-[#f37a1f]" : "text-gray-700"} group-hover:text-[#0083c4] ml-auto">${dayNum}</span>`;
      } else {
        head.innerHTML = `<span class="text-xs font-bold text-gray-300 ml-auto">${dayNum}</span>`;
      }
      cell.appendChild(head);

      const chips = document.createElement("div");
      chips.className = "space-y-1 flex-grow overflow-hidden";

      list.slice(0, 4).forEach((ev) => {
        const p = ev.extendedProps || {};
        const kind = p.kind || "departure";
        const chip = document.createElement("button");
        chip.type = "button";
        chip.className = [
          "trip-chip w-full text-left text-[9px] font-medium px-1.5 py-1 rounded truncate shadow-sm border transition-all hover:brightness-95",
          kind === "reservation"
            ? statusChipClass(p.reservation_status)
            : "bg-blue-50 text-[#0083c4] border-blue-100",
        ].join(" ");
        chip.title = ev.title || "";
        chip.innerHTML =
          kind === "reservation"
            ? `<i class="fas fa-ticket-alt mr-1"></i>${escapeHtml(ev.title)}`
            : `<i class="fas fa-plane-departure mr-1"></i>${escapeHtml(ev.title)}`;
        chip.addEventListener("click", (e) => {
          e.stopPropagation();
          openEventModal(ev);
        });
        chips.appendChild(chip);
      });
      if (list.length > 4) {
        const more = document.createElement("div");
        more.className = "text-[9px] text-gray-400 font-bold text-center";
        more.textContent = `+${list.length - 4}`;
        chips.appendChild(more);
      }

      cell.appendChild(chips);

      if (inMonth) {
        cell.addEventListener("click", () => {
          if (list.length === 0) openEmptyDayModal(ymd);
        });
      }

      gridEl.appendChild(cell);
    });
  }

  function buildQuery() {
    const p = getFilterParams();
    return new URLSearchParams(p).toString();
  }

  async function loadEvents() {
    if (!eventsUrl) return;
    const url = eventsUrl + (buildQuery() ? "?" + buildQuery() : "");
    const r = await fetch(url, {
      headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
      credentials: "same-origin",
    });
    eventsCache = await r.json();
    if (!Array.isArray(eventsCache)) eventsCache = [];
    renderGrid();
  }

  function openEventModal(ev) {
    const p = ev.extendedProps || {};
    const kind = p.kind || "departure";

    if (kind === "reservation" && p.reservation_id) {
      openReservationModal(p.reservation_id);
      return;
    }

    const modal = document.getElementById("ajin-modal-departure");
    const body = document.getElementById("ajin-modal-departure-body");
    const footer = document.getElementById("ajin-modal-departure-footer");
    if (!modal || !body) return;

    body.innerHTML = `<div class="text-center py-6 text-gray-500 text-sm"><i class="fas fa-spinner fa-spin"></i> Chargement…</div>`;
    if (footer) footer.classList.add("hidden");

    const params = { date: p.departure_date };
    if (p.travel_date_id) params.travel_date_id = p.travel_date_id;
    else if (p.voyage_id) params.voyage_id = p.voyage_id;
    else if (p.wp_travel_id) params.wp_travel_id = p.wp_travel_id;
    const qs = new URLSearchParams(params).toString();

    fetch(detailsUrl + "?" + qs, {
      headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.error) {
          body.innerHTML = `<p class="text-red-600 text-sm">${escapeHtml(data.error)}</p>`;
          return;
        }
        let html = "";
        if (data.featured_image_url) {
          html += `<div class="mb-3"><img src="${escapeHtml(data.featured_image_url)}" alt="" class="w-full max-h-48 object-cover rounded-xl"></div>`;
        }
        html += `<h4 class="font-bold text-[#0e3a5a] mb-2">${escapeHtml(data.name || "")}</h4>`;
        html += `<p class="text-sm text-gray-600 mb-1">Départ : <strong>${escapeHtml(data.departure_date_formatted || data.departure_date || "")}</strong></p>`;
        if (data.destination)
          html += `<p class="text-sm text-gray-600 mb-1">Destination : ${escapeHtml(data.destination)}</p>`;
        if (data.display_price != null)
          html += `<p class="text-sm text-gray-600 mb-3">À partir de <strong>${escapeHtml(String(data.display_price))} ${escapeHtml(data.currency_symbol || "DH")}</strong></p>`;
        body.innerHTML = html;
        const bCons = document.getElementById("ajin-btn-dep-consulter");
        const bRes = document.getElementById("ajin-btn-dep-reserver");
        if (bCons) bCons.href = data.route_consulter || "#";
        if (bRes) bRes.href = data.route_reserver || "#";
        if (footer) footer.classList.remove("hidden");
      })
      .catch(() => {
        body.innerHTML = `<p class="text-red-600 text-sm">Impossible de charger le détail.</p>`;
      });

    modal.classList.remove("hidden");
    modal.classList.add("flex");
  }

  function openReservationModal(id) {
    const modal = document.getElementById("ajin-modal-reservation");
    const body = document.getElementById("ajin-modal-reservation-body");
    if (!modal || !body) return;
    body.innerHTML = `<div class="text-center py-6 text-gray-500"><i class="fas fa-spinner fa-spin"></i></div>`;
    modal.classList.remove("hidden");
    modal.classList.add("flex");

    fetch(reservationDetailsUrl + "?id=" + encodeURIComponent(id), {
      headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.error) {
          body.innerHTML = `<p class="text-red-600">${escapeHtml(data.error)}</p>`;
          return;
        }
        const st = data.status || "";
        const badge =
          st === "VALIDEE"
            ? "bg-green-50 text-green-700 border-green-200"
            : st === "ANNULEE"
              ? "bg-red-50 text-red-700 border-red-200"
              : "bg-yellow-50 text-yellow-700 border-yellow-200";
        body.innerHTML = `
          <div class="space-y-2 text-sm">
            <div class="flex items-center justify-between gap-2">
              <span class="text-xs font-bold uppercase text-gray-400">Dossier #${data.id}</span>
              <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold border ${badge}">${escapeHtml(st)}</span>
            </div>
            <p class="font-bold text-[#0e3a5a]">${escapeHtml(data.client || "")}</p>
            <p class="text-gray-600">${escapeHtml(data.tour_name || "")}</p>
            <p class="text-gray-500 text-xs">${escapeHtml(data.departure_date_formatted || data.departure_date || "")}</p>
            ${data.branch ? `<p class="text-xs text-gray-400">Agence : ${escapeHtml(data.branch)}</p>` : ""}
            ${data.email ? `<p class="text-xs"><i class="far fa-envelope mr-1"></i>${escapeHtml(data.email)}</p>` : ""}
            ${data.phone ? `<p class="text-xs"><i class="fas fa-phone mr-1"></i>${escapeHtml(data.phone)}</p>` : ""}
          </div>`;
        document.getElementById("ajin-btn-res-edit").href = data.route_edit || "#";
      })
      .catch(() => {
        body.innerHTML = `<p class="text-red-600">Erreur de chargement.</p>`;
      });
  }

  function openEmptyDayModal(ymd) {
    const modal = document.getElementById("ajin-modal-new");
    const sub = document.getElementById("ajin-modal-new-sub");
    const links = document.getElementById("ajin-modal-new-links");
    if (!modal || !sub || !links) return;
    const d = parseYmd(ymd);
    sub.textContent = d
      ? d.toLocaleDateString("fr-FR", { weekday: "long", day: "numeric", month: "long", year: "numeric" })
      : ymd;
    const dayEvts = eventsForDay(ymd).filter((e) => (e.extendedProps || {}).kind === "departure");
    links.innerHTML = "";
    if (dayEvts.length === 0) {
      links.innerHTML = `<p class="text-sm text-gray-500 mb-3">Aucun départ programmé ce jour pour les filtres actuels.</p>`;
    } else {
      dayEvts.forEach((ev) => {
        const p = ev.extendedProps || {};
        const a = document.createElement("a");
        a.href = p.route_reserver || cfg.createUrl;
        a.className =
          "flex items-center gap-3 p-3 border border-gray-200 rounded-xl hover:border-[#0083c4] hover:bg-[#e6f3fa]/30 text-left";
        a.innerHTML = `<span class="w-10 h-10 rounded-full bg-[#e6f3fa] flex items-center justify-center text-[#0083c4]"><i class="fas fa-suitcase-rolling"></i></span>
          <span class="flex-grow"><span class="font-bold text-[#0e3a5a] text-sm block">${escapeHtml(ev.title)}</span><span class="text-xs text-gray-500">Réserver ce départ</span></span>
          <i class="fas fa-chevron-right text-gray-300"></i>`;
        links.appendChild(a);
      });
    }
    const generic = document.createElement("a");
    generic.href = cfg.createUrl;
    generic.className =
      "mt-3 block w-full text-center py-3 rounded-xl bg-[#0083c4] text-white font-bold text-sm hover:opacity-95";
    generic.textContent = "Nouvelle réservation";
    links.appendChild(generic);

    modal.classList.remove("hidden");
    modal.classList.add("flex");
  }

  function closeModals() {
    document.querySelectorAll(".ajin-cal-modal").forEach((m) => {
      m.classList.add("hidden");
      m.classList.remove("flex");
    });
  }

  document.getElementById("ajin-cal-prev")?.addEventListener("click", () => {
    viewMonth -= 1;
    if (viewMonth < 0) {
      viewMonth = 11;
      viewYear -= 1;
    }
    if (fp) fp.clear();
    hiddenFrom.value = "";
    hiddenTo.value = "";
    loadEvents();
  });
  document.getElementById("ajin-cal-next")?.addEventListener("click", () => {
    viewMonth += 1;
    if (viewMonth > 11) {
      viewMonth = 0;
      viewYear += 1;
    }
    if (fp) fp.clear();
    hiddenFrom.value = "";
    hiddenTo.value = "";
    loadEvents();
  });
  document.getElementById("ajin-cal-today")?.addEventListener("click", () => {
    const n = new Date();
    viewYear = n.getFullYear();
    viewMonth = n.getMonth();
    if (fp) fp.clear();
    hiddenFrom.value = "";
    hiddenTo.value = "";
    loadEvents();
  });

  document.getElementById("btn-apply-filters")?.addEventListener("click", () => {
    loadEvents();
  });

  document.querySelectorAll("[data-close-cal-modal]").forEach((b) => {
    b.addEventListener("click", closeModals);
  });

  document.querySelectorAll(".ajin-cal-modal").forEach((modal) => {
    modal.addEventListener("click", (e) => {
      if (e.target === modal) closeModals();
    });
  });

  loadEvents();
}

document.addEventListener("DOMContentLoaded", init);
