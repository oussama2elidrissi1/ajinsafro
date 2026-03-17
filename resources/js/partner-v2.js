import "flatpickr/dist/flatpickr.min.css";
import flatpickr from "flatpickr";
import { French } from "flatpickr/dist/l10n/fr.js";

import Chart from "chart.js/auto";

function qs(sel, root = document) {
  return root.querySelector(sel);
}

function qsa(sel, root = document) {
  return Array.from(root.querySelectorAll(sel));
}

function initDateRangePicker() {
  const el = qs("#date-range-picker");
  if (!el) return;
  flatpickr(el, {
    mode: "range",
    dateFormat: "d/m/Y",
    locale: French,
    showMonths: 1,
  });
}

function initDestinationsChart() {
  const canvas = qs("#destinationsChart");
  if (!canvas) return;
  const labels = JSON.parse(canvas.dataset.labels || "[]");
  const values = JSON.parse(canvas.dataset.values || "[]");
  if (!labels.length || !values.length) return;

  new Chart(canvas, {
    type: "doughnut",
    data: {
      labels,
      datasets: [
        {
          data: values,
          backgroundColor: ["#0083c4", "#f37a1f", "#10b981", "#8b5cf6", "#f3f4f6"],
          borderWidth: 0,
          hoverOffset: 4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: "70%",
      plugins: {
        legend: {
          position: "right",
          labels: {
            usePointStyle: true,
            padding: 15,
            font: { family: "Poppins, sans-serif", size: 11 },
          },
        },
      },
    },
  });
}

function initMobileMenu() {
  const btn = qs("#mobile-menu-btn");
  const overlay = qs("#mobile-menu");
  const drawer = qs("#mobile-menu-drawer");
  const closeBtn = qs("#close-menu-btn");
  if (!btn || !overlay || !drawer) return;

  function open() {
    overlay.classList.remove("opacity-0", "pointer-events-none");
    drawer.classList.remove("translate-x-full");
  }
  function close() {
    overlay.classList.add("opacity-0", "pointer-events-none");
    drawer.classList.add("translate-x-full");
  }

  btn.addEventListener("click", open);
  if (closeBtn) closeBtn.addEventListener("click", close);
  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) close();
  });
}

function initAjinsafroDrawer() {
  const burger = qs("#aj-burger");
  const drawer = qs("#aj-drawer");
  const closeBtn = qs("#aj-drawer-close");
  const navMenu = qs("#aj-nav-menu");
  if (!burger || !drawer) return;

  function openDrawer() {
    document.body.classList.add("menu-open");
    document.body.style.overflow = "hidden";
    drawer.classList.add("aj-menu-open");
    drawer.setAttribute("aria-hidden", "false");
    burger.setAttribute("aria-expanded", "true");
  }

  function closeDrawer() {
    document.body.classList.remove("menu-open");
    document.body.style.overflow = "";
    drawer.classList.remove("aj-menu-open");
    drawer.setAttribute("aria-hidden", "true");
    burger.setAttribute("aria-expanded", "false");
  }

  burger.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (document.body.classList.contains("menu-open")) closeDrawer();
    else openDrawer();
  });

  if (closeBtn) {
    closeBtn.addEventListener("click", (e) => {
      e.preventDefault();
      closeDrawer();
    });
  }

  if (navMenu) {
    navMenu.addEventListener("click", (e) => {
      const li = e.target.closest("li.aj-has-sub");
      if (!li || !li.querySelector(".aj-sub-menu")) return;
      const link = li.querySelector(":scope > a");
      if (link && link.contains(e.target) && window.innerWidth < 1280) {
        e.preventDefault();
        li.classList.toggle("aj-sub-open");
      }
    });
  }

  window.addEventListener("resize", () => {
    if (window.innerWidth >= 1280 && document.body.classList.contains("menu-open")) {
      closeDrawer();
    }
  });
}

function initPartnerNav() {
  qsa("[data-partner-nav]").forEach((a) => {
    a.addEventListener("click", () => {
      // no-op for server-side navigation, but keeps hook if needed later
    });
  });
}

document.addEventListener("DOMContentLoaded", () => {
  initDateRangePicker();
  initDestinationsChart();
  initMobileMenu();
  initAjinsafroDrawer();
  initPartnerNav();
});

