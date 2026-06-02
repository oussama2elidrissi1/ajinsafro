@once
    @push('styles')
        <style>
            #ws-voyage-detail-modal.ws-md-root{position:fixed;inset:0;z-index:99999;display:none;align-items:center;justify-content:center;padding:1rem;isolation:isolate}
            #ws-voyage-detail-modal.ws-md-root:not(.hidden){display:flex}
            .ws-md-overlay{position:absolute;inset:0;background:rgba(14,58,90,.52);backdrop-filter:blur(5px)}
            .ws-md-shell{position:relative;z-index:1;width:min(1120px,96vw);max-height:min(92vh,920px);display:flex;flex-direction:column;background:#f8fafc;border:1px solid rgba(226,232,240,.9);border-radius:20px;box-shadow:0 24px 70px rgba(15,23,42,.24);overflow:hidden;transform:translateY(10px) scale(.985);opacity:0;transition:transform .18s ease,opacity .18s ease}
            #ws-voyage-detail-modal.ws-md-visible .ws-md-shell{transform:translateY(0) scale(1);opacity:1}
            .ws-md-header{flex:0 0 auto;padding:1.25rem 1.5rem;border-bottom:1px solid #e2e8f0;background:linear-gradient(135deg,#ffffff 0%,#eef8fd 100%)}
            .ws-md-header-top{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem}
            .ws-md-title{font-size:1.2rem;font-weight:600;color:#0e3a5a;line-height:1.35;margin:0}
            .ws-md-meta{margin-top:.65rem;display:flex;flex-wrap:wrap;gap:.45rem .6rem;font-size:.76rem;color:#64748b}
            .ws-md-meta span,.ws-md-badge-status{display:inline-flex;align-items:center;gap:.35rem;padding:.28rem .62rem;border-radius:999px;background:#fff;border:1px solid #dbeafe;color:#334155}
            .ws-md-badge-status{background:#e6f3fa;color:#0083c4;border-color:rgba(0,131,196,.22);font-weight:600}
            .ws-md-close{width:2.55rem;height:2.55rem;display:inline-flex;align-items:center;justify-content:center;border-radius:14px;border:1px solid #dbe3ea;background:#fff;color:#64748b;cursor:pointer;transition:background .15s ease,color .15s ease,transform .15s ease}
            .ws-md-close:hover{background:#0e3a5a;color:#fff;transform:translateY(-1px)}
            .ws-md-body{flex:1 1 auto;min-height:0;overflow-y:auto;padding:1.25rem 1.5rem;background:#f8fafc}
            .ws-md-grid{display:grid;grid-template-columns:minmax(250px,.9fr) minmax(0,1.6fr);gap:1rem;align-items:start}
            .ws-md-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1rem;box-shadow:0 8px 22px rgba(15,23,42,.045)}
            .ws-md-section-head{display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:.85rem}
            .ws-md-section-title{display:flex;align-items:center;gap:.5rem;margin:0;color:#0e3a5a;font-size:.88rem;font-weight:600}
            .ws-md-section-title i{color:#0083c4}
            .ws-md-muted{color:#64748b;font-size:.78rem}
            .ws-md-date-list{display:flex;flex-direction:column;gap:.6rem}
            .ws-md-date-option{width:100%;display:flex;align-items:center;justify-content:space-between;gap:.8rem;padding:.78rem .85rem;border-radius:14px;border:1px solid #e2e8f0;background:#fff;color:#0f172a;text-align:left;cursor:pointer;transition:border-color .15s ease,background .15s ease,box-shadow .15s ease}
            .ws-md-date-option:hover{border-color:#93c5fd;background:#f8fcff}
            .ws-md-date-option.is-active{border-color:#0083c4;background:#e6f3fa;box-shadow:0 0 0 3px rgba(0,131,196,.1)}
            .ws-md-date-main{display:block;font-weight:600;color:#0e3a5a}
            .ws-md-date-sub{display:block;margin-top:.18rem;color:#64748b;font-size:.74rem}
            .ws-md-avail-badge{display:inline-flex;align-items:center;justify-content:center;white-space:nowrap;padding:.25rem .58rem;border-radius:999px;font-size:.7rem;font-weight:600;border:1px solid transparent}
            .ws-md-avail-badge--ok{background:#ecfdf5;color:#047857;border-color:#bbf7d0}
            .ws-md-avail-badge--warn{background:#fff7ed;color:#c2410c;border-color:#fed7aa}
            .ws-md-avail-badge--full{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
            .ws-md-avail-badge--unknown{background:#f1f5f9;color:#475569;border-color:#e2e8f0}
            .ws-md-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.7rem;margin-bottom:1rem}
            .ws-md-kpi{display:flex;gap:.65rem;align-items:center;border:1px solid #e2e8f0;border-radius:14px;padding:.75rem;background:#fff}
            .ws-md-kpi-icon{width:2.15rem;height:2.15rem;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;background:#e6f3fa;color:#0083c4}
            .ws-md-kpi span{display:block;color:#64748b;font-size:.72rem;font-weight:600}
            .ws-md-kpi strong{display:block;color:#0f172a;font-size:1.05rem;font-weight:600;line-height:1.1;margin-top:.15rem}
            .ws-md-kpi--ok .ws-md-kpi-icon{background:#ecfdf5;color:#047857}
            .ws-md-kpi--wait .ws-md-kpi-icon{background:#fff7ed;color:#c2410c}
            .ws-md-kpi--cancel .ws-md-kpi-icon{background:#fef2f2;color:#dc2626}
            .ws-md-kpi--blue .ws-md-kpi-icon{background:#e6f3fa;color:#0083c4}
            .ws-md-progress-wrap{border:1px solid #e2e8f0;border-radius:16px;padding:1rem;background:#fff;margin-bottom:1rem}
            .ws-md-progress-top{display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:.7rem;color:#0e3a5a;font-size:.85rem;font-weight:600}
            .ws-md-progress{height:.7rem;border-radius:999px;background:#e2e8f0;overflow:hidden}
            .ws-md-progress-bar{height:100%;width:0;background:linear-gradient(90deg,#0083c4,#0e3a5a);border-radius:999px;transition:width .18s ease}
            .ws-md-progress-copy{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.65rem;margin-top:.8rem;color:#64748b;font-size:.78rem}
            .ws-md-info-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem}
            .ws-md-info{border:1px solid #e2e8f0;border-radius:14px;padding:.75rem;background:#fbfdff}
            .ws-md-info span{display:block;color:#64748b;font-size:.72rem;font-weight:600}
            .ws-md-info strong{display:block;color:#0f172a;font-size:.9rem;font-weight:600;margin-top:.22rem}
            .ws-md-rooms{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem}
            .ws-md-room{border:1px solid #e2e8f0;border-radius:15px;background:#fff;padding:.85rem}
            .ws-md-room-head{display:flex;align-items:flex-start;justify-content:space-between;gap:.7rem;margin-bottom:.7rem}
            .ws-md-room-title{margin:0;color:#0e3a5a;font-size:.9rem;font-weight:600}
            .ws-md-room-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.55rem;color:#64748b;font-size:.75rem}
            .ws-md-room-grid strong{display:block;color:#0f172a;font-weight:600;margin-top:.12rem}
            .ws-md-empty{border:1px dashed #cbd5e1;border-radius:16px;background:#fff;padding:1.2rem;text-align:center;color:#64748b}
            .ws-md-empty i{display:block;color:#0083c4;font-size:1.5rem;margin-bottom:.35rem}
            .ws-md-footer{flex:0 0 auto;display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:1rem 1.5rem;border-top:1px solid #e2e8f0;background:#fff}
            .ws-md-footer-note{color:#64748b;font-size:.78rem}
            .ws-md-footer-actions{display:flex;gap:.6rem;flex-wrap:wrap;justify-content:flex-end}
            .ws-md-btn{display:inline-flex;align-items:center;justify-content:center;gap:.45rem;border-radius:12px;border:1px solid transparent;padding:.68rem .95rem;font-size:.83rem;font-weight:600;text-decoration:none;cursor:pointer;transition:background .15s ease,border-color .15s ease,color .15s ease}
            .ws-md-btn-secondary{background:#fff;border-color:#cbd5e1;color:#334155}
            .ws-md-btn-secondary:hover{background:#f8fafc;color:#0e3a5a}
            body.ws-md-open{overflow:hidden}
            @media(max-width:980px){.ws-md-grid{grid-template-columns:1fr}.ws-md-date-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}.ws-md-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}
            @media(max-width:640px){#ws-voyage-detail-modal.ws-md-root{padding:.65rem}.ws-md-shell{width:95vw;max-height:94vh;border-radius:16px}.ws-md-header,.ws-md-body,.ws-md-footer{padding:1rem}.ws-md-date-list,.ws-md-summary,.ws-md-info-grid,.ws-md-rooms,.ws-md-progress-copy{grid-template-columns:1fr}.ws-md-footer{align-items:stretch;flex-direction:column}.ws-md-footer-actions,.ws-md-btn{width:100%}}
        </style>
    @endpush

    <div id="ws-voyage-detail-modal" class="ws-md-root hidden" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ws-md-title">
        <div class="ws-md-overlay" data-ws-md-backdrop></div>
        <div class="ws-md-shell">
            <header class="ws-md-header">
                <div class="ws-md-header-top">
                    <div>
                        <h2 id="ws-md-title" class="ws-md-title">—</h2>
                        <div id="ws-md-sub" class="ws-md-meta"></div>
                    </div>
                    <button type="button" class="ws-md-close" data-ws-md-close aria-label="Fermer">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </div>
            </header>
            <div id="ws-md-body" class="ws-md-body"></div>
            <footer id="ws-md-footer" class="ws-md-footer"></footer>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var detailMapEl = document.getElementById('ws-modal-detail-json');
        var modalEl = document.getElementById('ws-voyage-detail-modal');
        var titleEl = document.getElementById('ws-md-title');
        var subEl = document.getElementById('ws-md-sub');
        var bodyEl = document.getElementById('ws-md-body');
        var footerEl = document.getElementById('ws-md-footer');
        var currentDetail = null;
        var currentDepartureIndex = 0;

        function details() {
            try { return JSON.parse((detailMapEl && detailMapEl.textContent) || '{}'); } catch (e) { return {}; }
        }

        function esc(value) {
            var div = document.createElement('div');
            div.textContent = value == null || value === '' ? '—' : String(value);
            return div.innerHTML;
        }

        function numberValue(value) {
            var parsed = parseInt(value, 10);
            return Number.isFinite(parsed) ? parsed : 0;
        }

        function pct(value) {
            return Math.max(0, Math.min(100, numberValue(value)));
        }

        function money(value, fallback) {
            var amount = Number(value || 0);
            if (!amount) return esc(fallback || 'Prix sur demande');
            return esc(new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(amount) + ' DH');
        }

        function statusBadge(key, label) {
            var className = 'ws-md-avail-badge ws-md-avail-badge--unknown';
            if (key === 'available' || key === 'open') className = 'ws-md-avail-badge ws-md-avail-badge--ok';
            if (key === 'almost_full' || key === 'limited') className = 'ws-md-avail-badge ws-md-avail-badge--warn';
            if (key === 'full' || key === 'closed' || key === 'inactive' || key === 'canceled' || key === 'cancelled') className = 'ws-md-avail-badge ws-md-avail-badge--full';
            return '<span class="' + className + '">' + esc(label || 'Disponible') + '</span>';
        }

        function kpi(icon, label, value, modifier) {
            return '<div class="ws-md-kpi ' + (modifier || '') + '"><span class="ws-md-kpi-icon"><i class="' + icon + '"></i></span><div><span>' + esc(label) + '</span><strong>' + esc(value) + '</strong></div></div>';
        }

        function renderDateSelector(detail, selectedIndex) {
            var departures = Array.isArray(detail.departures) ? detail.departures : [];
            if (!departures.length) {
                return '<section class="ws-md-card"><div class="ws-md-section-head"><h3 class="ws-md-section-title"><i class="fas fa-calendar-alt"></i>Choisir une date de départ</h3></div><div class="ws-md-empty"><i class="fas fa-calendar-times"></i>Aucune date de départ disponible.</div></section>';
            }

            var html = '<section class="ws-md-card"><div class="ws-md-section-head"><h3 class="ws-md-section-title"><i class="fas fa-calendar-alt"></i>Choisir une date de départ</h3><span class="ws-md-muted">' + departures.length + ' date(s)</span></div><div class="ws-md-date-list">';
            departures.forEach(function (departure, index) {
                html += '<button type="button" class="ws-md-date-option ' + (index === selectedIndex ? 'is-active' : '') + '" data-ws-md-date-index="' + index + '">';
                html += '<span><span class="ws-md-date-main">' + esc(departure.date_label) + '</span><span class="ws-md-date-sub">' + esc(departure.remaining != null ? departure.remaining + ' place(s) restantes' : 'Disponibilité à vérifier') + '</span></span>';
                html += statusBadge(departure.status_key, departure.status_label);
                html += '</button>';
            });
            return html + '</div></section>';
        }

        function renderDeparture(detail, departure) {
            if (!departure) {
                return '<section class="ws-md-card"><div class="ws-md-empty"><i class="fas fa-info-circle"></i>Sélectionnez une date pour voir les détails du départ.</div></section>';
            }

            var pax = departure.pax || {};
            var reservations = departure.reservations || {};
            var capacity = numberValue(departure.capacity);
            var remaining = numberValue(departure.remaining);
            var confirmed = numberValue(pax.validee);
            var pending = numberValue(pax.en_cours);
            var cancelled = numberValue(pax.annulee);
            var rate = pct(departure.fill_pct);
            var html = '<section class="ws-md-card"><div class="ws-md-section-head"><h3 class="ws-md-section-title"><i class="fas fa-chart-line"></i>Détail du départ sélectionné</h3>' + statusBadge(departure.status_key, departure.status_label) + '</div>';
            html += '<div class="ws-md-summary">';
            html += kpi('fas fa-users', 'Capacité', capacity ? capacity + ' places' : '—', '');
            html += kpi('fas fa-check-circle', 'Confirmées', confirmed + ' places', 'ws-md-kpi--ok');
            html += kpi('fas fa-hourglass-half', 'En attente', pending + ' places', 'ws-md-kpi--wait');
            html += kpi('fas fa-times-circle', 'Annulées', cancelled + ' places', 'ws-md-kpi--cancel');
            html += kpi('fas fa-chair', 'Restantes', remaining ? remaining + ' places' : '0 place', 'ws-md-kpi--blue');
            html += kpi('fas fa-percentage', 'Taux', rate + '%', '');
            html += '</div>';
            html += '<div class="ws-md-progress-wrap"><div class="ws-md-progress-top"><span>Taux de remplissage</span><span>' + rate + '%</span></div><div class="ws-md-progress"><div class="ws-md-progress-bar" style="width:' + rate + '%"></div></div>';
            html += '<div class="ws-md-progress-copy"><span>Confirmées : <strong>' + confirmed + '</strong> place(s) dans <strong>' + numberValue(reservations.validee) + '</strong> dossier(s)</span><span>En attente : <strong>' + pending + '</strong> place(s) dans <strong>' + numberValue(reservations.en_cours) + '</strong> dossier(s)</span></div></div>';
            html += renderInfo(detail, departure);
            return html + '</section>' + renderRooms(departure);
        }

        function renderInfo(detail, departure) {
            var fields = [
                ['Durée', detail.duration || '—'],
                ['Date sélectionnée', departure.date_label || '—'],
                ['Prix à partir de', departure.unit_price_label || money(departure.unit_price, detail.prices && detail.prices.adult_label)],
                ['Statut du départ', departure.status_label || 'Disponible'],
                ['Chambres configurées', Array.isArray(departure.rooms) ? departure.rooms.length + ' type(s)' : '0 type'],
                ['Destination', detail.destination || '—'],
                ['Ville de départ', detail.departure_city || '—'],
                ['Référence départ', departure.departure_id ? '#' + departure.departure_id : '—']
            ];
            var html = '<div class="ws-md-info-grid">';
            fields.forEach(function (field) {
                html += '<div class="ws-md-info"><span>' + esc(field[0]) + '</span><strong>' + esc(field[1]) + '</strong></div>';
            });
            return html + '</div>';
        }

        function renderRooms(departure) {
            var rooms = Array.isArray(departure.rooms) ? departure.rooms : [];
            var html = '<section class="ws-md-card"><div class="ws-md-section-head"><h3 class="ws-md-section-title"><i class="fas fa-bed"></i>Chambres disponibles</h3><span class="ws-md-muted">' + rooms.length + ' configuration(s)</span></div>';
            if (!rooms.length) {
                return html + '<div class="ws-md-empty"><i class="fas fa-bed"></i>Aucune chambre configurée pour ce départ.</div></section>';
            }

            html += '<div class="ws-md-rooms">';
            rooms.forEach(function (room) {
                html += '<article class="ws-md-room"><div class="ws-md-room-head"><h4 class="ws-md-room-title">' + esc(room.type || 'Chambre') + '</h4>' + statusBadge(room.is_available ? 'available' : 'full', room.is_available ? 'Disponible' : (room.status_label || 'Non disponible')) + '</div>';
                html += '<div class="ws-md-room-grid">';
                html += '<div>Capacité<strong>' + esc(room.capacity ? room.capacity + ' pers./chambre' : '—') + '</strong></div>';
                html += '<div>Places restantes<strong>' + esc(room.available_places != null ? room.available_places : '—') + '</strong></div>';
                html += '<div>Chambres restantes<strong>' + esc(room.available_rooms != null ? room.available_rooms : '—') + '</strong></div>';
                html += '<div>Supplément<strong>' + esc(room.supplement > 0 ? room.supplement + ' DH' : 'Inclus') + '</strong></div>';
                html += '</div></article>';
            });
            return html + '</div></section>';
        }

        function render(detail, selectedIndex) {
            var departures = Array.isArray(detail.departures) ? detail.departures : [];
            var departure = departures[selectedIndex] || departures[0] || null;
            return '<div class="ws-md-grid"><div>' + renderDateSelector(detail, selectedIndex) + '</div><div>' + renderDeparture(detail, departure) + '</div></div>';
        }

        function open(code, travelDateId) {
            var map = details();
            var detail = map[code];
            if (!detail || !modalEl) return;
            currentDetail = detail;
            var departures = Array.isArray(detail.departures) ? detail.departures : [];
            currentDepartureIndex = Math.max(0, departures.findIndex(function (departure) {
                return travelDateId && String(departure.travel_date_id) === String(travelDateId);
            }));
            if (currentDepartureIndex < 0) currentDepartureIndex = 0;
            titleEl.textContent = detail.title || '—';
            var bits = [];
            if (detail.post_status_label) bits.push('<span class="ws-md-badge-status"><i class="fas fa-check-circle"></i>' + esc(detail.post_status_label) + '</span>');
            if (detail.wp_post_id) bits.push('<span>WP #' + esc(detail.wp_post_id) + '</span>');
            if (detail.laravel_voyage_id) bits.push('<span>Laravel #' + esc(detail.laravel_voyage_id) + '</span>');
            subEl.innerHTML = bits.join('');
            bodyEl.innerHTML = render(detail, currentDepartureIndex);
            footerEl.innerHTML = '<span class="ws-md-footer-note">Aucune route Agent de réservation directe n’est configurée pour ce départ.</span><div class="ws-md-footer-actions"><button type="button" class="ws-md-btn ws-md-btn-secondary" data-ws-md-close><i class="fas fa-times"></i>Fermer</button></div>';
            modalEl.classList.remove('hidden');
            modalEl.classList.add('ws-md-visible');
            modalEl.setAttribute('aria-hidden', 'false');
            document.body.classList.add('ws-md-open');
        }

        function close() {
            if (!modalEl) return;
            modalEl.classList.remove('ws-md-visible');
            modalEl.classList.add('hidden');
            modalEl.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('ws-md-open');
        }

        document.addEventListener('click', function (event) {
            var target = event.target && event.target.closest ? event.target.closest('.btn-ws-open-detail,[data-ws-md-close],[data-ws-md-backdrop],[data-ws-md-date-index]') : null;
            if (!target) return;
            if (target.classList.contains('btn-ws-open-detail')) {
                event.preventDefault();
                open(target.getAttribute('data-row-code') || '', target.getAttribute('data-travel-date-id') || '');
                return;
            }
            if (target.hasAttribute('data-ws-md-date-index')) {
                event.preventDefault();
                if (!currentDetail) return;
                currentDepartureIndex = numberValue(target.getAttribute('data-ws-md-date-index'));
                bodyEl.innerHTML = render(currentDetail, currentDepartureIndex);
                return;
            }
            if (target.hasAttribute('data-ws-md-close') || target.hasAttribute('data-ws-md-backdrop')) {
                event.preventDefault();
                close();
            }
        }, true);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') close();
        });
    });
    </script>
@endonce
