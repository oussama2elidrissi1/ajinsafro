@props([
    'user',
    'managerTeamPreview' => null,
])

@if(!empty($managerTeamPreview))
<div class="row mb-4">
    <div class="col-12">
        <div class="rounded-2xl border border-gray-200 shadow-sm overflow-hidden" style="font-family: 'Poppins', system-ui, sans-serif;">
            <div class="px-4 py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2" style="background: linear-gradient(90deg, #e6f3fa 0%, #fff 100%); border-color: #e5e7eb !important;">
                <div>
                    <h5 class="mb-0 fw-bold" style="color: #0e3a5a;">Profil manager</h5>
                    <p class="mb-0 small text-muted">?quipe rattachée (même agence) · {{ $managerTeamPreview['count'] }} membre(s)</p>
                </div>
                @if(Route::has('agent.dashboard'))
                    <a href="{{ route('agent.dashboard') }}" class="btn btn-sm fw-bold text-white border-0" style="background: #0083c4;">
                        <i class="fas fa-chart-line me-1"></i> Tableau de bord
                    </a>
                @endif
            </div>
            <div class="p-3 bg-white">
                <div class="row g-2">
                    @foreach($managerTeamPreview['members'] as $member)
                        <div class="col-md-6 col-xl-4">
                            <div class="d-flex align-items-center gap-2 p-2 rounded border border-light bg-light-subtle">
                                <img src="{{ $member->avatar_url }}" alt="" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate" style="color: #0e3a5a;">{{ $member->name }}</div>
                                    <div class="small text-muted text-truncate">{{ $member->email ?: '?' }}</div>
                                    @if($member->job_title)
                                        <div class="small" style="color: #0083c4;">{{ $member->job_title }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($managerTeamPreview['count'] === 0)
                    <p class="text-muted small mb-0">Aucun utilisateur n?Ta <code>manager_id</code> pointant vers vous. Affectez un manager sur la fiche utilisateur (paramètres).</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

