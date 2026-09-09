{{--
    Champ bilingue FR / AR.

    Les deux versions sont presentes dans le DOM et soumises ensemble ; la bascule de langue
    ne fait qu'afficher l'une ou l'autre. Aucune valeur n'est donc perdue en changeant
    de langue, et les deux colonnes restent distinctes en base.

    Parametres :
      $name      nom du champ francais       ex. title_fr
      $nameAr    nom du champ arabe          ex. title_ar
      $label     libelle francais
      $labelAr   libelle arabe
      $value     valeur francaise
      $valueAr   valeur arabe
      $type      'text' (defaut) | 'textarea' | 'editor'
      $rows      hauteur du textarea
      $required  bool
      $hint      aide facultative
      $maxlength facultatif
--}}
@php
    $type = $type ?? 'text';
    $rows = $rows ?? 3;
    $required = $required ?? false;
    $valueAr = $valueAr ?? null;
    $labelAr = $labelAr ?? null;
    $hint = $hint ?? null;
    $maxlength = $maxlength ?? null;
    $frId = 'f_'.\Illuminate\Support\Str::slug($name, '_');
    $arId = 'f_'.\Illuminate\Support\Str::slug($nameAr, '_');
@endphp

<div class="mb-3">
    {{-- Version francaise --}}
    <div data-lang-pane="fr">
        <label class="form-label" for="{{ $frId }}">
            {{ $label }}
            @if ($required)<span class="text-danger">*</span>@endif
            <span class="ho-lang-badge ms-1">FR</span>
        </label>

        @if ($type === 'textarea' || $type === 'editor')
            <textarea id="{{ $frId }}" name="{{ $name }}" rows="{{ $rows }}"
                      class="form-control @error($name) is-invalid @enderror"
                      @if ($maxlength) maxlength="{{ $maxlength }}" @endif
                      @if ($required) required @endif>{{ old($name, $value) }}</textarea>
        @else
            <input type="text" id="{{ $frId }}" name="{{ $name }}"
                   class="form-control @error($name) is-invalid @enderror"
                   value="{{ old($name, $value) }}"
                   @if ($maxlength) maxlength="{{ $maxlength }}" @endif
                   @if ($required) required @endif>
        @endif

        @if ($hint)<div class="form-text">{{ $hint }}</div>@endif
        @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    {{-- Version arabe --}}
    <div data-lang-pane="ar">
        <label class="form-label" for="{{ $arId }}">
            {{ $labelAr ?: $label }}
            <span class="ho-lang-badge ms-1">AR</span>
        </label>

        @if ($type === 'textarea' || $type === 'editor')
            <textarea id="{{ $arId }}" name="{{ $nameAr }}" rows="{{ $rows }}" dir="rtl" lang="ar"
                      class="form-control @error($nameAr) is-invalid @enderror"
                      @if ($maxlength) maxlength="{{ $maxlength }}" @endif>{{ old($nameAr, $valueAr) }}</textarea>
        @else
            <input type="text" id="{{ $arId }}" name="{{ $nameAr }}" dir="rtl" lang="ar"
                   class="form-control @error($nameAr) is-invalid @enderror"
                   value="{{ old($nameAr, $valueAr) }}"
                   @if ($maxlength) maxlength="{{ $maxlength }}" @endif>
        @endif

        @if (! old($nameAr, $valueAr) && old($name, $value))
            <div class="form-text text-warning">Traduction arabe non completee.</div>
        @endif
        @error($nameAr)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
</div>
