<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Mise à jour</title>
</head>
<body>
<script>
(function () {
    var url = <?php echo json_encode($url, 15, 512) ?>;
    try {
        if (window.parent && window.parent !== window) {
            window.parent.location.href = url;
        } else {
            window.location.href = url;
        }
    } catch (e) {
        window.location.href = url;
    }
})();
</script>
<p class="text-muted small p-3"><?php echo e($message ?? 'Redirection…'); ?></p>
</body>
</html>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservations\embed-parent-refresh.blade.php ENDPATH**/ ?>