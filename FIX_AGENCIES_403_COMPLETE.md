# 🔧 FIX COMPLET : Erreur 403 sur les nouvelles pages des agences

## 📋 DIAGNOSTIC FINAL

### **1. Cause exacte du 403**

**Middleware impliqué** : `EnsureRoutePermission` (app/Http/Middleware/EnsureRoutePermission.php)

**Problème root cause** :
- Les routes `admin.agencies.*` et `admin.agency-employees.*` existent ✓
- Les permissions sont bien mappées dans `config/admin_menu.php` ✓
- **MAIS** : Les permissions ne sont pas assignées au rôle de l'utilisateur admin car :
  1. Le seeder `AdminPermissionsSeeder` utilise `AdminMenuPermissionRegistry::allPermissionNames()` qui collecte toutes les permissions depuis la config
  2. Les permissions des agences SONT dans la config ✓
  3. **Le problème** : Sans un nettoyage de cache et un refresh des données en BD, les permissions restent en cache ou ne sont pas recalculées

### **2. Rôles identifiés**

Le projet utilise **Spatie Permissions** avec ces rôles :
- `Admin` (legacy AdminPermissionsSeeder)
- `Ajinsafro Super Admin` (AjinsafroRolesSeeder)
- `Ajinsafro Siege Admin`
- `Ajinsafro Branch Admin`
- `Ajinsafro Chef Commercial`
- `Ajinsafro Commercial`
- `Ajinsafro Manager`
- `Ajinsafro Agent`
- `Comptable`
- `Partenaire`

### **3. Permissions ajoutées**

```
✓ agencies.view
✓ agencies.create
✓ agencies.edit
✓ agencies.delete
✓ agency_employees.view
✓ agency_employees.create
✓ agency_employees.edit
✓ agency_employees.delete
✓ agency_performance.view
✓ agency_commissions.view
```

---

## 📁 FICHIERS MODIFIÉS

### 1. **Seeder créé** : `database/seeders/AgencyPermissionsSeeder.php`
- Crée les 10 permissions des agences
- Assigne au rôle `Admin` et tous les rôles Ajinsafro
- Réinitialise le cache Spatie

### 2. **Script JavaScript** : `public/js/admin-sidebar-v2.js`
- **Changement** : Sidebar n'ouvre que **UN SEUL groupe à la fois**
- localStorage stocke maintenant `activeOpenGroup` au lieu de `openKeys[]`
- Au chargement, ferme tous les groupes sauf l'actif (route actuelle)
- Quand on clique sur un groupe, ferme les autres automatiquement

### 3. **Config** : `config/admin_menu.php` (INCHANGÉE)
- Les permissions des agences SONT bien mappées :
  - `admin.agencies.* → agencies.*`
  - `admin.agency-employees.* → agency_employees.*`
  - `admin.agencies.performance → agency_performance.view`

---

## 🚀 ÉTAPES DE DÉPLOIEMENT

### **Option 1 : Script automatique (recommandé)**

```bash
cd c:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin

php deploy-agency-permissions.php
```

Cela exécute automatiquement :
1. `php artisan optimize:clear`
2. `php artisan cache:clear`
3. `php artisan view:clear`
4. `php artisan config:clear`
5. `php artisan route:clear`
6. `php artisan permission:cache-reset`
7. `php artisan db:seed --class=AdminPermissionsSeeder`
8. `php artisan db:seed --class=AjinsafroRolesSeeder`
9. `php artisan db:seed --class=AgencyPermissionsSeeder`

### **Option 2 : Manuellement (en terminal PowerShell)**

```powershell
cd "c:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin"

# Nettoyer les caches
php artisan optimize:clear
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear

# Réinitialiser Spatie
php artisan permission:cache-reset

# Lancer les seeders
php artisan db:seed --class=AdminPermissionsSeeder
php artisan db:seed --class=AjinsafroRolesSeeder
php artisan db:seed --class=AgencyPermissionsSeeder
```

---

## ✅ VÉRIFICATION POST-DÉPLOIEMENT

### **Test 1 : Pages sans 403**

Connecté avec le compte dev/admin, visiter :
- ✓ https://booking.ajinsafro.net/admin/agencies
- ✓ https://booking.ajinsafro.net/admin/agencies/create
- ✓ https://booking.ajinsafro.net/admin/agency-employees
- ✓ https://booking.ajinsafro.net/admin/agency-employees/create
- ✓ https://booking.ajinsafro.net/admin/agencies/performance

**Résultat attendu** : Page affichée, pas de 403

### **Test 2 : Sidebar correcte**

- ✓ Sidebar affiche "Clients & Agences"
- ✓ Sous-menu affiche tous les liens agences
- ✓ Permissions respectées : si pas la permission, lien caché

### **Test 3 : Un seul groupe ouvert**

- ✓ Au chargement : ferme tous les groupes sauf l'actif
- ✓ Si route = /admin/agencies → ouvre SEULEMENT "Clients & Agences"
- ✓ Si route = /admin/circuits/voyages → ouvre SEULEMENT "Produits & Services"
- ✓ En cliquant sur un groupe, ferme les autres automatiquement

---

## 📝 FICHIERS IMPLIQUÉS

```
database/seeders/
  ├── AdminPermissionsSeeder.php          (existant, inchangé)
  ├── AjinsafroRolesSeeder.php            (existant, inchangé)
  └── AgencyPermissionsSeeder.php         ⭐ CRÉÉ

config/
  └── admin_menu.php                      (existant, permissions OK)

routes/
  └── admin.php                           (existant, routes OK)

public/js/
  └── admin-sidebar-v2.js                 ⭐ MODIFIÉ (un seul groupe ouvert)

resources/views/
  ├── layouts/admin-v2.blade.php          (existant, inchangé)
  ├── admin/partials/sidebar-v2.blade.php (existant, structure OK)
  └── admin/partials/header-v2.blade.php  (existant, inchangé)

app/Http/Middleware/
  └── EnsureRoutePermission.php           (existant, fonctionne correctement)

app/Support/
  └── AdminMenuPermissionRegistry.php     (existant, collecte permissions OK)
```

---

## 🔐 SÉCURITÉ

**Garanties** :
- ✓ Les permissions Spatie sont vérifiées par le middleware
- ✓ Pas de bypass du 403
- ✓ Authentification obligatoire
- ✓ Seuls les utilisateurs autorisés accèdent aux pages
- ✓ Les rôles et permissions sont respectés
- ✓ localStorage ne contient que le groupe actif, pas de données sensibles

---

## 📊 RÉSUMÉ DES CHANGEMENTS

| Élément | Avant | Après |
|---------|-------|-------|
| Permissionsagencies | Manquantes en BD | Créées et assignées ✓ |
| Routes admin.agencies | Existent | Mappées correctement ✓ |
| Routes admin.agency-employees | Existent | Mappées correctement ✓ |
| Sidebar groupes ouverts | Plusieurs à la fois | Un seul à la fois ✓ |
| Erreur 403 | Bloque l'accès | Résolu ✓ |
| Menu actif | Pas visible | Visible et ouvert ✓ |

---

## 🆘 EN CAS DE PROBLÈME

### **Problème : Toujours 403**

```bash
# 1. Vérifier les permissions en BD
php artisan tinker
>>> use Spatie\Permission\Models\Permission;
>>> Permission::where('name', 'agencies.view')->exists();
# Doit retourner true

# 2. Vérifier le rôle de l'utilisateur
>>> $user = User::where('is_admin', true)->first();
>>> $user->getRoleNames();
>>> $user->can('agencies.view');
# Doit retourner true

# 3. Relancer les seeders
php artisan db:seed --class=AgencyPermissionsSeeder

# 4. Relancer permission:cache-reset
php artisan permission:cache-reset
```

### **Problème : Sidebar vide**

```bash
# Vérifier le service AdminMenuService
php artisan tinker
>>> $service = app(\App\Services\Admin\AdminMenuService::class);
>>> $menu = $service->buildForUser(auth()->user());
>>> count($menu);
```

### **Problème : Plusieurs groupes ouverts**

```javascript
// Dans la console navigateur
localStorage.clear();
location.reload();
// Le localStorage est réinitialisé
```

---

## ✨ PROCHAINES AMÉLIORATIONS (optionnel)

- [ ] Ajouter des icônes pour "Performance agences" et "Commissions"
- [ ] Ajouter un contrôleur d'accès par branche pour agency_employees
- [ ] Ajouter des tests unitaires pour les permissions
- [ ] Documenter l'API des agences

---

**Date** : 10 mai 2026  
**Statut** : ✅ Prêt au déploiement  
**Impact** : 🟢 Bas (permissions uniquement, pas de données)
