#!/bin/bash

###############################################################################
# Script de déploiement Laravel - Package Builder API
# Usage: ./deploy.sh [--skip-git] [--no-cache]
###############################################################################

set -e  # Exit on error

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Configuration
APP_DIR=$(pwd)
PHP_VERSION="8.2"  # Ajustez selon votre version PHP

echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}   Laravel Package Builder API - Déploiement${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo ""

# Étape 1: Git pull (sauf si --skip-git)
if [[ "$1" != "--skip-git" ]]; then
    echo -e "${YELLOW}[1/7] Git pull...${NC}"
    git pull origin main || {
        echo -e "${RED}✗ Erreur lors du git pull${NC}"
        exit 1
    }
    echo -e "${GREEN}✓ Git pull terminé${NC}"
    echo ""
else
    echo -e "${YELLOW}[1/7] Git pull ignoré (--skip-git)${NC}"
    echo ""
fi

# Étape 2: Composer install (si besoin)
if [ -f "composer.json" ]; then
    echo -e "${YELLOW}[2/7] Vérification des dépendances Composer...${NC}"
    if [ ! -d "vendor" ]; then
        echo "Installation des dépendances..."
        composer install --no-dev --optimize-autoloader
    else
        echo "Dépendances déjà installées"
    fi
    echo -e "${GREEN}✓ Composer OK${NC}"
    echo ""
else
    echo -e "${YELLOW}[2/7] Pas de composer.json trouvé, ignoré${NC}"
    echo ""
fi

# Étape 3: Clear all caches
echo -e "${YELLOW}[3/7] Nettoyage des caches Laravel...${NC}"
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
echo -e "${GREEN}✓ Caches nettoyés${NC}"
echo ""

# Étape 4: Migrations (si nécessaire)
echo -e "${YELLOW}[4/7] Vérification des migrations...${NC}"
read -p "Exécuter les migrations ? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate --force
    echo -e "${GREEN}✓ Migrations exécutées${NC}"
else
    echo "Migrations ignorées"
fi
echo ""

# Étape 5: Re-cache pour production (sauf si --no-cache)
if [[ "$1" != "--no-cache" ]] && [[ "$2" != "--no-cache" ]]; then
    echo -e "${YELLOW}[5/7] Re-cache pour production...${NC}"
    php artisan route:cache
    php artisan config:cache
    php artisan view:cache
    echo -e "${GREEN}✓ Caches de production générés${NC}"
    echo ""
else
    echo -e "${YELLOW}[5/7] Re-cache ignoré (--no-cache)${NC}"
    echo ""
fi

# Étape 6: Restart services
echo -e "${YELLOW}[6/7] Redémarrage des services...${NC}"

# Détection automatique du service PHP-FPM
if systemctl is-active --quiet "php${PHP_VERSION}-fpm"; then
    echo "Redémarrage de PHP-FPM..."
    sudo systemctl restart "php${PHP_VERSION}-fpm"
    echo -e "${GREEN}✓ PHP-FPM redémarré${NC}"
elif systemctl is-active --quiet "php-fpm"; then
    echo "Redémarrage de PHP-FPM..."
    sudo systemctl restart php-fpm
    echo -e "${GREEN}✓ PHP-FPM redémarré${NC}"
else
    echo -e "${YELLOW}⚠ PHP-FPM non détecté ou déjà arrêté${NC}"
fi

# Détection automatique du serveur web
if systemctl is-active --quiet nginx; then
    echo "Reload de Nginx..."
    sudo systemctl reload nginx
    echo -e "${GREEN}✓ Nginx reloadé${NC}"
elif systemctl is-active --quiet apache2; then
    echo "Redémarrage d'Apache..."
    sudo systemctl restart apache2
    echo -e "${GREEN}✓ Apache redémarré${NC}"
else
    echo -e "${YELLOW}⚠ Aucun serveur web (Nginx/Apache) détecté${NC}"
fi
echo ""

# Étape 7: Vérification des routes
echo -e "${YELLOW}[7/7] Vérification des routes API...${NC}"
php artisan route:list --path=api/public | head -n 20
echo -e "${GREEN}✓ Routes vérifiées${NC}"
echo ""

# Résumé final
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}   ✓ DÉPLOIEMENT TERMINÉ AVEC SUCCÈS${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo ""
echo -e "Testez maintenant l'API :"
echo -e "${YELLOW}curl -i https://booking.ajinsafro.net/api/public/tours/1/package-state -H 'Accept: application/json'${NC}"
echo ""
echo -e "Logs Laravel : ${YELLOW}tail -f storage/logs/laravel.log${NC}"
echo ""

exit 0
