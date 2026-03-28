# Interface admin par rôle et agence

Ce document décrit l’adaptation de l’interface admin selon le rôle et l’agence de l’utilisateur connecté.

---

## 1. Fichiers modifiés

### Config
- **`config/admin_menu.php`**  
  - Paramètres : entrées de menu avec `roles` pour restreindre par rôle (Agences, Rôles & Permissions, Paramètres généraux, Home page, Sécurité).

### Vues
- **`resources/views/layouts/partials/sidebar-ajinsafro.blade.php`**  
  - Filtre des enfants du menu par `roles` en plus des permissions.
- **`resources/views/admin/dashboard/index.blade.php`**  
  - Dashboard avec cartes (réservations, clients, en cours, validées) et lien « Vue globale » pour les comptes siège.
- **`resources/views/admin/branches/index.blade.php`**  
  - Bouton « Nouvelle agence » affiché uniquement si `$canCreateBranch` (super_admin / siege_admin).

### Contrôleurs
- **`app/Http/Controllers/Admin/DashboardController.php`**  
  - Injection de `BranchScopeService`.  
  - `index()` : statistiques scopées (réservations, clients) + `can_see_all_branches`.  
  - `vueGlobale(Request $request)` : toutes les stats (réservations, clients, messages, CA, graphiques, dernières réservations, top voyages, agences) filtrées par `visibleBranchIds`.  
  - Bloc « Agences » / « Dernières agences » basé sur les branches visibles.
- **`app/Http/Controllers/Admin/ClientController.php`**  
  - Vérification d’accès par agence dans `show`, `edit`, `update`, `destroy`.  
  - `trashed`, `restore`, `forceDelete`, `bulkAction` : scope ou vérification sur les clients visibles.  
  - `index`, `create`, `edit` : liste des utilisateurs pour assignation scopée avec `scopeUsers`.
- **`app/Http/Controllers/Admin/ReservationsController.php`**  
  - `create` et `edit` : liste des clients pour le formulaire scopée avec `scopeClients`.
- **`app/Http/Controllers/Admin/BranchController.php`**  
  - `create()` et `store()` : réservés à `canSeeAllBranches` (redirection + message si accès refusé).  
  - `index()` : passage de `canCreateBranch` à la vue.
- **`app/Http/Controllers/Admin/UserAccessController.php`**  
  - `ensureUserInScope($currentUser, $targetUser)` : vérification que l’utilisateur cible est dans le périmètre (branch / siège).  
  - `edit`, `update`, `toggleActive`, `destroy` : appel à `ensureUserInScope` avant toute action.

### Seeders
- **`database/seeders/AjinsafroRolesSeeder.php`**  
  - Rôle **agent** : ajout des permissions **messagerie.*** (en plus de dashboard, réservations, clients, circuits, operations, visa).

---

## 2. Logique d’affichage par rôle

- **Périmètre données**  
  - Géré par `BranchScopeService` :  
    - `visibleBranchIds($user)` : `null` = toutes les agences (super_admin, siege_admin), sinon `[branch_id]` ou `[]`.  
    - `scopeReservations`, `scopeClients`, `scopeUsers` appliquent ce périmètre aux requêtes.

- **Menu (sidebar)**  
  - Une entrée est visible si :  
    - la route existe ;  
    - si `roles` est défini, l’utilisateur a au moins un de ces rôles ;  
    - si `permission` est défini, l’utilisateur a la permission.  
  - Les permissions par rôle sont définies dans `AjinsafroRolesSeeder` (et `AdminPermissionsSeeder` pour les permissions de base).

- **Pages et actions**  
  - Middleware `route.permission` (EnsureRoutePermission) : accès à une route admin selon les permissions.  
  - Contrôleurs : vérification d’accès par agence (client, réservation, utilisateur cible) et redirection ou 403 si hors périmètre.

---

## 3. Menus visibles par rôle

Résumé (les sous-entrées dépendent des permissions détaillées dans `config/admin_menu.php` et du seeder de rôles).

| Section / entrée   | super_admin | siege_admin | branch_admin | chef_commercial | commercial | agent |
|--------------------|-------------|-------------|--------------|-----------------|------------|-------|
| Dashboard          | Oui         | Oui         | Oui          | Oui             | Oui        | Oui   |
| Réservations      | Oui         | Oui         | Oui          | Oui             | Oui        | Oui   |
| Clients            | Oui         | Oui         | Oui          | Oui             | Oui        | Oui   |
| Produits           | Oui         | Oui         | Oui          | Oui             | Non        | Non   |
| Circuits           | Oui         | Oui         | Oui          | Oui             | Oui        | Oui   |
| Hébergements       | Oui         | Oui         | Oui          | Oui             | Non        | Non   |
| Opérations terrain | Oui         | Oui         | Oui          | Oui             | Non        | Oui   |
| Visa               | Oui         | Oui         | Oui          | Oui             | Non        | Oui   |
| Finance            | Oui         | Oui         | Oui          | Oui             | Non        | Non   |
| Réseau partenaires | Oui         | Oui         | Oui          | Non             | Non        | Non   |
| Reporting          | Oui         | Oui         | Oui          | Oui             | Non        | Non   |
| WordPress          | Oui         | Oui         | Oui          | Non             | Non        | Non   |
| Messagerie        | Oui         | Oui         | Oui          | Oui             | Oui        | Oui   |
| Paramètres         | Oui         | Oui         | Oui          | Oui             | Non        | Non   |
| → Agences          | Oui         | Oui         | Oui          | Oui             | Non        | Non   |
| → Utilisateurs     | Oui         | Oui         | Oui          | Oui             | Non        | Non   |
| → Rôles & Permissions | Oui      | Oui         | Non          | Non             | Non        | Non   |
| → Param. généraux / Home / Sécurité | Oui (Sécurité: super_admin) | Oui (sans Sécurité) | Non | Non | Non | Non |

---

## 4. Dashboards visibles par rôle

- **Tous les rôles**  
  - **Dashboard principal** (`/admin/dashboard`) : cartes Réservations, Clients, En cours, Validées (données scopées).  
  - Lien « Vue globale » affiché uniquement si `canSeeAllBranches` (super_admin, siege_admin).

- **Vue globale** (`/admin/dashboard/vue-globale`)  
  - Accessible à tous ceux qui ont la permission `dashboard.overview.view`.  
  - Contenu :  
    - **Réseau (siège)** : tous les chiffres, toutes les agences, dernières réservations, top voyages, dernières agences.  
    - **Agence** : mêmes blocs mais limités aux réservations / clients / messages / CA de l’agence ; bloc agences = son agence uniquement.

- **Statistiques / Alertes**  
  - Pages dédiées inchangées ; à scoper côté contrôleur si elles affichent des données par agence.

---

## 5. Tester chaque compte de démo

Mot de passe commun : **password123**.

1. **super_admin**  
   - Créer un utilisateur avec le rôle `super_admin` (ou utiliser un compte existant).  
   - Vérifier : menu complet, Vue globale avec toutes les agences, liste agences avec bouton « Nouvelle agence », Paramètres (Rôles, Sécurité, etc.), listes réservations/clients/utilisateurs non filtrées par agence.

2. **siege_admin** (ex. `siege@ajinsafro.com`)  
   - Même périmètre que super_admin (toutes les agences).  
   - Vérifier : Vue globale, création d’agence, pas d’accès à « Sécurité » (réservé à super_admin si vous l’avez restreint).

3. **branch_admin** (ex. `agence.tanger@ajinsafro.com`, `agence.FES@ajinsafro.com`)  
   - Connexion → menu sans Rôles & Permissions, Paramètres généraux, Home page, Sécurité.  
   - Agences : une seule agence listée, pas de bouton « Nouvelle agence ».  
   - Dashboard et Vue globale : chiffres limités à son agence.  
   - Réservations / Clients : uniquement ceux de l’agence.  
   - Utilisateurs : uniquement ceux de l’agence.

4. **chef_commercial** (ex. `chef.TNG@ajinsafro.com`, `chef.FES@ajinsafro.com`)  
   - Même périmètre données qu’un branch_admin (son agence).  
   - Menu : Paramètres avec Agences (sa branche) et Utilisateurs (son équipe). Pas de Rôles & Permissions, Param. généraux, Home, Sécurité. Pas de Réseau partenaires ni WordPress.

5. **commercial** (ex. `commercial.TNG@ajinsafro.com`)  
   - Menu : Dashboard, Réservations, Clients, Circuits, Messagerie.  
   - Données : son agence uniquement (réservations, clients).  
   - Pas d’Opérations, Visa, Finance, Paramètres, etc.

6. **agent** (ex. `agent.TNG@ajinsafro.com`)  
   - Menu : Dashboard, Réservations, Clients, Circuits, Opérations, Visa, Messagerie.  
   - Données : son agence uniquement.  
   - Pas de Finance, Réseau partenaires, Paramètres, etc.

Après modification des rôles ou permissions, réexécuter :

```bash
php artisan db:seed --class=AjinsafroRolesSeeder --force
```

Puis vider le cache des permissions si utilisé :

```bash
php artisan permission:cache-reset
```

---

## 6. Récapitulatif des règles métier

- **super_admin / siege_admin** : voient tout le réseau (toutes agences), peuvent créer des agences, gérer rôles/sécurité (super_admin pour Sécurité).
- **branch_admin** : voit uniquement son agence, interface complète agence (réservations, clients, circuits, etc.), Paramètres limités (Agences en lecture/édition de sa branche, Utilisateurs de l’agence).
- **chef_commercial** : même périmètre données que branch_admin (son agence), menu sans partie paramètres globale ni partenaires/wordpress.
- **commercial** : son agence, focus clients / réservations / voyages / messagerie.
- **agent** : son agence, modules opérationnels (réservations, clients, circuits, opérations, visa, messagerie).
- **Tanger** :  
  - siege_admin Tanger = vue réseau (toutes agences).  
  - branch_admin Tanger = vue agence Tanger uniquement.

Les listes (réservations, clients, utilisateurs), les formulaires (selects clients, utilisateurs assignés) et les dashboards utilisent tous les données filtrées par `BranchScopeService` selon le rôle et l’agence du compte connecté.
