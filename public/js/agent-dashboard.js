(function () {
  function parseJsonAttr(el, name, fallback) {
    try {
      var raw = el.getAttribute(name);
      return raw ? JSON.parse(raw) : fallback;
    } catch (e) {
      return fallback;
    }
  }

  function initBookingsChart() {
    var canvas = document.getElementById('agentDashboardBookingsChart');
    if (!canvas || !window.Chart) return;

    var labels = parseJsonAttr(canvas, 'data-labels', []);
    var series = parseJsonAttr(canvas, 'data-series', []);

    var ctx = canvas.getContext('2d');
    if (!ctx) return;

    // eslint-disable-next-line no-new
    new window.Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Réservations',
            data: series,
            backgroundColor: 'rgba(0,131,196,0.18)',
            borderColor: 'rgba(0,131,196,0.85)',
            borderWidth: 1.5,
            borderRadius: 10,
            maxBarThickness: 42
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                return ' ' + (ctx.raw || 0) + ' réservation(s)';
              }
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: {
              color: '#64748b',
              maxRotation: 0,
              autoSkip: true,
              callback: function (value) {
                var label = (this.getLabelForValue ? this.getLabelForValue(value) : '') || '';
                return label.length > 22 ? label.slice(0, 22) + '…' : label;
              }
            }
          },
          y: {
            beginAtZero: true,
            ticks: { color: '#64748b', precision: 0 },
            grid: { color: 'rgba(148,163,184,0.25)' }
          }
        }
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initBookingsChart();
  });
})();

