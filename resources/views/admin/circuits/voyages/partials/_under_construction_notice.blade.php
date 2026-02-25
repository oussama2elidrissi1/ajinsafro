@php
    $tabName = $tabName ?? null;
    $title = $title ?? ('⚠️ Section' . ($tabName ? ' ' . $tabName : '') . ' en cours de construction — ne pas modifier');
@endphp

<div class="alert alert-warning mb-4" role="alert">
    <h5 class="alert-heading mb-2">{{ $title }}</h5>
    <p class="mb-2">
        Cette section est encore en développement et n’est pas finalisée.
    </p>
    <p class="mb-0">
        Merci de ne pas modifier ces champs pour le moment afin d’éviter incohérences, erreurs de sauvegarde ou comportements inattendus.
        Elle sera activée dès qu’elle sera prête.
    </p>
</div>
