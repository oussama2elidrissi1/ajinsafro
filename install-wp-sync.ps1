# ======================================================
# Script d'Installation - Synchronisation WP/Laravel
# ======================================================
# Usage: .\install-wp-sync.ps1
# ======================================================

Write-Host "======================================================" -ForegroundColor Cyan
Write-Host "  Installation WP Bidirectional Sync" -ForegroundColor Cyan
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host ""

# Step 1: Generate secrets
Write-Host "[1/7] Génération des secrets HMAC..." -ForegroundColor Yellow

function Generate-HexSecret {
    param([int]$bytes)
    $randomBytes = New-Object byte[] $bytes
    [Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($randomBytes)
    return [BitConverter]::ToString($randomBytes).Replace("-", "").ToLower()
}

$webhookSecret = Generate-HexSecret -bytes 32  # 64 hex chars
$manualToken = Generate-HexSecret -bytes 16    # 32 hex chars

Write-Host "   ✓ Webhook Secret: $webhookSecret" -ForegroundColor Green
Write-Host "   ✓ Manual Token: $manualToken" -ForegroundColor Green
Write-Host ""

# Step 2: Check if .env exists
Write-Host "[2/7] Configuration .env..." -ForegroundColor Yellow

if (-not (Test-Path ".env")) {
    Write-Host "   ✗ Fichier .env introuvable!" -ForegroundColor Red
    Write-Host "   Copie de .env.example..." -ForegroundColor Yellow
    Copy-Item ".env.example" ".env"
}

# Step 3: Add config to .env
Write-Host "   Ajout de la configuration WP Sync..." -ForegroundColor Yellow

$envContent = Get-Content ".env" -Raw

if ($envContent -notmatch "WP_AUTO_SYNC_ENABLED") {
    $wpConfig = @"

# ======================================================
# WordPress Bidirectional Sync Configuration
# ======================================================
WP_AUTO_SYNC_ENABLED=true
WP_WEBHOOK_SECRET=$webhookSecret
WP_MANUAL_SYNC_TOKEN=$manualToken
WP_TABLE_PREFIX=cFdgeZ_
WP_SITE_URL=https://ajinsafro.com
WP_DB_CONNECTION=wp

"@
    Add-Content ".env" $wpConfig
    Write-Host "   ✓ Configuration ajoutée à .env" -ForegroundColor Green
} else {
    Write-Host "   ⚠ Configuration déjà présente dans .env" -ForegroundColor Yellow
}
Write-Host ""

# Step 4: Clear config cache
Write-Host "[3/7] Nettoyage du cache configuration..." -ForegroundColor Yellow
& php artisan config:clear
Write-Host "   ✓ Cache nettoyé" -ForegroundColor Green
Write-Host ""

# Step 5: Run migration
Write-Host "[4/7] Exécution de la migration..." -ForegroundColor Yellow
& php artisan migrate --force
if ($LASTEXITCODE -eq 0) {
    Write-Host "   ✓ Migration terminée" -ForegroundColor Green
} else {
    Write-Host "   ✗ Erreur migration (code $LASTEXITCODE)" -ForegroundColor Red
}
Write-Host ""

# Step 6: Test WP connection
Write-Host "[5/7] Test de connexion WordPress..." -ForegroundColor Yellow
$testResult = & php artisan tinker --execute="echo app(\App\Repositories\WpRepository::class)->getOption('siteurl');"
if ($testResult) {
    Write-Host "   ✓ Connexion WP OK: $testResult" -ForegroundColor Green
} else {
    Write-Host "   ✗ Connexion WP échouée" -ForegroundColor Red
    Write-Host "   Vérifier config/database.php connexion 'wp'" -ForegroundColor Yellow
}
Write-Host ""

# Step 7: Create WordPress plugin ZIP
Write-Host "[6/7] Création du ZIP plugin WordPress..." -ForegroundColor Yellow

if (Test-Path "wp-plugin/ajinsafro-sync-webhook") {
    $zipPath = "wp-plugin/ajinsafro-sync-webhook.zip"
    
    if (Test-Path $zipPath) {
        Remove-Item $zipPath -Force
    }
    
    Compress-Archive -Path "wp-plugin/ajinsafro-sync-webhook" -DestinationPath $zipPath -Force
    
    if (Test-Path $zipPath) {
        Write-Host "   ✓ Plugin zippé: $zipPath" -ForegroundColor Green
    } else {
        Write-Host "   ✗ Erreur création ZIP" -ForegroundColor Red
    }
} else {
    Write-Host "   ✗ Dossier plugin introuvable" -ForegroundColor Red
}
Write-Host ""

# Step 8: Summary
Write-Host "[7/7] Récapitulatif de l'installation" -ForegroundColor Yellow
Write-Host ""
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host "  Installation Terminée !" -ForegroundColor Green
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Secrets générés:" -ForegroundColor White
Write-Host "  WP_WEBHOOK_SECRET = $webhookSecret" -ForegroundColor Cyan
Write-Host "  WP_MANUAL_SYNC_TOKEN = $manualToken" -ForegroundColor Cyan
Write-Host ""

Write-Host "Prochaines étapes:" -ForegroundColor White
Write-Host "  1. Installer le plugin WordPress:" -ForegroundColor Yellow
Write-Host "     • WP Admin → Extensions → Ajouter → Téléverser" -ForegroundColor Gray
Write-Host "     • Fichier: wp-plugin/ajinsafro-sync-webhook.zip" -ForegroundColor Gray
Write-Host "     • Activer le plugin" -ForegroundColor Gray
Write-Host ""
Write-Host "  2. Configurer le plugin WP:" -ForegroundColor Yellow
Write-Host "     • WP Admin → Réglages → Ajinsafro Sync" -ForegroundColor Gray
Write-Host "     • Laravel URL: https://admin.ajinsafro.com" -ForegroundColor Gray
Write-Host "     • Webhook Secret: $webhookSecret" -ForegroundColor Gray
Write-Host "     • Tester la connexion" -ForegroundColor Gray
Write-Host ""
Write-Host "  3. Tester la synchronisation:" -ForegroundColor Yellow
Write-Host "     php artisan wp:sync status" -ForegroundColor Gray
Write-Host "     php artisan tinker" -ForegroundColor Gray
Write-Host "     >>> \$v = Voyage::create(['name' => 'Test Sync', 'slug' => 'test-sync']);" -ForegroundColor Gray
Write-Host "     >>> \$v->wp_post_id  // Doit afficher un ID WP" -ForegroundColor Gray
Write-Host ""

Write-Host "Documentation:" -ForegroundColor White
Write-Host "  • Quick Start: WP_SYNC_QUICK_START.md" -ForegroundColor Gray
Write-Host "  • README: WP_BIDIRECTIONAL_SYNC_README.md" -ForegroundColor Gray
Write-Host "  • Checklist: WP_SYNC_FINAL_CHECKLIST.md" -ForegroundColor Gray
Write-Host ""

Write-Host "Tests automatisés:" -ForegroundColor White
Write-Host "  php artisan test --filter WpSyncTest" -ForegroundColor Gray
Write-Host ""

Write-Host "======================================================" -ForegroundColor Cyan
Write-Host "✨ Bonne synchronisation ! ✨" -ForegroundColor Green
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host ""
