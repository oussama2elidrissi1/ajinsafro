{{--
  V2 Section footer �?" Précédent / Enregistrer / Suivant
  Params: $prev, $prevLabel, $next, $nextLabel, $formId
--}}
<div class="v2-section-footer">
    <div>
        @if(!empty($prev))
            <button type="button" class="v2-nav-btn" data-v2-prev="{{ $prev }}">
                <i class="bx bx-chevron-left"></i>
                {{ $prevLabel ?? 'Précédent' }}
            </button>
        @endif
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <button type="button" class="v2-btn v2-btn-primary" data-v2-save style="padding:8px 18px;font-size:13px;">
            <i class="bx bx-save"></i>
            Enregistrer cette étape
        </button>
        @if(!empty($next))
            <button type="button" class="v2-nav-btn" data-v2-next="{{ $next }}">
                {{ $nextLabel ?? 'Suivant' }}
                <i class="bx bx-chevron-right"></i>
            </button>
        @endif
    </div>
</div>

