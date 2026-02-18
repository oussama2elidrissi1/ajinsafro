# Guide de déploiement et test - Hôtels & Transferts par Jour

## 📋 Checklist de déploiement

### 1. Exécuter les migrations
```bash
php artisan migrate

# Ou spécifiquement:
php artisan migrate --path=database/migrations/2026_02_18_000001_create_program_day_transfers_table.php
php artisan migrate --path=database/migrations/2026_02_18_000002_add_hotel_id_to_travel_program_days_table.php
```

### 2. Clarifications avant mise en prod
- ✅ La table `travel_program_days` aura une nouvelle colonne `hotel_id`
- ✅ Une nouvelle table pivot `program_day_transfers` sera créée
- ✅ Les données existantes (hôtels/transferts globaux du tour) ne sont pas affectées

### 3. Vérifier les permissions
- Pas de nouvelles permissions requises
- Les mêmes autorisations pour modifier les tours suffisent

## 🧪 Procédure de test manual

### Test 1: Ajout d'un hôtel et de transferts à un jour

#### Préalable
1. Créer un tour avec:
   - Programme de 3+ jours (Jour 1, 2, 3)
   - Au moins 1 hôtel enregistré (tab Hôtels)
   - Au moins 2 transferts enregistrés (tab Transferts)

2. Aller à la page d'édition du tour: `/admin/circuits/voyages/{id}/edit`
   - Onglet "Programme"

#### Étapes
1. Cliquer sur le bouton **"+ Ajouter un élément"** du **Jour 2**
   - Le drawer "Jour 2 — Ajouter" doit s'ouvrir

2. **Cliquer sur l'onglet "Hôtels"**
   - Voir la liste des hôtels du tour
   - Sélectionner un hôtel (ex: "Hotel X")
   - Vérifier que les détails (nom, adresse, type chambre, plan repas) s'affichent

3. **Cliquer sur l'onglet "Transferts"**
   - Voir 2 sections: "Arrivée :" et "Départ :"
   - Cocher 1 transfert d'arrivée et 1 de départ
   - Vérifier que les détails des transferts sélectionnés s'affichent (routes, horaires)

4. **Fermer le drawer** (bouton "Fermer" ou X)

5. **Cliquer sur "Enregistrer toutes les modifications"**
   - La requête POST doit inclure:
     ```
     programme_days[1][hotel_id]=5
     programme_days[1][transfer_ids]=2,7
     ```

6. **Attendre la redirection** avec message de succès
   - "Tour mis à jour avec succès dans WordPress !"

7. **Recharger la page** `F5`
   - Cliquer à nouveau sur **"+ Ajouter un élément"** du Jour 2
   - Les sélections précédentes (hôtel + transferts) doivent être restaurées

✅ **Test validé** si les données persistent et se restaurent.

---

### Test 2: Modification des hôtels/transferts d'un jour

#### Étapes
1. Ouvrir le drawer du Jour 2 (toujours)

2. **Changer d'hôtel** (sélectionner un autre)

3. **Cocher/décocher des transferts**

4. Sauvegarder et vérifier la persistance

✅ **Test validé** si les changements sont enregistrés.

---

### Test 3: Isolation par jour

#### Étapes
1. Configurer Jour 1: Hôtel A, Transferts [1, 3]

2. Configurer Jour 2: Hôtel B, Transferts [2]

3. Configurer Jour 3: Aucun hôtel, Transferts [1]

4. Sauvegarder

5. Recharger et vérifier:
   - Jour 1 → Hôtel A, Transferts [1, 3]
   - Jour 2 → Hôtel B, Transferts [2]
   - Jour 3 → Aucun hôtel, Transferts [1]

✅ **Test validé** si chaque jour conserve son contexte indépendant.

---

### Test 4: Suppression de jours

#### Étapes
1. Tour avec 4 jours configurés (Jour 1, 2, 3, 4)

2. Supprimer le Jour 2 (via l'interface existante)

3. Sauvegarder

4. Recharger et vérifier:
   - Les associations Jour 2 sont supprimées (cascade delete via FK)
   - Les associations Jour 3 et Jour 4 restent intactes

✅ **Test validé** si les associations supprimées ne "pollluent" pas les autres jours.

---

### Test 5: Comportement avec données invalides

#### Étapes
1. Éditer directement la base de données:
   - Insérer une association `program_day_transfers` avec un `transfer_id` qui n'existe pas

2. Ouvrir le drawer du jour concerné

3. Vérifier:
   - Le formulaire ne doit pas crash
   - Les transferts invalides ne s'affichent pas

4. Modifier et sauvegarder

5. Vérifier que la ligne invalide reste intacte (pas de deletion automatique)

✅ **Test validé** si le système est robuste face aux données corrompues.

---

## 🐛 Debugging

### Logs à consulter
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Erreurs du formulaire
network tab (DevTools) → POST /admin/circuits/voyages/{id}
vérifier le payload `programme_days[X][hotel_id]` et `programme_days[X][transfer_ids]`
```

### Points de breakpoint (si Xdebug disponible)
1. `VoyageController@syncDayHotelsAndTransfers()` (ligne ~1160)
2. `TravelProgramDay::update()` et `transfers()->sync()`

### Inspection de la DB
```sql
-- Vérifier les associations créées
SELECT * FROM program_day_transfers WHERE program_day_id = $dayId;

-- Vérifier le hotel_id des jours
SELECT id, day_number, hotel_id FROM travel_program_days WHERE voyage_id = $voyageId;
```

---

## ✅ Critères de réussite final

- [ ] Les migrations s'exécutent sans erreur
- [ ] L'interface modal offre les onglets Hôtels / Transferts
- [ ] Les données persisten après sauvegarde
- [ ] Changement de jour restaure le bon contexte
- [ ] Chaque jour a ses propres hôtels/transferts isolés
- [ ] Suppression d'un jour supprime ses associations
- [ ] Pas de régression sur la fonctionnalité Activités / Vols
- [ ] Pas d'erreur console ou 500 dans les logs

---

## 📝 Notes d'implémentation importantes

### Architecture multi-base de données
- `TravelProgramDay` : base 'default' (Laravel)
- `TourHotel` / `TourTransfer` : base 'wp'
- Table pivot `program_day_transfers` : base 'default'
- **Validation applicative** pour les IDs transferts (pas de FK cross-db)

### Format de transmission
- Hôtel: **single value** `programme_days[X][hotel_id] = "5"`
- Transferts: **CSV string** `programme_days[X][transfer_ids] = "1,3,7"`
  - Backend parse et valide chaque ID

### Restauration des données
- JavaScript lit `window.programDayHotelsTransfers` (généré par la vue)
- Structure: `{ [dayId]: {hotel_id: x, transfer_ids: [...]} }`
- Permet une relecture fidèle du jour

---

## 🚀 Prochaines améliorations (optionnel)

1. **API REST** : Endpoint POST pour ajouter/modifier hotels/transfers d'un jour
2. **Export** : Inclure dans les exports PDF/ICS
3. **Affichage frontend** : Montrer hôtel/transferts sur le site public
4. **Validation avancée** : Règles métier (dates, compatibilités)
5. **Historique** : Audit trail des changements par jour
