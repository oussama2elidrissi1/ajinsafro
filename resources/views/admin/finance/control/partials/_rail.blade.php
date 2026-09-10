@php
    /**
     * Rail des modules « Finance & controle ».
     *
     * Source unique : config/admin_menu.php via AdminMenuService — le rail affiche donc
     * exactement les ecrans auxquels l'utilisateur a droit, dans le meme ordre que le mega-menu.
     * Aucune URL n'est ecrite en dur ici.
     */
    $fcUser = auth()->user();
    $fcItems = [];

    try {
        $fcNode = collect(app(\App\Services\Admin\AdminMenuService::class)->buildForUser($fcUser, ['only_keys' => ['finance-control']]))->first();
        foreach ($fcNode['children'] ?? [] as $fcChild) {
            if (empty($fcChild['is_clickable'])) {
                continue;
            }
            $fcItems[] = [
                'label' => (string) $fcChild['label'],
                'href' => (string) $fcChild['href'],
                'route' => (string) ($fcChild['route'] ?? ''),
                'active' => (bool) $fcChild['active'],
            ];
        }
    } catch (\Throwable $e) {
        $fcItems = [];
    }

    // Compteurs du rail : nombre de projets suivis et pieces justificatives manquantes.
    $fcProjectsCount = null;
    $fcMissingDocuments = null;

    try {
        $fcProjectsCount = \App\Models\Departure::query()->count();
        $fcMissingDocuments = app(\App\Services\Finance\FinanceControlService::class)->missingDocumentsCount();
    } catch (\Throwable $e) {
        $fcProjectsCount = null;
        $fcMissingDocuments = null;
    }

    $fcBadges = [
        'admin.finance.control.travel-projects.index' => ['value' => $fcProjectsCount, 'warn' => false],
        'admin.finance.control.documents.index' => ['value' => $fcMissingDocuments ?: null, 'warn' => true],
    ];

    $fcDocumentsHref = \Illuminate\Support\Facades\Route::has('admin.finance.control.documents.index')
        ? route('admin.finance.control.documents.index')
        : null;
@endphp

<aside class="fc-rail" aria-label="Modules Finance &amp; contrôle">
    <div class="fc-rail-title">Finance &amp; contrôle</div>

    @foreach ($fcItems as $fcItem)
        @php($fcBadge = $fcBadges[$fcItem['route']] ?? null)
        <a href="{{ $fcItem['href'] }}"
           class="fc-rail-item {{ $fcItem['active'] ? 'is-active' : '' }}"
           @if($fcItem['active']) aria-current="page" @endif>
            <span>{{ $fcItem['label'] }}</span>
            @if ($fcBadge && $fcBadge['value'] !== null)
                <span class="fc-rail-badge {{ $fcBadge['warn'] ? 'is-warn' : '' }}">{{ $fcBadge['value'] }}</span>
            @endif
        </a>
    @endforeach

    @if ($fcMissingDocuments)
        <div class="fc-rail-alert">
            <div class="fc-rail-alert-title">{{ $fcMissingDocuments }} justificatif{{ $fcMissingDocuments > 1 ? 's' : '' }} manquant{{ $fcMissingDocuments > 1 ? 's' : '' }}</div>
            <div class="fc-rail-alert-text">Opérations enregistrées sans pièce jointe.</div>
            @if ($fcDocumentsHref)
                <a href="{{ $fcDocumentsHref }}" class="fc-rail-alert-link">Traiter →</a>
            @endif
        </div>
    @endif
</aside>
