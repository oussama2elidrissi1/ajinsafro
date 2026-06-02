@once
    @push('styles')
        <style>
            #ws-voyage-detail-modal.ws-md-root{position:fixed;inset:0;z-index:99999;display:none;align-items:center;justify-content:center;padding:1rem;isolation:isolate}
            #ws-voyage-detail-modal.ws-md-root:not(.hidden){display:flex}
            .ws-md-overlay{position:absolute;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(4px)}
            .ws-md-shell{position:relative;z-index:1;width:100%;max-width:900px;max-height:min(94vh,900px);display:flex;flex-direction:column;background:#fff;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.22);overflow:hidden;transform:scale(.97);opacity:0;transition:transform .2s ease,opacity .2s ease}
            #ws-voyage-detail-modal.ws-md-visible .ws-md-shell{transform:scale(1);opacity:1}
            .ws-md-header{padding:1.25rem 1.5rem;border-bottom:1px solid #e8ecf1;background:linear-gradient(180deg,#fafbfc,#fff)}
            .ws-md-header-top{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem}
            .ws-md-title{font-size:1.125rem;font-weight:800;color:#0e3a5a;line-height:1.35;margin:0}
            .ws-md-meta{margin-top:.5rem;display:flex;flex-wrap:wrap;gap:.5rem .75rem;font-size:.75rem;color:#64748b}
            .ws-md-badge-status{display:inline-flex;align-items:center;padding:.2rem .55rem;border-radius:999px;font-size:.65rem;font-weight:800;text-transform:uppercase;background:#e6f3fa;color:#0083c4;border:1px solid rgba(0,131,196,.2)}
            .ws-md-close{width:2.5rem;height:2.5rem;display:inline-flex;align-items:center;justify-content:center;border-radius:12px;border:1px solid #e2e8f0;background:#fff;color:#64748b;cursor:pointer}
            .ws-md-body{flex:1;min-height:0;overflow-y:auto;padding:1.25rem 1.5rem 1.5rem;background:#f8fafc}
            .ws-md-body-inner{display:flex;flex-direction:column;gap:1rem}
            .ws-md-card{background:#fff;border:1px solid #e8ecf1;border-radius:12px;padding:1rem 1.15rem;box-shadow:0 1px 3px rgba(14,58,90,.04)}
            .ws-md-section-head{display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#64748b}
            .ws-md-section-head i{color:#0083c4}
            .ws-md-dl{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.65rem 1rem;font-size:.875rem}
            .ws-md-dl dt{color:#94a3b8;font-weight:600;font-size:.75rem}.ws-md-dl dd{margin:0;font-weight:700;color:#0f172a}
            .ws-md-date-pills,.ws-md-stats-row{display:flex;flex-wrap:wrap;gap:.5rem}
            .ws-md-date-pill,.ws-md-stat-pill{display:inline-flex;align-items:center;gap:.45rem;padding:.45rem .75rem;border-radius:10px;border:1px solid #e2e8f0;background:#f8fafc;font-size:.8125rem;font-weight:700;color:#1e293b}
            .ws-md-stat-pill.ok{background:#ecfdf5;color:#047857;border-color:#a7f3d0}.ws-md-stat-pill.wait{background:#fffbeb;color:#b45309;border-color:#fde68a}.ws-md-stat-pill.cancel{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
            .ws-md-departure-list{display:grid;grid-template-columns:1fr;gap:.75rem}.ws-md-departure-card{border:1px solid #e2e8f0;border-radius:12px;padding:.9rem;background:#fff}
            .ws-md-departure-card-head{display:flex;align-items:center;justify-content:space-between;gap:.75rem}.ws-md-departure-date{font-weight:800;color:#0e3a5a}
            .ws-md-departure-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.5rem;margin-top:.75rem}.ws-md-dep-kpi{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:.65rem}.ws-md-dep-kpi span{display:block;font-size:.68rem;color:#64748b;font-weight:700}.ws-md-dep-kpi strong{display:block;margin-top:.2rem;color:#0f172a;font-weight:800}
            .ws-md-progress{height:.55rem;border-radius:999px;background:#e2e8f0;overflow:hidden;margin-top:.75rem}.ws-md-progress-bar{height:100%;background:#0083c4}
            .ws-md-avail-badge{display:inline-flex;padding:.25rem .55rem;border-radius:999px;font-size:.72rem;font-weight:800}.ws-md-avail-badge--ok{background:#ecfdf5;color:#047857}.ws-md-avail-badge--warn{background:#fffbeb;color:#b45309}.ws-md-avail-badge--full{background:#fef2f2;color:#b91c1c}.ws-md-avail-badge--unknown{background:#f1f5f9;color:#475569}
            .ws-md-footer{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:1rem 1.5rem;border-top:1px solid #e8ecf1;background:#fff}.ws-md-footer-actions{display:flex;gap:.6rem;flex-wrap:wrap}
            .ws-md-btn{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;border-radius:10px;border:1px solid transparent;padding:.65rem .9rem;font-size:.82rem;font-weight:800;text-decoration:none;cursor:pointer}.ws-md-btn-secondary{background:#fff;border-color:#cbd5e1;color:#334155}
            body.ws-md-open{overflow:hidden}
            @media(max-width:640px){.ws-md-dl,.ws-md-departure-kpis{grid-template-columns:1fr}.ws-md-footer{flex-direction:column;align-items:stretch}.ws-md-btn{width:100%}}
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
        function details(){try{return JSON.parse((detailMapEl && detailMapEl.textContent) || '{}')}catch(e){return {}}}
        function esc(value){var d=document.createElement('div');d.textContent=value == null ? '' : String(value);return d.innerHTML}
        function badge(dep){var k=dep.status_key||'unknown',c='ws-md-avail-badge ws-md-avail-badge--unknown';if(k==='available')c='ws-md-avail-badge ws-md-avail-badge--ok';else if(k==='almost_full')c='ws-md-avail-badge ws-md-avail-badge--warn';else if(k==='full')c='ws-md-avail-badge ws-md-avail-badge--full';return '<span class="'+c+'">'+esc(dep.status_label||'Disponible')+'</span>'}
        function render(detail){
            var html='<div class="ws-md-body-inner">';
            html+='<section class="ws-md-card"><div class="ws-md-section-head"><i class="fas fa-info-circle"></i> Informations générales</div><dl class="ws-md-dl">';
            if(detail.destination) html+='<div><dt>Destination</dt><dd>'+esc(detail.destination)+'</dd></div>';
            if(detail.duration) html+='<div><dt>Durée</dt><dd>'+esc(detail.duration)+'</dd></div>';
            html+='<div><dt>Prix à partir de</dt><dd>'+esc((detail.prices&&detail.prices.adult_label)||'—')+'</dd></div></dl></section>';
            if(Array.isArray(detail.travel_dates)&&detail.travel_dates.length){html+='<section class="ws-md-card"><div class="ws-md-section-head"><i class="fas fa-calendar-alt"></i> Dates de disponibilité</div><div class="ws-md-date-pills">';detail.travel_dates.forEach(function(td){html+='<span class="ws-md-date-pill">'+esc(td.date_label||'—')+'</span>'});html+='</div></section>'}
            if(Array.isArray(detail.departures)&&detail.departures.length){html+='<section class="ws-md-card"><div class="ws-md-section-head"><i class="fas fa-route"></i> Disponibilités par départ</div><div class="ws-md-departure-list">';detail.departures.forEach(function(dep){var pax=dep.pax||{},rs=dep.reservations||{};html+='<article class="ws-md-departure-card"><div class="ws-md-departure-card-head"><div class="ws-md-departure-date">'+esc(dep.date_label||'—')+'</div>'+badge(dep)+'</div><div class="ws-md-departure-kpis">';html+='<div class="ws-md-dep-kpi"><span>Capacité</span><strong>'+esc(dep.capacity!=null?dep.capacity:'—')+'</strong></div>';html+='<div class="ws-md-dep-kpi"><span>Confirmées</span><strong>'+esc(pax.validee||0)+'</strong></div>';html+='<div class="ws-md-dep-kpi"><span>En attente</span><strong>'+esc(pax.en_cours||0)+'</strong></div>';html+='<div class="ws-md-dep-kpi"><span>Annulées</span><strong>'+esc(pax.annulee||0)+'</strong></div>';html+='<div class="ws-md-dep-kpi"><span>Total dossiers</span><strong>'+esc(rs.total||0)+'</strong></div>';html+='<div class="ws-md-dep-kpi"><span>Places restantes</span><strong>'+esc(dep.remaining!=null?dep.remaining:'—')+'</strong></div>';html+='</div><div class="ws-md-progress"><div class="ws-md-progress-bar" style="width:'+esc(dep.fill_pct||0)+'%"></div></div></article>'});html+='</div></section>'}
            if(detail.stats){html+='<section class="ws-md-card"><div class="ws-md-section-head"><i class="fas fa-chart-bar"></i> Réservations</div><div class="ws-md-stats-row"><span class="ws-md-stat-pill ok"><i class="fas fa-check-circle"></i> '+esc(detail.stats.validee||0)+' confirmées</span><span class="ws-md-stat-pill wait"><i class="fas fa-hourglass-half"></i> '+esc(detail.stats.en_cours||0)+' en attente</span><span class="ws-md-stat-pill cancel"><i class="fas fa-times-circle"></i> '+esc(detail.stats.annulee||0)+' annulées</span></div></section>'}
            return html+'</div>';
        }
        function open(code){var d=details()[code];if(!d||!modalEl)return;titleEl.textContent=d.title||'—';var bits=[];if(d.post_status_label)bits.push('<span class="ws-md-badge-status">'+esc(d.post_status_label)+'</span>');if(d.wp_post_id)bits.push('<span>WP #'+esc(d.wp_post_id)+'</span>');if(d.laravel_voyage_id)bits.push('<span>Laravel #'+esc(d.laravel_voyage_id)+'</span>');subEl.innerHTML=bits.join('');bodyEl.innerHTML=render(d);footerEl.innerHTML='<button type="button" class="ws-md-btn ws-md-btn-secondary" data-ws-md-close><i class="fas fa-times"></i> Fermer</button><div class="ws-md-footer-actions"></div>';modalEl.classList.remove('hidden');modalEl.classList.add('ws-md-visible');modalEl.setAttribute('aria-hidden','false');document.body.classList.add('ws-md-open')}
        function close(){if(!modalEl)return;modalEl.classList.remove('ws-md-visible');modalEl.classList.add('hidden');modalEl.setAttribute('aria-hidden','true');document.body.classList.remove('ws-md-open')}
        document.addEventListener('click',function(e){var target=e.target&&e.target.closest?e.target.closest('.btn-ws-open-detail,[data-ws-md-close],[data-ws-md-backdrop]'):null;if(!target)return;if(target.classList.contains('btn-ws-open-detail')){e.preventDefault();open(target.getAttribute('data-row-code')||'');return}if(target.hasAttribute('data-ws-md-close')||target.hasAttribute('data-ws-md-backdrop')){e.preventDefault();close()}},true);
        document.addEventListener('keydown',function(e){if(e.key==='Escape')close()});
    });
    </script>
@endonce
