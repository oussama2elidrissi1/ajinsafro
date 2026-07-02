@once
@auth
    @php
        $supportRouteExists = \Illuminate\Support\Facades\Route::has('support.reclamations.store');
        $devRouteExists = \Illuminate\Support\Facades\Route::has('admin.dev.reclamations.index');
        $supportUser = auth()->user();
        $isDevSupport = strtolower(trim((string) ($supportUser?->email ?? ''))) === 'dev@ajinsafro.ma';
        $supportInVoyageStudio = request()->routeIs('admin.circuits.voyages.create', 'admin.circuits.voyages.edit-v2');
        $myDevReclamations = collect();
        $myDevReclamationsCount = 0;

        try {
            if ($supportUser && \Illuminate\Support\Facades\Schema::hasTable('dev_reclamations')) {
                $myDevReclamationsCount = \App\Models\DevReclamation::query()
                    ->where('user_id', $supportUser->id)
                    ->count();
                $myDevReclamations = \App\Models\DevReclamation::query()
                    ->where('user_id', $supportUser->id)
                    ->latest()
                    ->limit(3)
                    ->get();
            }
        } catch (\Throwable) {
            $myDevReclamations = collect();
            $myDevReclamationsCount = 0;
        }
    @endphp

    @if($supportRouteExists)
        <div class="dev-support-widget {{ $supportInVoyageStudio ? 'dev-support-widget--voyage-studio' : '' }}">
            @if($devRouteExists && $isDevSupport)
                <a class="dev-support-widget__dev-link" href="{{ route('admin.dev.reclamations.index') }}">
                    <i class="bx bx-list-check"></i>
                    Reclamations dev
                </a>
            @endif

            <button type="button" class="dev-support-widget__button" data-dev-reclamation-open>
                <i class="bx bx-message-square-error"></i>
                <span>Signaler un probleme</span>
            </button>
        </div>

        <div class="dev-support-modal" id="devSupportModal" aria-hidden="true">
            <div class="dev-support-modal__backdrop" data-dev-reclamation-close></div>
            <div class="dev-support-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="devSupportModalTitle">
                <div class="dev-support-modal__header">
                    <div>
                        <h5 id="devSupportModalTitle">Envoyer une reclamation au dev</h5>
                        <p>Expliquez le probleme et joignez une capture si necessaire.</p>
                    </div>
                    <button type="button" class="dev-support-modal__close" data-dev-reclamation-close aria-label="Fermer">
                        <i class="bx bx-x"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('support.reclamations.store') }}" enctype="multipart/form-data" class="dev-support-form">
                    @csrf
                    <input type="hidden" name="page_url" value="{{ url()->current() }}">

                    <label>
                        <span>Sujet</span>
                        <input type="text" name="subject" maxlength="160" placeholder="Ex. Probleme sur la page reservation">
                    </label>

                    <label>
                        <span>Message</span>
                        <textarea name="message" rows="6" required placeholder="Decrivez le probleme, la page concernee, et ce qui devait se passer."></textarea>
                    </label>

                    <label>
                        <span>Image / capture</span>
                        <input type="file" name="attachment" accept="image/*">
                    </label>

                    <div class="dev-support-history">
                        <div class="dev-support-history__head">
                            <span>Mes reclamations</span>
                            <a href="{{ route('support.reclamations.index') }}">
                                {{ $myDevReclamationsCount }} total
                            </a>
                        </div>

                        @if($myDevReclamations->isNotEmpty())
                            <div class="dev-support-history__list">
                                @foreach($myDevReclamations as $historyReclamation)
                                    <a class="dev-support-history__item" href="{{ route('support.reclamations.show', $historyReclamation) }}">
                                        <span class="dev-support-history__title">
                                            {{ \Illuminate\Support\Str::limit($historyReclamation->subject ?: 'Sans sujet', 44) }}
                                        </span>
                                        <span class="dev-support-history__meta">
                                            <span class="dev-support-history__status dev-support-history__status--{{ $historyReclamation->status }}">
                                                {{ $historyReclamation->status_label }}
                                            </span>
                                            <span>{{ $historyReclamation->created_at?->format('d/m/Y H:i') }}</span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="dev-support-history__empty">Aucune reclamation envoyee avec ce compte.</div>
                        @endif
                    </div>

                    <div class="dev-support-form__footer">
                        <a href="{{ route('support.reclamations.index') }}">Voir toutes mes reclamations</a>
                        <button type="submit">
                            <i class="bx bx-send"></i>
                            Envoyer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <style>
            .dev-support-widget{position:fixed;right:22px;bottom:22px;z-index:2140;display:flex;align-items:flex-end;gap:10px;flex-direction:column}
            .dev-support-widget--voyage-studio{bottom:92px}
            .dev-support-widget__button,.dev-support-widget__dev-link{border:0;border-radius:999px;box-shadow:0 18px 40px rgba(15,23,42,.22);font-weight:800;text-decoration:none;display:inline-flex;align-items:center;gap:8px;letter-spacing:0}
            .dev-support-widget__button{background:#ff5b1a;color:#fff;padding:13px 18px}
            .dev-support-widget__dev-link{background:#0f3150;color:#fff;padding:10px 14px;font-size:13px}
            .dev-support-widget__button:hover,.dev-support-widget__dev-link:hover{color:#fff;transform:translateY(-1px)}
            .dev-support-widget__button i,.dev-support-widget__dev-link i{font-size:18px}
            .dev-support-modal{position:fixed;inset:0;z-index:2150;display:none}
            .dev-support-modal.is-open{display:block}
            .dev-support-modal__backdrop{position:absolute;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(3px)}
            .dev-support-modal__dialog{position:absolute;right:24px;bottom:88px;width:min(520px,calc(100vw - 32px));background:#fff;border-radius:18px;box-shadow:0 30px 80px rgba(15,23,42,.32);overflow:hidden;border:1px solid #e2e8f0}
            .dev-support-widget--voyage-studio + .dev-support-modal .dev-support-modal__dialog{bottom:154px}
            .dev-support-modal__header{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:20px 22px;background:#f8fafc;border-bottom:1px solid #e2e8f0}
            .dev-support-modal__header h5{margin:0;color:#0f3150;font-size:18px;font-weight:800}
            .dev-support-modal__header p{margin:4px 0 0;color:#64748b;font-size:13px}
            .dev-support-modal__close{border:0;background:#fff;border-radius:999px;width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;color:#334155}
            .dev-support-form{display:grid;gap:15px;padding:20px 22px}
            .dev-support-form label{display:grid;gap:7px;margin:0;color:#0f3150;font-size:13px;font-weight:700}
            .dev-support-form input,.dev-support-form textarea{width:100%;border:1px solid #cbd5e1;border-radius:12px;padding:11px 13px;font-size:14px;color:#0f172a;outline:none}
            .dev-support-form input:focus,.dev-support-form textarea:focus{border-color:#0ea5e9;box-shadow:0 0 0 3px rgba(14,165,233,.14)}
            .dev-support-history{border:1px solid #e2e8f0;border-radius:14px;background:#f8fafc;padding:12px}
            .dev-support-history__head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:9px;color:#0f3150;font-size:13px;font-weight:800}
            .dev-support-history__head a{color:#0f3150;text-decoration:none;font-size:12px;font-weight:800}
            .dev-support-history__list{display:grid;gap:7px}
            .dev-support-history__item{display:grid;gap:4px;padding:10px 11px;border:1px solid #e2e8f0;border-radius:11px;background:#fff;text-decoration:none}
            .dev-support-history__item:hover{border-color:#0ea5e9;box-shadow:0 8px 20px rgba(15,23,42,.08)}
            .dev-support-history__title{color:#0f172a;font-size:13px;font-weight:800;line-height:1.25}
            .dev-support-history__meta{display:flex;align-items:center;justify-content:space-between;gap:8px;color:#64748b;font-size:11px}
            .dev-support-history__status{display:inline-flex;align-items:center;border-radius:999px;padding:3px 8px;font-size:10px;font-weight:900;text-transform:uppercase}
            .dev-support-history__status--ouverte{background:#e0f2fe;color:#075985}
            .dev-support-history__status--en_cours{background:#fef3c7;color:#92400e}
            .dev-support-history__status--traitee{background:#dcfce7;color:#166534}
            .dev-support-history__empty{padding:10px 11px;border:1px dashed #cbd5e1;border-radius:11px;color:#64748b;background:#fff;font-size:12px}
            .dev-support-form__footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:4px}
            .dev-support-form__footer a{color:#0f3150;font-weight:700;text-decoration:none;font-size:13px}
            .dev-support-form__footer button{border:0;border-radius:12px;background:#0f3150;color:#fff;padding:11px 16px;font-weight:800;display:inline-flex;align-items:center;gap:8px}
            @media (max-width:640px){.dev-support-widget{right:14px;bottom:14px}.dev-support-widget--voyage-studio{bottom:86px}.dev-support-widget__button span{display:none}.dev-support-modal__dialog{right:16px;bottom:74px}.dev-support-widget--voyage-studio + .dev-support-modal .dev-support-modal__dialog{bottom:142px}}
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modal = document.getElementById('devSupportModal');
                if (!modal) return;

                document.querySelectorAll('[data-dev-reclamation-open]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        modal.classList.add('is-open');
                        modal.setAttribute('aria-hidden', 'false');
                    });
                });

                document.querySelectorAll('[data-dev-reclamation-close]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        modal.classList.remove('is-open');
                        modal.setAttribute('aria-hidden', 'true');
                    });
                });
            });
        </script>
    @endif
@endauth
@endonce
