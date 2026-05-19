@extends('layouts.admin-v6')

@section('title', 'Comptes points de vente')

@push('styles')
<style>
    .aj-page-head { display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:20px; }
    .aj-page-head h1 { margin:0; font-size:28px; font-weight:900; color:#172b4d; }
    .aj-page-head p { margin:6px 0 0; color:#71829a; font-weight:600; }
    .aj-card { background:#fff; border:1px solid #e6edf5; border-radius:18px; box-shadow:0 12px 35px rgba(15,45,75,.08); }
    .aj-card-head { padding:18px 20px 0; display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .aj-card-body { padding:20px; }
    .aj-filter-grid { display:grid; grid-template-columns:repeat(6, minmax(0,1fr)); gap:12px; }
    .aj-form-control, .aj-select { width:100%; height:44px; border-radius:12px; border:1px solid #dce8f3; background:#fff; padding:0 14px; font-weight:700; color:#172b4d; }
    .aj-table { width:100%; border-collapse:collapse; }
    .aj-table th { text-align:left; background:#f7fbff; color:#66758a; font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:900; padding:12px 14px; border-bottom:1px solid #e6edf5; white-space:nowrap; }
    .aj-table td { padding:14px; border-bottom:1px solid #edf2f7; vertical-align:middle; }
    .aj-badge { display:inline-flex; align-items:center; gap:5px; padding:5px 10px; border-radius:999px; font-size:11px; font-weight:900; }
    .aj-badge.ok { background:#e8fff4; color:#19b982; }
    .aj-badge.off { background:#fff0ef; color:#ef4d45; }
    .aj-badge.soft { background:#eef4ff; color:#005792; }
    .aj-btn { display:inline-flex; align-items:center; gap:8px; height:42px; padding:0 14px; border-radius:12px; border:1px solid #dce8f3; background:#fff; color:#06345c; font-weight:800; text-decoration:none; }
    .aj-btn.primary { background:#005792; color:#fff; border-color:#005792; }
    .aj-avatar { width:42px; height:42px; border-radius:50%; object-fit:cover; }
    .aj-subtle { color:#71829a; font-size:12px; font-weight:600; }
    @media (max-width: 1200px) { .aj-filter-grid { grid-template-columns:repeat(2, minmax(0,1fr)); } }
    @media (max-width: 768px) { .aj-filter-grid { grid-template-columns:1fr; } .aj-table { min-width:900px; } }
</style>
@endpush

@section('content')
<div class="aj-page-head">
    <div>
        <h1>Comptes points de vente</h1>
        <p>Comptes utilisateurs lies aux points de vente, roles, acces et reservations affectees.</p>
    </div>
    @if(Route::has('admin.agency-accounts.create'))
        <a href="{{ route('admin.agency-accounts.create') }}" class="aj-btn primary"><i class="bx bx-user-plus"></i> Nouveau compte</a>
    @endif
</div>

<div class="aj-card" style="margin-bottom:18px;">
    <div class="aj-card-body">
        <form method="GET" action="{{ route('admin.agency-accounts.index') }}">
            <div class="aj-filter-grid">
                <input type="text" name="search" class="aj-form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Nom, email, tÃ©lÃ©phone">
                <select name="branch_id" class="aj-select">
                    <option value="">Tous les points de vente</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((int)($filters['branch_id'] ?? 0) === $branch->id)>{{ $branch->display_name }}</option>
                    @endforeach
                </select>
                <select name="role_name" class="aj-select">
                    <option value="">Tous les rÃ´les</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" @selected(($filters['role_name'] ?? '') === $role->name)>{{ $role->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="aj-select">
                    <option value="">Tous les statuts</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Actif</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactif</option>
                </select>
                <select name="can_login" class="aj-select">
                    <option value="">Peut se connecter</option>
                    <option value="1" @selected(($filters['can_login'] ?? '') === '1')>Oui</option>
                    <option value="0" @selected(($filters['can_login'] ?? '') === '0')>Non</option>
                </select>
                <select name="last_login" class="aj-select">
                    <option value="">DerniÃ¨re connexion</option>
                    <option value="recent" @selected(($filters['last_login'] ?? '') === 'recent')>DÃ©jÃ  connectÃ©</option>
                    <option value="never" @selected(($filters['last_login'] ?? '') === 'never')>Jamais</option>
                </select>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px;flex-wrap:wrap;">
                <button type="submit" class="aj-btn primary"><i class="bx bx-filter-alt"></i> Filtrer</button>
                <a href="{{ route('admin.agency-accounts.index') }}" class="aj-btn"><i class="bx bx-reset"></i> RÃ©initialiser</a>
            </div>
        </form>
    </div>
</div>

<div class="aj-card">
    <div class="aj-card-head">
        <div>
            <strong style="font-size:16px;color:#172b4d;">Liste des comptes</strong>
            <div class="aj-subtle">{{ $accounts->total() }} compte(s) trouvÃ©(s)</div>
        </div>
    </div>
    <div class="aj-card-body" style="padding-top:14px;overflow-x:auto;">
        <table class="aj-table">
            <thead>
                <tr>
                    <th>Compte</th>
                            <th>Point de vente</th>
                    <th>EmployÃ©</th>
                    <th>RÃ´le</th>
                    <th>Statut</th>
                    <th>DerniÃ¨re connexion</th>
                    <th>RÃ©servations</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <img class="aj-avatar" src="{{ $account->avatar_url }}" alt="{{ $account->name }}">
                                <div>
                                    <div style="font-weight:900;color:#172b4d;">{{ $account->name }}</div>
                                    <div class="aj-subtle">{{ $account->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $account->branch?->display_name ?? 'â€”' }}</td>
                        <td>{{ $account->agencyEmployee?->full_name ?? 'â€”' }}</td>
                        <td>{{ $account->roles->first()?->name ?? 'â€”' }}</td>
                        <td>
                            @if($account->is_active)
                                <span class="aj-badge ok">Actif</span>
                            @else
                                <span class="aj-badge off">Inactif</span>
                            @endif
                            @if($account->agencyEmployee?->can_login)
                                <div style="margin-top:6px;"><span class="aj-badge soft">Login point de vente</span></div>
                            @endif
                        </td>
                        <td>
                            {{ $account->last_login_at?->timezone('Africa/Casablanca')?->format('d/m/Y H:i') ?? 'Jamais' }}
                        </td>
                        <td>{{ (int) ($account->assigned_reservations_count ?? 0) }}</td>
                        <td>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                @if(Route::has('admin.agency-accounts.show'))
                                    <a href="{{ route('admin.agency-accounts.show', $account) }}" class="aj-btn">Voir</a>
                                @endif
                                @if(Route::has('admin.agency-accounts.edit'))
                                    <a href="{{ route('admin.agency-accounts.edit', $account) }}" class="aj-btn">Ã‰diter</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="padding:28px;text-align:center;color:#71829a;font-weight:700;">Aucun compte point de vente trouvÃ©.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="aj-card-body" style="padding-top:0;">
        {{ $accounts->links() }}
    </div>
</div>
@endsection

