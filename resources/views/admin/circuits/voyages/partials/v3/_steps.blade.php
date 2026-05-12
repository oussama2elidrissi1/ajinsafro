<aside class="v2-sidebar v3-steps-card" id="v2-sidebar">
    <div class="v3-steps-card__head">
        <p class="v3-card-kicker">Workflow</p>
        <h2 class="v3-card-title">Etapes du voyage</h2>
        <p class="v3-card-subtitle">Validation par etape avec sauvegarde guidee.</p>
    </div>

    @php $lastGroup = null; @endphp
    @foreach($sections as $i => $sec)
        @if($sec['group'] !== $lastGroup)
            <div class="v2-sb-group">{{ $sec['group'] }}</div>
            @php $lastGroup = $sec['group']; @endphp
        @endif
        @php
            $stepState = $initialStepStates[$sec['id']] ?? 'incomplete';
            $stateLabel = $stepState === 'complete' ? 'Validee' : ($stepState === 'error' ? 'Erreur' : 'A completer');
        @endphp
        <button type="button" class="v2-sb-item{{ $i === 0 ? ' active' : '' }} state-{{ $stepState }}" data-v2-nav="{{ $sec['id'] }}" data-v2-step-state="{{ $stepState }}">
            <span class="v2-sb-index">{{ $i + 1 }}</span>
            <span class="v2-sb-icon"><i class="bx {{ $sec['icon'] }}"></i></span>
            <span class="v2-sb-label-wrap">
                <span class="v2-sb-label">{{ $sec['label'] }}</span>
                <span class="v2-sb-meta" data-v2-step-meta>{{ $stateLabel }}</span>
            </span>
            <span class="v2-sb-dot"></span>
        </button>
    @endforeach

    <div class="v2-sb-footer">
        <button type="button" class="v2-sb-save" data-v2-save>
            <i class="bx bx-save"></i>
            {{ $isCreate ? 'Creer le voyage' : 'Enregistrer l etape courante' }}
        </button>
    </div>
</aside>
