{{--
    Selecteur d'image unitaire.

    S'appuie sur l'uploader existant du projet (route admin.local-media.upload) : aucun
    second systeme de medias n'est introduit. Le champ ne stocke que le chemin retourne.

    Parametres : $inputName, $value (chemin), $context (dossier logique)
--}}
@php
    $context = $context ?? 'hajj-omra';
    $value = $value ?? null;
    $previewUrl = $value ? (\Illuminate\Support\Str::startsWith($value, ['http://', 'https://'])
        ? $value
        : \Illuminate\Support\Facades\Storage::disk('public')->url($value)) : null;
@endphp

<div class="ho-image-picker" data-image-picker data-context="{{ $context }}">
    <input type="hidden" name="{{ $inputName }}" value="{{ $value }}" data-role="path">

    <div class="d-flex align-items-start gap-2">
        <div class="ho-thumb flex-shrink-0" style="width:96px;" data-role="preview-wrap" @if (! $previewUrl) hidden @endif>
            <img src="{{ $previewUrl }}" alt="" data-role="preview">
            <button type="button" class="ho-thumb-remove" data-role="clear" title="Retirer">&times;</button>
        </div>
        <div class="flex-grow-1">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-role="browse">Choisir une image</button>
            <input type="file" accept="image/*" class="d-none" data-role="file">
            <div class="form-text" data-role="status">JPG, PNG ou WEBP — 5 Mo maximum.</div>
        </div>
    </div>
</div>
