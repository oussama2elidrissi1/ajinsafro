<?php
    $tabName = $tabName ?? null;
    $title = $title ?? ('⚠️ Section' . ($tabName ? ' ' . $tabName : '') . ' en cours de construction — ne pas modifier');
?>

<div class="alert alert-warning mb-4" role="alert">
    <h5 class="alert-heading mb-2"><?php echo e($title); ?></h5>
    <p class="mb-2">
        Cette section est encore en développement et n’est pas finalisée.
    </p>
    <p class="mb-0">
        Merci de ne pas modifier ces champs pour le moment afin d’éviter incohérences, erreurs de sauvegarde ou comportements inattendus.
        Elle sera activée dès qu’elle sera prête.
    </p>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\_under_construction_notice.blade.php ENDPATH**/ ?>