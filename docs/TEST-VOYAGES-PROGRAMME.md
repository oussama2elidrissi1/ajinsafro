# Guide de test – Programme du voyage (jour par jour)

## Prérequis

1. Exécuter les migrations :
   ```bash
   php artisan migrate
   ```
   Tables créées : `voyages`, `travel_program_days`.

2. Être connecté en tant qu’admin (utilisateur avec `is_admin = true`).

## Accès

- Menu **Circuits** → **Voyages** (ou URL : `/admin/circuits/voyages`).

## Scénarios de test

### 1. Créer un voyage

1. Aller sur la liste des voyages.
2. Cliquer sur **Créer un voyage**.
3. Renseigner **Nom du voyage** (ex. : « Circuit Maroc 8 jours ») et éventuellement **Description**.
4. Cliquer sur **Créer le voyage**.
5. Vérifier la redirection vers la page d’édition du voyage.

### 2. Ajouter des jours au programme

1. Sur la page d’édition d’un voyage, trouver la section **Programme du voyage**.
2. Cliquer sur **+ Ajouter un jour**.
3. Dans le modal, remplir :
   - **Titre du jour** (ex. : « Arrivée à Casablanca »),
   - **Ville / Étape** (ex. : « Casablanca »),
   - **Description détaillée**,
   - **Nombre de nuits** (0 ou 1),
   - **Type de journée** (Arrivée, Visite, Transfert, Libre),
   - **Repas inclus** (Petit-déjeuner, Déjeuner, Dîner).
4. Valider avec **Ajouter le jour**.
5. Vérifier que la carte du jour apparaît dans la section programme (Jour 1 – Titre, ville, type, nuits, aperçu description, repas).
6. Répéter pour plusieurs jours (Jour 2, Jour 3…) : le numéro de jour est attribué automatiquement.

### 3. Modifier un jour

1. Sur une carte de jour, cliquer sur **Modifier**.
2. Dans le modal, modifier un ou plusieurs champs.
3. Cliquer sur **Enregistrer**.
4. Vérifier que la carte affiche les nouvelles valeurs.

### 4. Supprimer un jour

1. Sur une carte de jour, cliquer sur **Supprimer**.
2. Confirmer dans la boîte de dialogue.
3. Vérifier que le jour disparaît et que les jours suivants sont renumérotés (ex. : suppression du Jour 2 → l’ancien Jour 3 devient Jour 2).

### 5. Modifier / supprimer le voyage

1. Dans la section **Informations du voyage**, modifier le nom ou la description puis **Enregistrer les modifications**.
2. Depuis la liste des voyages, utiliser **Modifier** ou **Supprimer** sur une ligne et vérifier le comportement.

## Points de contrôle

- Aucune icône vide dans la sidebar.
- Cartes du programme lisibles (titre, ville, type, nuits, aperçu, repas).
- Modals « Ajouter un jour » et « Modifier le jour » avec les mêmes champs.
- Pas de réservation, hôtel, activité ni tarif : uniquement données descriptives du programme.
