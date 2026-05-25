<aside class="v2-sidebar v3-steps-card" id="v2-sidebar">
    <div class="v3-steps-card__head">
        <div class="v3-steps-card__title">
            <p class="v3-card-kicker">Workflow</p>
            <h2 class="v3-card-title">Etapes du voyage</h2>
            <p class="v3-card-subtitle">Validation par etape avec sauvegarde guidee.</p>
        </div>
        <button type="button" id="workflowToggleBtn" class="workflow-toggle-btn" aria-label="Ouvrir ou fermer le workflow">
            <i class="bx bx-menu"></i>
        </button>
    </div>

    <nav class="v3-stepper" aria-label="Etapes du voyage">
        <div class="v3-stepper__mobile">
            <label class="v3-stepper__mobile-label" for="v3StepSelect">Etape</label>
            <select class="form-select form-select-sm v3-stepper__select" id="v3StepSelect">
                @foreach($sections as $i => $sec)
                    <option value="{{ $sec['id'] }}">{{ ($i + 1) }} / {{ count($sections) }} — {{ $sec['label'] }}</option>
                @endforeach
            </select>
        </div>

        <div class="v3-stepper__list" role="list">
            @php $lastGroup = null; @endphp
            @foreach($sections as $i => $sec)
                @if($sec['group'] !== $lastGroup)
                    <div class="v3-stepper__group">{{ $sec['group'] }}</div>
                    @php $lastGroup = $sec['group']; @endphp
                @endif
                @php
                    $stepState = $initialStepStates[$sec['id']] ?? 'incomplete';
                    $stateLabel = $stepState === 'complete' ? 'Validée' : ($stepState === 'error' ? 'Erreur' : 'À compléter');
                    $isComplete = $stepState === 'complete';
                @endphp
                <button
                    type="button"
                    class="v2-sb-item v3-step{{ $i === 0 ? ' active' : '' }} state-{{ $stepState }}"
                    data-v2-nav="{{ $sec['id'] }}"
                    data-v2-step-state="{{ $stepState }}"
                    title="{{ $sec['label'] }}"
                    aria-label="{{ $sec['label'] }}"
                >
                    <span class="v3-step__lead">
                        <span class="v2-sb-index v3-step__number">{{ $i + 1 }}</span>
                        <span class="v2-sb-icon v3-step__icon"><i class="bx {{ $sec['icon'] }}"></i></span>
                    </span>
                    <span class="v3-step__text">
                        <span class="v3-step__label">{{ $sec['label'] }}</span>
                        <span class="v3-step__status" data-v2-step-meta>{{ $stateLabel }}</span>
                    </span>
                    <span class="v3-step__tail" aria-hidden="true">
                        @if($isComplete)
                            <span class="v3-step__badge v3-step__badge--ok"><i class="bx bx-check"></i></span>
                        @elseif($stepState === 'error')
                            <span class="v3-step__badge v3-step__badge--err"><i class="bx bx-error"></i></span>
                        @else
                            <span class="v3-step__badge v3-step__badge--todo"><i class="bx bx-circle"></i></span>
                        @endif
                    </span>
                </button>
            @endforeach
        </div>
    </nav>

    <div class="v2-sb-footer">
        <button type="button" class="v2-sb-save" data-v2-save>
            <i class="bx bx-save"></i>
            {{ $isCreate ? 'Creer le voyage' : 'Enregistrer l etape courante' }}
        </button>
    </div>
</aside>

<script>
    (function () {
        var select = document.getElementById('v3StepSelect');
        if (!select) return;
        function syncFromHash() {
            var step = String(window.location.hash || '').replace('#', '');
            if (!step) return;
            if (select.value !== step) {
                select.value = step;
            }
        }
        syncFromHash();
        window.addEventListener('hashchange', syncFromHash);
        select.addEventListener('change', function () {
            var v = String(select.value || '').trim();
            if (!v) return;
            window.location.hash = '#' + v;
        });
    })();
</script>
