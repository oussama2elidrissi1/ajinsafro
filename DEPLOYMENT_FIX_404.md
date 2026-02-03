# Guide de correction du 404 en production

## ✅ Statut local : FONCTIONNEL
L'API retourne bien **200 + JSON** en local :
```
GET http://127.0.0.1:8000/api/public/tours/1/package-state
Status: 200 OK
```

## ❌ Problème en production
L'URL `https://booking.ajinsafro.net/api/public/tours/1/package-state` retourne **404**.

---

## 🔍 Diagnostic : Problème de déploiement

Le code Laravel est correct, mais le serveur de production n'a pas été mis à jour. Voici les étapes pour corriger :

---

## 🚀 Solution : Déploiement et cache

### Étape 1 : Uploader les fichiers modifiés

Assurez-vous que ces fichiers sont bien sur le serveur de production :

```
routes/api.php  ← MODIFIÉ (paramètres {voyageId} au lieu de {voyage_id})
```

**Commande Git :**
```bash
# Sur votre machine locale
git status
git add routes/api.php
git commit -m "fix: API routes parameter names for package builder"
git push origin main

# Sur le serveur de production
cd /path/to/booking.ajinsafro.net
git pull origin main
```

---

### Étape 2 : Nettoyer les caches Laravel (CRITIQUE)

Sur le **serveur de production**, exécutez ces commandes dans l'ordre :

```bash
cd /path/to/booking.ajinsafro.net

# 1. Nettoyer le cache des routes
php artisan route:clear

# 2. Nettoyer le cache de configuration
php artisan config:clear

# 3. Nettoyer le cache de l'application
php artisan cache:clear

# 4. Nettoyer le cache des vues compilées
php artisan view:clear

# 5. IMPORTANT : Re-cache des routes pour la production
php artisan route:cache

# 6. Re-cache de la config pour la production
php artisan config:cache
```

**⚠️ Note sur `route:cache` :**
- En production, Laravel utilise souvent `php artisan route:cache` pour optimiser les performances
- Ce cache doit être **regénéré** après chaque modification de routes
- Si vous n'utilisez pas `route:cache` en prod, omettez cette étape

---

### Étape 3 : Vérifier les routes sur le serveur

```bash
# Lister les routes pour vérifier qu'elles sont bien enregistrées
php artisan route:list --path=api/public
```

**Attendu :**
```
POST   api/public/checkout/create
POST   api/public/package/session/{sessionId}/action
GET    api/public/tours/{voyageId}/package-state
```

---

### Étape 4 : Redémarrer les services (si nécessaire)

Selon votre configuration serveur :

**Si vous utilisez PHP-FPM :**
```bash
sudo systemctl restart php8.2-fpm
# ou
sudo service php8.2-fpm restart
```

**Si vous utilisez Nginx :**
```bash
sudo systemctl reload nginx
# ou
sudo nginx -s reload
```

**Si vous utilisez Apache :**
```bash
sudo systemctl restart apache2
# ou
sudo service apache2 restart
```

**Si vous utilisez Laravel Octane :**
```bash
php artisan octane:reload
```

---

### Étape 5 : Tester l'API en production

```bash
# Depuis le serveur ou votre machine locale
curl -i https://booking.ajinsafro.net/api/public/tours/1/package-state \
  -H "Accept: application/json"
```

**Attendu :**
```
HTTP/2 200
Content-Type: application/json

{"success":true,"data":{"tour":{...}}}
```

---

## 🛠️ Troubleshooting

### Si le 404 persiste après tout ça

#### A. Vérifier le .htaccess (Apache)

Le fichier `public/.htaccess` doit contenir :

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ index.php [L]
</IfModule>
```

#### B. Vérifier la config Nginx

Votre `nginx.conf` ou site config doit contenir :

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ ^/api {
    try_files $uri $uri/ /index.php?$query_string;
}
```

#### C. Vérifier les permissions

```bash
# Les permissions doivent être correctes
cd /path/to/booking.ajinsafro.net
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### D. Vérifier les logs Laravel

```bash
# Consulter les erreurs Laravel
tail -n 50 storage/logs/laravel.log

# Consulter les erreurs Nginx
sudo tail -n 50 /var/log/nginx/error.log

# Consulter les erreurs Apache
sudo tail -n 50 /var/log/apache2/error.log
```

#### E. Vérifier le fichier .env en production

Assurez-vous que votre `.env` contient :

```bash
APP_ENV=production
APP_DEBUG=false  # ou true temporairement pour debug
APP_URL=https://booking.ajinsafro.net

# Route caching activé ?
ROUTE_CACHE_PATH=bootstrap/cache/routes-v7.php  # Si utilisé
```

#### F. Mode de maintenance

Vérifiez que l'app n'est pas en maintenance :

```bash
php artisan up
```

---

## 📋 Checklist finale

- [ ] Fichier `routes/api.php` uploadé sur le serveur
- [ ] `php artisan route:clear` exécuté
- [ ] `php artisan config:clear` exécuté
- [ ] `php artisan cache:clear` exécuté
- [ ] `php artisan route:cache` exécuté (si utilisé en prod)
- [ ] Services redémarrés (PHP-FPM/Nginx/Apache)
- [ ] `php artisan route:list --path=api/public` affiche les 3 routes
- [ ] `curl https://booking.ajinsafro.net/api/public/tours/1/package-state` retourne 200
- [ ] Le JSON contient `{"success":true,"data":{...}}`

---

## 🎯 Commandes rapides (copier-coller pour prod)

```bash
# Depuis le serveur de production
cd /path/to/booking.ajinsafro.net

# Git pull (si utilisé)
git pull origin main

# Clear all caches
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Re-cache pour prod (optionnel mais recommandé)
php artisan route:cache
php artisan config:cache

# Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl reload nginx

# Vérifier
php artisan route:list --path=api/public
curl -i https://booking.ajinsafro.net/api/public/tours/1/package-state -H "Accept: application/json"
```

---

## ✅ Résultat attendu final

```bash
$ curl -i https://booking.ajinsafro.net/api/public/tours/1/package-state

HTTP/2 200
Content-Type: application/json

{"success":true,"data":{"tour":{"id":1,"name":"Séjour Dubaï 7 jours (6 nuits)","slug":"sejour-dubai-7-jours-6-nuits",...}}}
```

---

## 📞 Support

Si le problème persiste après toutes ces étapes, partagez :
1. Le contenu de `storage/logs/laravel.log` (dernières lignes)
2. Le résultat de `php artisan route:list --path=api/public` sur le serveur
3. La config Nginx/Apache de votre site
4. Les permissions des fichiers (`ls -la storage/ bootstrap/cache/`)
