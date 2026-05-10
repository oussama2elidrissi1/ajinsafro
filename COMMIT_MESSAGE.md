## 🔧 Fix: Erreur 403 sur pages agences + Sidebar multi-groupes

### 🎯 Problème résolu
- [x] Erreur 403 "Unauthorized action" sur /admin/agencies et /admin/agency-employees
- [x] Permissions des agences non assignées aux rôles admin
- [x] Sidebar ouvrait plusieurs groupes simultanement (UX mauvaise)
- [x] Groupe de menu actif pas toujours visible

### ✅ Corrections apportées

#### 1. **Permissions des agences** 
Créé : `database/seeders/AgencyPermissionsSeeder.php`
- Crée 10 permissions : agencies.*, agency_employees.*, agency_performance.*, agency_commissions.*
- Assigne au rôle Admin et tous les rôles Ajinsafro
- Réinitialise cache Spatie permissions

#### 2. **Sidebar Admin V2**
Modifié : `public/js/admin-sidebar-v2.js`
- Un seul groupe ouvert à la fois
- localStorage stocke activeOpenGroup au lieu de openKeys[]
- Au chargement : ferme tous les groupes sauf l'actif (route actuelle)
- En cliquant sur un groupe : ferme les autres automatiquement

#### 3. **Déploiement**
Créé : `deploy-agency-permissions.php`
- Script automatisé pour nettoyer les caches et lancer les seeders
- Génère des rapports colorisés dans le terminal

### 📁 Fichiers modifiés

```
database/seeders/AgencyPermissionsSeeder.php     [NEW]
public/js/admin-sidebar-v2.js                   [MODIFIED]
deploy-agency-permissions.php                   [NEW]
FIX_AGENCIES_403_COMPLETE.md                    [NEW]
```

### 🚀 Instructions de déploiement

```bash
php deploy-agency-permissions.php
```

Ou manuellement :
```bash
php artisan optimize:clear
php artisan cache:clear
php artisan permission:cache-reset
php artisan db:seed --class=AgencyPermissionsSeeder
```

### ✅ Vérifications post-déploiement

- [ ] https://booking.ajinsafro.net/admin/agencies (pas de 403)
- [ ] https://booking.ajinsafro.net/admin/agency-employees (pas de 403)
- [ ] Sidebar affiche "Clients & Agences"
- [ ] Un seul groupe menu ouvert à la fois
- [ ] Groupe actif visible et bien ouvert

### 🔐 Sécurité

- Permissions Spatie vérifiées via middleware EnsureRoutePermission
- Pas de bypass du 403
- Authentification obligatoire
- localStorage ne contient que le groupe actif

### 📝 Notes techniques

- AjinsafroRolesSeeder inclut déjà agencies.* dans ses filtres ✓
- Config admin_menu.php a les permissions bien mappées ✓
- Routes admin.php existent et sont nommées correctement ✓
- Middleware EnsureRoutePermission valide correctement ✓

---

**Affected routes:**
- GET  /admin/agencies
- POST /admin/agencies
- GET  /admin/agencies/create
- GET  /admin/agencies/{agency}
- GET  /admin/agencies/{agency}/edit
- PUT  /admin/agencies/{agency}
- DELETE /admin/agencies/{agency}
- GET  /admin/agencies/performance
- GET  /admin/agency-employees
- POST /admin/agency-employees
- GET  /admin/agency-employees/create
- GET  /admin/agency-employees/{employee}
- GET  /admin/agency-employees/{employee}/edit
- PUT  /admin/agency-employees/{employee}
- DELETE /admin/agency-employees/{employee}
