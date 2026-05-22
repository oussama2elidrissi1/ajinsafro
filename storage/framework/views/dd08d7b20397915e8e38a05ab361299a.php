<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte partenaire activé</title>
    <style>
        body { font-family: sans-serif; line-height: 1.5; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { margin-bottom: 24px; }
        .box { background: #f8f9fa; border-radius: 8px; padding: 24px; margin: 20px 0; }
        .btn { display: inline-block; background: #0d6efd; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 6px; margin-top: 16px; }
        .footer { margin-top: 32px; font-size: 12px; color: #6c757d; }
    </style>
</head>
<body>
    <div class="header">
        <strong><?php echo e(config('app.name')); ?></strong>
    </div>
    <p>Bonjour <?php echo e($partner->nom_responsable); ?>,</p>
    <p>Votre demande d’inscription en tant que partenaire a été <strong>validée</strong>.</p>
    <div class="box">
        <p class="mb-0">Vous pouvez désormais vous connecter à votre espace partenaire avec l’adresse email et le mot de passe que vous avez choisis lors de l’inscription.</p>
    </div>
    <p>
        <a href="<?php echo e(url('/login')); ?>" class="btn">Accéder à la connexion</a>
    </p>
    <p>Une fois connecté, vous pourrez gérer vos réservations et vos clients.</p>
    <div class="footer">
        <p>Cet email a été envoyé par <?php echo e(config('app.name')); ?>. Merci de ne pas répondre directement à ce message.</p>
    </div>
</body>
</html>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\emails\partner-account-validated.blade.php ENDPATH**/ ?>