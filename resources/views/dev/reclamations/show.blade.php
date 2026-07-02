@extends('layouts.admin-v6')

@section('title', 'Traitement reclamation')
@section('page_title', 'Traitement reclamation')

@section('content')
<div class="dev-reclamation-page">
    <section class="dev-reclamation-head">
        <div>
            <span class="dev-reclamation-kicker">Reclamation au dev</span>
            <h1>{{ $reclamation->subject ?: 'Reclamation' }}</h1>
            <div class="dev-reclamation-meta">
                <span>Envoyee par {{ $reclamation->user?->name ?? 'Utilisateur' }}</span>
                <span>{{ $reclamation->created_at->format('d/m/Y H:i') }}</span>
                <span class="dev-badge dev-badge--{{ $reclamation->status }}">{{ $reclamation->status_label }}</span>
            </div>
        </div>
        <a href="{{ route('admin.dev.reclamations.index') }}" class="dev-back-link">
            <i class="bx bx-arrow-back"></i>
            Retour
        </a>
    </section>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="dev-reclamation-grid">
        <section class="dev-card dev-card--message">
            <div class="dev-card-head">
                <div>
                    <span class="dev-reclamation-kicker">Message envoye</span>
                    <h2>Probleme signale</h2>
                </div>
            </div>
            <div class="dev-card-body">
                <p class="dev-message">{{ $reclamation->message }}</p>

                @if($reclamation->page_url)
                    <a class="dev-page-link" href="{{ $reclamation->page_url }}" target="_blank" rel="noopener">
                        <i class="bx bx-link-external"></i>
                        <span>{{ $reclamation->page_url }}</span>
                    </a>
                @endif

                @if($reclamation->attachment_url)
                    <a class="dev-attachment" href="{{ $reclamation->attachment_url }}" target="_blank" rel="noopener">
                        <img src="{{ $reclamation->attachment_url }}" alt="Capture jointe">
                    </a>
                @endif
            </div>
        </section>

        <aside class="dev-card dev-card--response">
            <div class="dev-card-head">
                <div>
                    <span class="dev-reclamation-kicker">Traitement dev</span>
                    <h2>Reponse et statut</h2>
                </div>
            </div>
            <div class="dev-card-body">
                <form method="POST" action="{{ route('admin.dev.reclamations.update', $reclamation) }}" class="dev-treatment-form">
                    @csrf
                    @method('PATCH')

                    <div class="dev-field">
                        <label for="status">Statut</label>
                        <select id="status" name="status">
                            @foreach(\App\Models\DevReclamation::statuses() as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $reclamation->status) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="dev-field">
                        <label for="dev_response">Message affiche a l'utilisateur</label>
                        <textarea id="dev_response" name="dev_response" rows="9" placeholder="Expliquez la correction ou la suite a faire...">{{ old('dev_response', $reclamation->dev_response) }}</textarea>
                    </div>

                    <button type="submit" class="dev-submit-btn">
                        <i class="bx bx-check-circle"></i>
                        Enregistrer le traitement
                    </button>
                </form>
            </div>
        </aside>
    </div>
</div>

<style>
    .dev-reclamation-page{--dev-blue:#0b68d1;--dev-dark:#102a43;--dev-muted:#64748b;--dev-border:#dbe6f2;--dev-bg:#f6f9fc;position:relative;z-index:1;margin:-1.5rem -.75rem;padding:22px 28px 44px;background:var(--dev-bg);min-height:calc(100vh - 100px);font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:var(--dev-dark)}
    .dev-reclamation-head{display:flex;align-items:flex-start;justify-content:space-between;gap:22px;margin-bottom:18px;padding:22px 24px;border:1px solid var(--dev-border);border-radius:18px;background:#fff;box-shadow:0 14px 34px rgba(15,45,75,.07)}
    .dev-reclamation-kicker{display:block;margin-bottom:6px;color:#71829a;font-size:11px;font-weight:900;letter-spacing:.06em;text-transform:uppercase}
    .dev-reclamation-head h1{margin:0;color:#102a43;font-size:28px;font-weight:900;line-height:1.15}
    .dev-reclamation-meta{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-top:10px;color:#64748b;font-size:13px;font-weight:700}
    .dev-badge{display:inline-flex;align-items:center;border-radius:999px;padding:6px 10px;font-size:11px;font-weight:900;text-transform:uppercase}
    .dev-badge--ouverte{background:#e0f2fe;color:#075985}
    .dev-badge--en_cours{background:#fef3c7;color:#92400e}
    .dev-badge--traitee{background:#dcfce7;color:#166534}
    .dev-back-link{display:inline-flex;align-items:center;gap:8px;border:1px solid #c7d7ea;border-radius:13px;background:#fff;color:#0f3150;padding:11px 15px;font-size:13px;font-weight:900;text-decoration:none;white-space:nowrap}
    .dev-back-link:hover{border-color:#0b68d1;color:#0f3150;box-shadow:0 10px 24px rgba(11,104,209,.12)}
    .dev-reclamation-grid{display:grid;grid-template-columns:minmax(0,2fr) minmax(420px,1fr);gap:18px;align-items:start}
    .dev-card{overflow:hidden;border:1px solid var(--dev-border);border-radius:18px;background:#fff;box-shadow:0 16px 38px rgba(15,45,75,.07)}
    .dev-card--response{min-width:0}
    .dev-card-head{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #e7eef7;background:#fbfdff}
    .dev-card-head h2{margin:0;color:#102a43;font-size:18px;font-weight:900;line-height:1.2}
    .dev-card-body{padding:20px 22px}
    .dev-message{margin:0 0 16px;color:#334155;font-size:14px;line-height:1.65;white-space:pre-line;overflow-wrap:anywhere}
    .dev-page-link{display:flex;align-items:flex-start;gap:9px;margin-bottom:16px;padding:12px 14px;border-radius:14px;background:#f8fafc;color:#0f3150;font-size:13px;font-weight:900;text-decoration:none;word-break:break-word}
    .dev-page-link i{font-size:18px;margin-top:1px}
    .dev-attachment{display:block;overflow:hidden;border:1px solid #e2e8f0;border-radius:16px;background:#f8fafc}
    .dev-attachment img{display:block;width:100%;max-height:520px;object-fit:contain;background:#fff}
    .dev-treatment-form{display:grid;gap:16px}
    .dev-field{display:grid;gap:7px}
    .dev-field label{margin:0;color:#52637a;font-size:12px;font-weight:900}
    .dev-field select,.dev-field textarea{width:100%;border:1px solid #d8e5f2;border-radius:14px;background:#fff;color:#102a43;font-size:14px;font-weight:700;outline:0;transition:border-color .15s ease,box-shadow .15s ease}
    .dev-field select{height:46px;padding:0 14px}
    .dev-field textarea{min-height:230px;padding:14px;line-height:1.55;resize:vertical}
    .dev-field select:focus,.dev-field textarea:focus{border-color:#0b68d1;box-shadow:0 0 0 4px rgba(11,104,209,.1)}
    .dev-submit-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;width:100%;height:48px;border:0;border-radius:14px;background:#19b982;color:#fff;font-size:14px;font-weight:900;box-shadow:0 14px 28px rgba(25,185,130,.18)}
    .dev-submit-btn:hover{background:#12a875}
    .dev-submit-btn i{font-size:19px}
    @media (max-width:1180px){.dev-reclamation-grid{grid-template-columns:1fr}.dev-card--response{position:static}}
    @media (max-width:640px){.dev-reclamation-page{margin:-1rem -.75rem;padding:16px 12px 36px}.dev-reclamation-head{flex-direction:column}.dev-reclamation-head h1{font-size:22px}.dev-back-link{align-self:flex-start}.dev-card-body{padding:16px}}
</style>
@endsection
