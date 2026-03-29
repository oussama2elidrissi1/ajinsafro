<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Mise à jour</title>
</head>
<body>
<script>
(function () {
    var url = @json($url);
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
<p class="text-muted small p-3">{{ $message ?? 'Redirection…' }}</p>
</body>
</html>
