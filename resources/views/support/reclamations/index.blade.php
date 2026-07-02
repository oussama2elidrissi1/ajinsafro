@extends((auth()->user() && method_exists(auth()->user(), 'isClientPortal') && auth()->user()->isClientPortal()) ? 'client.layout' : ((auth()->user() && method_exists(auth()->user(), 'isPartner') && auth()->user()->isPartner()) ? 'layouts.partner-v2' : 'layouts.master-ajinsafro'))

@section('title', 'Mes reclamations')
@section('page_title', 'Mes reclamations')

@section('content')
<div class="support-reclamations-page">
    <div class="support-reclamations-shell">
        <div class="support-reclamations-header">
            <div>
                <span class="support-reclamations-kicker">Support dev</span>
                <h1>Mes reclamations</h1>
                <p>Suivez vos problemes envoyes, leur statut et les reponses du dev.</p>
            </div>
            <button type="button" class="support-reclamations-primary" data-dev-reclamation-open>
                <i class="bx bx-message-square-error"></i>
                Envoyer une reclamation
            </button>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="support-reclamations-list">
            @forelse($reclamations as $reclamation)
                <article class="support-reclamation-row">
                    <div class="support-reclamation-row__main">
                        <div class="support-reclamation-row__top">
                            <h2>{{ $reclamation->subject ?: 'Sans sujet' }}</h2>
                            <span class="support-status support-status--{{ $reclamation->status }}">{{ $reclamation->status_label }}</span>
                        </div>
                        <p>{{ \Illuminate\Support\Str::limit($reclamation->message, 170) }}</p>
                        <div class="support-reclamation-row__meta">
                            <span><i class="bx bx-calendar"></i>{{ $reclamation->created_at->format('d/m/Y H:i') }}</span>
                            @if($reclamation->dev_response)
                                <span class="support-response-ready"><i class="bx bx-check-circle"></i> Reponse disponible</span>
                            @else
                                <span><i class="bx bx-time-five"></i> En attente de reponse</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('support.reclamations.show', $reclamation) }}" class="support-reclamation-row__action">
                        Voir le detail
                        <i class="bx bx-right-arrow-alt"></i>
                    </a>
                </article>
            @empty
                <div class="support-empty-state">
                    <i class="bx bx-message-square-x"></i>
                    <h2>Aucune reclamation envoyee</h2>
                    <p>Utilisez le bouton de support pour envoyer un probleme au dev avec une capture.</p>
                </div>
            @endforelse
        </div>

        @if($reclamations->hasPages())
            <div class="support-reclamations-pagination">{{ $reclamations->links() }}</div>
        @endif
    </div>
</div>

<style>
    .support-reclamations-page{position:relative;z-index:1;padding:26px 18px 56px;background:#f3f7fb;min-height:calc(100vh - 120px)}
    .support-reclamations-shell{width:min(1220px,100%);margin:0 auto}
    .support-reclamations-header{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:18px;padding:22px 24px;border:1px solid #dbe6f2;border-radius:18px;background:#fff;box-shadow:0 16px 38px rgba(15,23,42,.07)}
    .support-reclamations-kicker{display:block;margin-bottom:6px;color:#64748b;font-size:11px;font-weight:900;letter-spacing:.04em;text-transform:uppercase}
    .support-reclamations-header h1{margin:0;color:#102a43;font-size:28px;font-weight:900;line-height:1.15}
    .support-reclamations-header p{margin:7px 0 0;color:#64748b;font-size:14px}
    .support-reclamations-primary{display:inline-flex;align-items:center;gap:8px;border:0;border-radius:13px;background:#0f3150;color:#fff;padding:12px 16px;font-size:13px;font-weight:900;box-shadow:0 12px 26px rgba(15,49,80,.18)}
    .support-reclamations-primary:hover{background:#164466}
    .support-reclamations-primary i{font-size:18px}
    .support-reclamations-list{display:grid;gap:12px}
    .support-reclamation-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:center;padding:18px 20px;border:1px solid #dbe6f2;border-radius:16px;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.055)}
    .support-reclamation-row__main{min-width:0}
    .support-reclamation-row__top{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-bottom:7px}
    .support-reclamation-row__top h2{margin:0;color:#102a43;font-size:16px;font-weight:900;line-height:1.25}
    .support-reclamation-row p{margin:0;color:#475569;font-size:13px;line-height:1.45}
    .support-reclamation-row__meta{display:flex;flex-wrap:wrap;align-items:center;gap:12px;margin-top:10px;color:#64748b;font-size:12px;font-weight:700}
    .support-reclamation-row__meta span{display:inline-flex;align-items:center;gap:5px}
    .support-reclamation-row__meta i{font-size:15px}
    .support-response-ready{color:#15803d!important}
    .support-status{display:inline-flex;align-items:center;border-radius:999px;padding:5px 10px;font-size:11px;font-weight:900;text-transform:uppercase}
    .support-status--ouverte{background:#e0f2fe;color:#075985}
    .support-status--en_cours{background:#fef3c7;color:#92400e}
    .support-status--traitee{background:#dcfce7;color:#166534}
    .support-reclamation-row__action{display:inline-flex;align-items:center;justify-content:center;gap:7px;border:1px solid #c7d7ea;border-radius:12px;padding:10px 13px;color:#0f3150;background:#fff;font-size:12px;font-weight:900;text-decoration:none;white-space:nowrap}
    .support-reclamation-row__action:hover{border-color:#0ea5e9;color:#0f3150;box-shadow:0 8px 22px rgba(14,165,233,.12)}
    .support-empty-state{display:grid;place-items:center;text-align:center;gap:8px;padding:52px 20px;border:1px dashed #cbd5e1;border-radius:18px;background:#fff;color:#64748b}
    .support-empty-state i{font-size:42px;color:#0ea5e9}
    .support-empty-state h2{margin:0;color:#102a43;font-size:18px;font-weight:900}
    .support-empty-state p{margin:0;font-size:13px}
    .support-reclamations-pagination{margin-top:16px}
    @media (max-width:760px){.support-reclamations-header{align-items:flex-start;flex-direction:column}.support-reclamation-row{grid-template-columns:1fr}.support-reclamation-row__action{justify-self:start}}
    @media (max-width:640px){.support-reclamations-page{padding:16px 12px 40px}.support-reclamations-header h1{font-size:23px}}
</style>
@endsection
