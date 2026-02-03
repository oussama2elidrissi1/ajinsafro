#Requires -Version 5.1

<#
.SYNOPSIS
    Script de déploiement Laravel - Package Builder API (Windows)

.DESCRIPTION
    Déploie les mises à jour Laravel : git pull, clear cache, re-cache, test routes

.PARAMETER SkipGit
    Ignore le git pull

.PARAMETER NoCache
    N'exécute pas le re-cache de production

.EXAMPLE
    .\deploy.ps1
    Déploiement complet

.EXAMPLE
    .\deploy.ps1 -SkipGit
    Déploiement sans git pull

.EXAMPLE
    .\deploy.ps1 -NoCache
    Déploiement sans re-cache
#>

param(
    [switch]$SkipGit,
    [switch]$NoCache
)

# Colors
function Write-Success { param([string]$Message) Write-Host "✓ $Message" -ForegroundColor Green }
function Write-Info { param([string]$Message) Write-Host "➜ $Message" -ForegroundColor Cyan }
function Write-Warning { param([string]$Message) Write-Host "⚠ $Message" -ForegroundColor Yellow }
function Write-Error { param([string]$Message) Write-Host "✗ $Message" -ForegroundColor Red }
function Write-Header { param([string]$Message) Write-Host "`n═══════════════════════════════════════════════════════" -ForegroundColor Green; Write-Host "   $Message" -ForegroundColor Green; Write-Host "═══════════════════════════════════════════════════════`n" -ForegroundColor Green }

# Configuration
$ErrorActionPreference = "Stop"
$AppDir = Get-Location

Write-Header "Laravel Package Builder API - Déploiement"

# Étape 1: Git pull
if (-not $SkipGit) {
    Write-Info "[1/6] Git pull..."
    try {
        git pull origin main
        Write-Success "Git pull terminé"
    } catch {
        Write-Error "Erreur lors du git pull: $_"
        exit 1
    }
} else {
    Write-Warning "[1/6] Git pull ignoré (paramètre -SkipGit)"
}

# Étape 2: Composer
Write-Info "[2/6] Vérification des dépendances Composer..."
if (Test-Path "composer.json") {
    if (-not (Test-Path "vendor")) {
        Write-Info "Installation des dépendances..."
        composer install --no-dev --optimize-autoloader
    } else {
        Write-Info "Dépendances déjà installées"
    }
    Write-Success "Composer OK"
} else {
    Write-Warning "Pas de composer.json trouvé, ignoré"
}

# Étape 3: Clear caches
Write-Info "[3/6] Nettoyage des caches Laravel..."
try {
    php artisan route:clear
    php artisan config:clear
    php artisan cache:clear
    php artisan view:clear
    Write-Success "Caches nettoyés"
} catch {
    Write-Error "Erreur lors du nettoyage des caches: $_"
    exit 1
}

# Étape 4: Migrations
Write-Info "[4/6] Vérification des migrations..."
$runMigrations = Read-Host "Exécuter les migrations ? (y/N)"
if ($runMigrations -eq "y" -or $runMigrations -eq "Y") {
    try {
        php artisan migrate --force
        Write-Success "Migrations exécutées"
    } catch {
        Write-Error "Erreur lors des migrations: $_"
        exit 1
    }
} else {
    Write-Info "Migrations ignorées"
}

# Étape 5: Re-cache pour production
if (-not $NoCache) {
    Write-Info "[5/6] Re-cache pour production..."
    try {
        php artisan route:cache
        php artisan config:cache
        php artisan view:cache
        Write-Success "Caches de production générés"
    } catch {
        Write-Error "Erreur lors du re-cache: $_"
        exit 1
    }
} else {
    Write-Warning "[5/6] Re-cache ignoré (paramètre -NoCache)"
}

# Étape 6: Vérification des routes
Write-Info "[6/6] Vérification des routes API..."
try {
    php artisan route:list --path=api/public | Select-Object -First 20
    Write-Success "Routes vérifiées"
} catch {
    Write-Error "Erreur lors de la vérification des routes: $_"
}

# Résumé final
Write-Header "✓ DÉPLOIEMENT TERMINÉ AVEC SUCCÈS"

Write-Host "Testez maintenant l'API :" -ForegroundColor Cyan
Write-Host "Invoke-WebRequest -Uri 'https://booking.ajinsafro.net/api/public/tours/1/package-state' -Headers @{'Accept'='application/json'}" -ForegroundColor Yellow
Write-Host ""
Write-Host "Logs Laravel : " -NoNewline -ForegroundColor Cyan
Write-Host "Get-Content storage\logs\laravel.log -Tail 50 -Wait" -ForegroundColor Yellow
Write-Host ""

exit 0
