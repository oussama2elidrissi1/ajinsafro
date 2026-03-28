# Redirections login / logout – Ajinsafro

## Cause exacte du bug

1. **AdminMiddleware n’acceptait que `is_admin = true`**  
   Les comptes créés par `BranchAccountsSeeder` ont **`is_admin => false`** et sont identifiés par des **rôles Spatie** (super_admin, siege_admin, branch_admin, chef_commercial, commercial, agent). En allant sur `/login`, un invité se connecte → redirection vers `/admin/dashboard` (HOME) → AdminMiddleware refusait l’accès car `is_admin` était false → redirection vers `/`. Résultat : on avait l’impression que « /login envoie vers / ».

2. **GET /logout ne déconnectait pas**  
   La route GET `/logout` faisait seulement `redirect()->route('login')`. La session restait active, donc en arrivant sur `/login` l’utilisateur était encore connecté et repartait vers admin ou `/` selon son rôle.

3. **`?show_login=1` trop strict**  
   Le test était `$request->query('show_login') === '1'`. Si le paramètre était envoyé autrement (ex. `true`, `yes`) ou perdu (proxy, cache), la page login ne s’affichait pas et l’utilisateur était redirigé.

---

## Fichiers modifiés

| Fichier | Modification |
|--------|---------------|
| **app/Models/User.php** | Ajout de `canAccessAdmin()` : vrai si `is_admin` ou si l’utilisateur a un des rôles admin (super_admin, siege_admin, branch_admin, chef_commercial, commercial, agent + noms alternatifs). |
| **app/Http/Middleware/AdminMiddleware.php** | Remplacement de `$request->user()->is_admin` par `$request->user()->canAccessAdmin()` pour autoriser l’accès admin selon les rôles. |
| **app/Http/Middleware/RedirectIfAuthenticated.php** | Utilisation de `canAccessAdmin()` pour la redirection ; `show_login` accepte toute valeur non vide (`$request->filled('show_login')`) ; simplification des cas (admin → HOME, partenaire → partner, autre → `/`). |
| **routes/web.php** | Route GET `logout` : appelle `Auth::logout()`, `session()->invalidate()`, `session()->regenerateToken()`, puis `redirect()->route('login')`. Nom de route : `logout.get`. |
| **app/Http/Controllers/Auth/LoginController.php** | Commentaires sur la redirection après login (partenaire vs admin) ; comportement inchangé (admin → `redirectPath()` = HOME). |

---

## Logique finale de redirection

### GET /login

- **Invité (non connecté)**  
  → Affichage du formulaire de connexion.

- **Connecté sans `?show_login`**  
  - `canAccessAdmin()` → **redirect `/admin/dashboard`**.  
  - Partenaire (rôle Partenaire) → **redirect `partner.dashboard`** ou **`partner.pending`**.  
  - Ni admin ni partenaire → **redirect `/`**.

- **Connecté avec `?show_login=1` (ou toute valeur)**  
  → Affichage du formulaire login (pour changer de compte).

### POST /login (connexion réussie)

- Partenaire (et partenaire validé) → **redirect `partner.dashboard`**.  
- Partenaire en attente → **redirect `partner.pending`**.  
- Tout autre compte (dont tous les rôles admin) → **redirect `/admin/dashboard`** (HOME).

### Logout

- **POST /logout** (formulaire avec CSRF)  
  → Déconnexion Laravel puis redirect vers `login` (comportement par défaut de `AuthenticatesUsers`).

- **GET /logout**  
  → `Auth::logout()`, invalidation session, régénération du token, puis **redirect `login`**. Utile pour lien “Déconnexion” ouvert en GET ou favori.

### Routes admin (middleware `admin`)

- Utilisateur non connecté → **redirect `login`**.  
- Connecté mais `!canAccessAdmin()` → **redirect `/`** avec message “Access denied.”.  
- Connecté et `canAccessAdmin()` → accès autorisé.

---

## Comment tester

### 1. Invité

- Ouvrir **/login** (en navigation privée ou après déconnexion).  
  → La page de login s’affiche.  
- Se connecter avec un compte admin (ex. `siege@ajinsafro.com` / `password123`).  
  → Redirection vers **/admin/dashboard**.  
- Se déconnecter (bouton Déconnexion = POST logout ou aller sur **/logout** en GET).  
  → Redirection vers **/login**, formulaire affiché.

### 2. Compte admin (rôles sans is_admin)

- Se connecter avec `agence.FES@ajinsafro.com` (branch_admin, `is_admin = false`).  
  → Redirection vers **/admin/dashboard** (plus de redirect vers `/`).  
- Aller sur **/login** sans paramètre.  
  → Redirection vers **/admin/dashboard**.  
- Aller sur **/login?show_login=1**.  
  → La page login s’affiche (changer de compte).  
- Ouvrir **/logout** (GET).  
  → Déconnexion puis **/login**.

### 3. Partenaire

- Se connecter avec un compte partenaire validé.  
  → Redirection vers **partner.dashboard**.  
- Aller sur **/login**.  
  → Redirection vers l’espace partenaire (ou pending si non validé).  
- **/login?show_login=1**  
  → Affiche le formulaire login.

### 4. Rôles à vérifier

- **super_admin / siege_admin** → /login → **/admin/dashboard**.  
- **branch_admin / chef_commercial / commercial / agent** → idem (tous ont `canAccessAdmin()`).  
- **Partenaire** → après login → **partner.dashboard** ou **partner.pending**.

### 5. Pas de boucle

- Déconnexion (POST ou GET **/logout**) → **/login** (page affichée, invité).  
- Accès à **/admin/dashboard** sans être connecté → **/login**.  
- Compte admin sur **/login** (sans `show_login`) → **/admin/dashboard** (une seule redirection).

---

## Constantes et guards

- **HOME** : `RouteServiceProvider::HOME` = **`/admin/dashboard`** (redirection par défaut après login pour les comptes admin).  
- **Guards** : guard web par défaut (pas de guard personnalisé pour login).  
- **Auth** : `Auth::routes()` (LoginController, pas Fortify/Breeze/Jetstream).
