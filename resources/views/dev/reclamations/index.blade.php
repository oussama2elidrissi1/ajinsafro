@extends('layouts.admin-v6')

@section('title', 'Reclamations dev')
@section('page_title', 'Reclamations dev')

@section('content')
<div class="dev-reclamations-page">
    <section class="dev-reclamations-head">
        <div>
            <span class="dev-reclamations-kicker">Support dev</span>
            <h1>Reclamations au dev</h1>
            <p>File de problemes envoyes par les agents et utilisateurs.</p>
        </div>
        <form method="GET" action="{{ route('admin.dev.reclamations.index') }}" class="dev-reclamations-search">
            @if($status)
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <i class="bx bx-search"></i>
            <input type="search" name="q" value="{{ $q }}" placeholder="Rechercher une reclamation...">
            <button type="submit">Filtrer</button>
        </form>
    </section>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <section class="dev-reclamations-stats">
        <a href="{{ route('admin.dev.reclamations.index', $q !== '' ? ['q' => $q] : []) }}" class="dev-status-card {{ !$status ? 'is-active' : '' }}">
            <span>Toutes</span>
            <strong>{{ $counts['all'] }}</strong>
        </a>
        @foreach(\App\Models\DevReclamation::statuses() as $key => $label)
            <a href="{{ route('admin.dev.reclamations.index', array_filter(['status' => $key, 'q' => $q ?: null])) }}" class="dev-status-card {{ $status === $key ? 'is-active' : '' }}">
                <span>{{ $label }}</span>
                <strong>{{ $counts[$key] ?? 0 }}</strong>
            </a>
        @endforeach
    </section>

    <section class="dev-reclamations-panel">
        <div class="dev-panel-head">
            <div>
                <span class="dev-reclamations-kicker">Traitement</span>
                <h2>Liste des reclamations</h2>
            </div>
            <span class="dev-panel-count">{{ $reclamations->total() }} resultat(s)</span>
        </div>

        <div class="dev-reclamations-table">
            <table>
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Sujet</th>
                        <th>Statut</th>
                        <th>Image</th>
                        <th>Date</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reclamations as $reclamation)
                        <tr>
                            <td>
                                <strong>{{ $reclamation->user?->name ?? 'Utilisateur' }}</strong>
                                <span>{{ $reclamation->user?->email }}</span>
                            </td>
                            <td>
                                <strong>{{ $reclamation->subject ?: 'Sans sujet' }}</strong>
                                <span>{{ \Illuminate\Support\Str::limit($reclamation->message, 95) }}</span>
                            </td>
                            <td>
                                <span class="dev-badge dev-badge--{{ $reclamation->status }}">{{ $reclamation->status_label }}</span>
                            </td>
                            <td>
                                <span class="dev-image-state {{ $reclamation->attachment_path ? 'has-image' : '' }}">
                                    <i class="bx {{ $reclamation->attachment_path ? 'bx-image' : 'bx-minus-circle' }}"></i>
                                    {{ $reclamation->attachment_path ? 'Oui' : 'Non' }}
                                </span>
                            </td>
                            <td>{{ $reclamation->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.dev.reclamations.show', $reclamation) }}" class="dev-action-link">
                                    Traiter
                                    <i class="bx bx-right-arrow-alt"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="dev-empty-state">
                                    <i class="bx bx-message-square-x"></i>
                                    <strong>Aucune reclamation.</strong>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reclamations->hasPages())
            <div class="dev-pagination">{{ $reclamations->links('pagination::bootstrap-5') }}</div>
        @endif
    </section>
</div>

<style>
    .dev-reclamations-page{--dev-blue:#0b68d1;--dev-dark:#102a43;--dev-muted:#64748b;--dev-border:#dbe6f2;--dev-bg:#f6f9fc;position:relative;z-index:1;margin:-1.5rem -.75rem;padding:22px 28px 44px;background:var(--dev-bg);min-height:calc(100vh - 100px);font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:var(--dev-dark)}
    .dev-reclamations-head{display:flex;align-items:center;justify-content:space-between;gap:22px;margin-bottom:18px;padding:22px 24px;border:1px solid var(--dev-border);border-radius:18px;background:#fff;box-shadow:0 14px 34px rgba(15,45,75,.07)}
    .dev-reclamations-kicker{display:block;margin-bottom:6px;color:#71829a;font-size:11px;font-weight:900;letter-spacing:.06em;text-transform:uppercase}
    .dev-reclamations-head h1,.dev-panel-head h2{margin:0;color:#102a43;font-weight:900;line-height:1.15}
    .dev-reclamations-head h1{font-size:28px}
    .dev-reclamations-head p{margin:7px 0 0;color:var(--dev-muted);font-size:14px}
    .dev-reclamations-search{display:grid;grid-template-columns:auto minmax(220px,360px) auto;align-items:center;gap:8px;padding:7px;border:1px solid #d8e5f2;border-radius:14px;background:#f8fbff}
    .dev-reclamations-search i{padding-left:8px;color:#6b7c93;font-size:18px}
    .dev-reclamations-search input{height:38px;border:0;background:transparent;color:#102a43;font-weight:700;outline:0}
    .dev-reclamations-search button{height:38px;border:0;border-radius:11px;background:#0b68d1;color:#fff;padding:0 16px;font-weight:900}
    .dev-reclamations-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}
    .dev-status-card{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;border:1px solid var(--dev-border);border-radius:16px;background:#fff;color:#102a43;text-decoration:none;box-shadow:0 10px 28px rgba(15,45,75,.055)}
    .dev-status-card:hover,.dev-status-card.is-active{border-color:#0b68d1;color:#102a43;box-shadow:0 14px 34px rgba(11,104,209,.13)}
    .dev-status-card span{color:#64748b;font-size:13px;font-weight:800}
    .dev-status-card strong{font-size:26px;font-weight:900}
    .dev-reclamations-panel{overflow:hidden;border:1px solid var(--dev-border);border-radius:18px;background:#fff;box-shadow:0 16px 38px rgba(15,45,75,.07)}
    .dev-panel-head{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:18px 22px;border-bottom:1px solid #e7eef7;background:#fbfdff}
    .dev-panel-head h2{font-size:18px}
    .dev-panel-count{display:inline-flex;align-items:center;border-radius:999px;background:#eef6ff;color:#0b68d1;padding:7px 11px;font-size:12px;font-weight:900}
    .dev-reclamations-table{overflow-x:auto}
    .dev-reclamations-table table{width:100%;border-collapse:collapse}
    .dev-reclamations-table th{padding:13px 18px;background:#f1f6fb;color:#486581;font-size:11px;font-weight:900;letter-spacing:.04em;text-transform:uppercase;white-space:nowrap}
    .dev-reclamations-table td{padding:15px 18px;border-top:1px solid #edf2f7;color:#102a43;vertical-align:middle}
    .dev-reclamations-table td strong{display:block;color:#102a43;font-size:13px;font-weight:900}
    .dev-reclamations-table td span{display:block;margin-top:4px;color:#64748b;font-size:12px}
    .dev-badge{display:inline-flex!important;width:max-content;align-items:center;border-radius:999px;padding:6px 10px;font-size:11px!important;font-weight:900!important;text-transform:uppercase}
    .dev-badge--ouverte{background:#e0f2fe;color:#075985!important}
    .dev-badge--en_cours{background:#fef3c7;color:#92400e!important}
    .dev-badge--traitee{background:#dcfce7;color:#166534!important}
    .dev-image-state{display:inline-flex!important;align-items:center;gap:6px;margin:0!important;color:#94a3b8!important;font-weight:900}
    .dev-image-state.has-image{color:#0b68d1!important}
    .dev-action-link{display:inline-flex;align-items:center;gap:7px;border:1px solid #c7d7ea;border-radius:12px;background:#fff;color:#0f3150;padding:9px 12px;font-size:12px;font-weight:900;text-decoration:none;white-space:nowrap}
    .dev-action-link:hover{border-color:#0b68d1;color:#0f3150;box-shadow:0 10px 24px rgba(11,104,209,.12)}
    .dev-empty-state{display:grid;place-items:center;gap:8px;padding:44px 20px;color:#64748b;text-align:center}
    .dev-empty-state i{font-size:40px;color:#0b68d1}
    .dev-pagination{display:flex;align-items:center;justify-content:flex-end;padding:16px 20px;border-top:1px solid #edf2f7}
    .dev-pagination nav{display:flex;align-items:center;justify-content:flex-end;width:100%}
    .dev-pagination .pagination{display:flex;align-items:center;gap:6px;margin:0}
    .dev-pagination .page-item{display:block;margin:0}
    .dev-pagination .page-link{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 11px;border:1px solid #d8e5f2;border-radius:10px;background:#fff;color:#0f3150;font-size:12px;font-weight:900;line-height:1;text-decoration:none;box-shadow:none}
    .dev-pagination .page-link:hover{border-color:#0b68d1;color:#0b68d1;background:#f4f9ff}
    .dev-pagination .page-item.active .page-link{border-color:#0b68d1;background:#0b68d1;color:#fff}
    .dev-pagination .page-item.disabled .page-link{border-color:#edf2f7;background:#f8fafc;color:#94a3b8}
    .dev-pagination svg{width:14px!important;height:14px!important;max-width:14px!important;max-height:14px!important;display:block}
    .dev-pagination p{margin:0;color:#64748b;font-size:12px;font-weight:800}
    @media (max-width:1100px){.dev-reclamations-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.dev-reclamations-head{align-items:flex-start;flex-direction:column}.dev-reclamations-search{width:100%;grid-template-columns:auto minmax(0,1fr) auto}}
    @media (max-width:640px){.dev-reclamations-page{margin:-1rem -.75rem;padding:16px 12px 36px}.dev-reclamations-stats{grid-template-columns:1fr}.dev-reclamations-search{grid-template-columns:auto minmax(0,1fr)}.dev-reclamations-search button{grid-column:1 / -1}.dev-reclamations-table th,.dev-reclamations-table td{padding:12px}}
</style>
@endsection
