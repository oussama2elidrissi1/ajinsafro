@php
    $supportUser = auth()->user();
    $supportIsClient = $supportUser && method_exists($supportUser, 'isClientPortal') && $supportUser->isClientPortal();
    $supportIsPartner = $supportUser && method_exists($supportUser, 'isPartner') && $supportUser->isPartner();
    $supportUsesAgentPortal = !$supportIsClient
        && !$supportIsPartner
        && \App\Services\View\AgentPortalLayout::shouldUse($supportUser);
    $supportLayout = $supportIsClient
        ? 'client.layout'
        : ($supportIsPartner ? 'layouts.partner-v2' : ($supportUsesAgentPortal ? 'layouts.master-ajinsafro' : 'layouts.admin-v6'));
@endphp

@extends($supportLayout)

@section('title', 'Reclamation')
@section('page_title', 'Reclamation')

@section('content')
<div class="support-reclamation-page">
    <div class="support-reclamation-shell">
        <div class="support-reclamation-header">
            <div class="support-reclamation-title">
                <span class="support-reclamation-kicker">Reclamation au dev</span>
                <h1>{{ $reclamation->subject ?: 'Reclamation' }}</h1>
                <div class="support-reclamation-meta">
                    <span>Envoyee le {{ $reclamation->created_at->format('d/m/Y H:i') }}</span>
                    <span class="support-status support-status--{{ $reclamation->status }}">{{ $reclamation->status_label }}</span>
                </div>
            </div>
            <a href="{{ route('support.reclamations.index') }}" class="support-reclamation-back">
                <i class="bx bx-arrow-back"></i>
                Retour
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="support-reclamation-grid">
            <section class="support-card support-card--message">
                <div class="support-card-head">
                    <div>
                        <span class="support-card-kicker">Message envoye</span>
                        <h2>Probleme signale</h2>
                    </div>
                </div>
                <div class="support-card-body">
                    <p class="support-message">{{ $reclamation->message }}</p>

                    @if($reclamation->page_url)
                        <a class="support-page-link" href="{{ $reclamation->page_url }}" target="_blank" rel="noopener">
                            <i class="bx bx-link-external"></i>
                            <span>{{ $reclamation->page_url }}</span>
                        </a>
                    @endif

                    @if($reclamation->attachment_url)
                        <a class="support-attachment" href="{{ $reclamation->attachment_url }}" target="_blank" rel="noopener">
                            <img src="{{ $reclamation->attachment_url }}" alt="Capture jointe">
                        </a>
                    @endif
                </div>
            </section>

            <aside class="support-card support-card--response">
                <div class="support-card-head">
                    <div>
                        <span class="support-card-kicker">Traitement dev</span>
                        <h2>Reponse et statut</h2>
                    </div>
                </div>
                <div class="support-card-body">
                    @if($reclamation->dev_response)
                        <p class="support-message">{{ $reclamation->dev_response }}</p>
                        <div class="support-response-footer">
                            <span>Traitee par {{ $reclamation->handler?->name ?? 'Dev' }}</span>
                            @if($reclamation->handled_at)
                                <strong>{{ $reclamation->handled_at->format('d/m/Y H:i') }}</strong>
                            @endif
                        </div>
                    @else
                        <div class="support-empty-response">
                            <i class="bx bx-time-five"></i>
                            <span>Le dev n'a pas encore ajoute de reponse.</span>
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</div>

<style>
    .support-reclamation-page{position:relative;z-index:1;padding:24px 28px 56px;background:#f3f7fb;min-height:calc(100vh - 120px)}
    .support-reclamation-shell{width:100%;max-width:none;margin:0}
    .support-reclamation-header{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:18px;padding:20px 22px;border:1px solid #dbe6f2;border-radius:16px;background:#fff;box-shadow:0 14px 34px rgba(15,23,42,.06)}
    .support-reclamation-kicker,.support-card-kicker{display:block;margin-bottom:5px;color:#64748b;font-size:11px;font-weight:900;letter-spacing:.04em;text-transform:uppercase}
    .support-reclamation-title h1{margin:0;color:#102a43;font-size:26px;font-weight:900;line-height:1.18}
    .support-reclamation-meta{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-top:8px;color:#64748b;font-size:13px}
    .support-status{display:inline-flex;align-items:center;border-radius:999px;padding:5px 10px;font-size:11px;font-weight:900;text-transform:uppercase}
    .support-status--ouverte{background:#e0f2fe;color:#075985}
    .support-status--en_cours{background:#fef3c7;color:#92400e}
    .support-status--traitee{background:#dcfce7;color:#166534}
    .support-reclamation-back{display:inline-flex;align-items:center;gap:7px;border:1px solid #c7d7ea;border-radius:12px;padding:10px 14px;background:#fff;color:#0f3150;font-size:13px;font-weight:900;text-decoration:none;white-space:nowrap}
    .support-reclamation-back:hover{border-color:#0ea5e9;color:#0f3150;box-shadow:0 8px 22px rgba(14,165,233,.12)}
    .support-reclamation-grid{display:grid;grid-template-columns:minmax(0,2fr) minmax(420px,1fr);gap:18px;align-items:start}
    .support-card{overflow:hidden;border:1px solid #dbe6f2;border-radius:16px;background:#fff;box-shadow:0 14px 34px rgba(15,23,42,.06)}
    .support-card--response{min-width:0}
    .support-card-head{display:flex;align-items:center;justify-content:space-between;padding:17px 20px;border-bottom:1px solid #edf2f7;background:#fbfdff}
    .support-card-head h2{margin:0;color:#102a43;font-size:16px;font-weight:900}
    .support-card-body{padding:20px}
    .support-message{margin:0 0 16px;color:#334155;font-size:14px;line-height:1.65;white-space:pre-line;overflow-wrap:anywhere}
    .support-page-link{display:flex;align-items:flex-start;gap:8px;margin-bottom:16px;padding:10px 12px;border-radius:12px;background:#f8fafc;color:#0f3150;font-size:12px;font-weight:800;text-decoration:none;word-break:break-word}
    .support-page-link i{font-size:17px;margin-top:1px}
    .support-attachment{display:block;overflow:hidden;border:1px solid #e2e8f0;border-radius:14px;background:#f8fafc}
    .support-attachment img{display:block;width:100%;max-height:380px;object-fit:contain;background:#fff}
    .support-response-footer{display:grid;gap:3px;margin-top:14px;padding-top:14px;border-top:1px solid #e2e8f0;color:#64748b;font-size:12px}
    .support-response-footer strong{color:#102a43}
    .support-empty-response{display:flex;align-items:center;gap:10px;padding:14px;border:1px dashed #cbd5e1;border-radius:14px;background:#f8fafc;color:#64748b;font-size:13px;font-weight:800}
    .support-empty-response i{font-size:20px;color:#0ea5e9}
    @media (max-width:1100px){.support-reclamation-grid{grid-template-columns:1fr}.support-reclamation-header{flex-direction:column}.support-reclamation-back{align-self:flex-start}}
    @media (max-width:640px){.support-reclamation-page{padding:16px 12px 36px}.support-reclamation-title h1{font-size:21px}.support-card-body{padding:16px}}
</style>
@endsection
