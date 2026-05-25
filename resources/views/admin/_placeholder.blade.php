{{-- Partial: page placeholder AjinsAfro - titre du sous-menu + card "Page en cours de construction" --}}
<x-admin.page-header
    :title="$title"
    :breadcrumbs="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => $title],
    ]"
/>

<div class="row">
    <div class="col-12">
        <div class="aj-empty">
            <div class="mb-3">
                <i class="bx bx-layer" style="font-size: 2.5rem; color: var(--ajp-primary);"></i>
            </div>
            <h5 class="mb-2" style="color: var(--ajp-ink); font-weight: 800;">{{ $title }}</h5>
            <p class="text-muted mb-0" style="font-weight: 600;">Cette section est en cours de développement.</p>
        </div>
    </div>
</div>

