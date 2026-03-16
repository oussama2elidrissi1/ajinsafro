# Comptes et agences Ajinsafro

## Rôles (Spatie)

| Rôle | Description | Périmètre données |
|------|-------------|-------------------|
| `super_admin` | Accès technique total | Toutes les agences |
| `siege_admin` | Compte global siège Tanger | Toutes les agences |
| `branch_admin` | Compte principal d'une agence | Uniquement son agence |
| `chef_commercial` | Supervision commerciale | Son agence |
| `commercial` | Vente / réservation | Son agence |
| `agent` | Traitement opérationnel | Son agence |

## Seeders

### Fichiers

- **BranchesSeeder** : Agences (Tanger siège, Fès, Casablanca, Marrakech, Bruxelles). Idempotent (`firstOrCreate` par `code`).
- **AjinsafroRolesSeeder** : Crée les 6 rôles et assigne les permissions. À lancer après `AdminPermissionsSeeder` (pour que les permissions existent).
- **BranchAccountsSeeder** : Comptes de démo. Appelle `BranchesSeeder`, puis crée :
  - 1 compte siège global : `siege@ajinsafro.com` (siege_admin)
  - 1 compte agence Tanger : `agence.tanger@ajinsafro.com` (branch_admin)
  - Pour chaque agence (TNG, FES, CAS, MAR, BRU) : 1 chef commercial, 1 commercial, 1 agent
  - Pour Fès, Casablanca, Marrakech, Bruxelles : 1 compte agence (branch_admin) chacun

Mot de passe de test pour tous ces comptes : **password123**

### Commandes

```bash
# Migrations (si pas déjà fait)
php artisan migrate --force

# Rôles uniquement
php artisan db:seed --class=AjinsafroRolesSeeder --force

# Comptes agences (inclut BranchesSeeder)
php artisan db:seed --class=BranchAccountsSeeder --force

# Tout (branches + permissions admin + rôles ajinsafro + comptes agences + autres seeders)
php artisan db:seed --force
```

### Comptes créés par BranchAccountsSeeder

| Email | Rôle | Agence |
|-------|------|--------|
| siege@ajinsafro.com | siege_admin | Tanger (accès global) |
| agence.tanger@ajinsafro.com | branch_admin | Tanger |
| agence.FES@ajinsafro.com | branch_admin | Fès |
| agence.CAS@ajinsafro.com | branch_admin | Casablanca |
| agence.MAR@ajinsafro.com | branch_admin | Marrakech |
| agence.BRU@ajinsafro.com | branch_admin | Bruxelles |
| chef.TNG@ajinsafro.com | chef_commercial | Tanger |
| commercial.TNG@ajinsafro.com | commercial | Tanger |
| agent.TNG@ajinsafro.com | agent | Tanger |
| (idem pour FES, CAS, MAR, BRU) | | |

## Tests manuels

1. **Compte siège global** : Connexion avec `siege@ajinsafro.com` / `password123` → voir toutes les agences, tous les utilisateurs, toutes les réservations/clients.
2. **Compte agence Tanger** : `agence.tanger@ajinsafro.com` → uniquement données agence Tanger.
3. **Compte agence Fès** : `agence.FES@ajinsafro.com` → uniquement données agence Fès.
4. **Chef commercial** : `chef.FES@ajinsafro.com` → agence Fès, même périmètre que branch_admin pour les données.
5. **Agent** : `agent.FES@ajinsafro.com` → agence Fès, droits limités (dashboard, réservations, clients, circuits, etc.).

Les voyages (circuits) restent globaux pour tous les comptes.
