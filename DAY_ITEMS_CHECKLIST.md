# Checklist: Vérification et Déploiement

## ✅ Avant de déployer

### Frontend (Vérifications dans le navigateur)

**1. Vérifier que le gestionnaire d'état existe**
```javascript
// Ouvrir la console du navigateur (F12)
console.log(window.dayItemsManager);
// Doit afficher un objet avec les méthodes: getDay, setFlights, getHotel, setTransfers, syncToForm, loadFromForm, countItems
```

**2. Vérifier que les inputs hidden existent**
```javascript
document.querySelectorAll('input[name^="programme_days["][name$="[flights]"]');
document.querySelectorAll('input[name^="programme_days["][name$="[hotel_id]"]');
document.querySelectorAll('input[name^="programme_days["][name$="[transfer_ids]"]');
// Chaque jour doit voir 3 inputs hidden
```

**3. Vérifier que le drawer expose les composants**
```javascript
document.getElementById('day-builder-hotels-manager');     // Doit exister
document.getElementById('day-builder-transfers-manager');   // Doit exister
document.getElementById('day-builder-flights-manager');     // Doit exister
```

**4. Tester l'ouverture du drawer**
- Aller à l'onglet **Programme**
- Cliquer sur "Ajouter un élément" pour un jour (bouton jaune)
- Le drawer doit s'ouvrir avec 4 onglets: **Activités / Hôtels / Transferts / Vols**
- Chaque onglet doit charger les données correspondantes

### Backend (Vérifications code)

**5. Vérifier les routes API**
```bash
php artisan route:list | grep programme
# Doit voir des routes pour le Programme
```

**6. Vérifier les migrations**
```bash
php artisan migrate:status
# Les migrations doivent être "Ran"
```

**7. Vérifier les relations Eloquent**
```php
// Dans VoyageController ou un test
$day = TravelProgramDay::find(1);
$day->flights();       // Doit retourner une relation
$day->transfers();     // Doit retourner une relation
$day->hotel();         // Doit retourner une relation
```

---

## 🧪 Scénarios de test

### Scénario 1: Ajouter 1 hôtel à un jour

**Étapes:**
1. Aller à l'onglet **Programme**
2. Cliquer sur "Ajouter un élément" pour le Jour 1
3. Aller à l'onglet **Hôtels**
4. Sélectionner un hôtel dans la liste déroulante
5. Vérifier que les détails s'affichent
6. Fermer le drawer
7. Enregistrer le formulaire

**Attentes:**
- L'input hidden `programme_days[0][hotel_id]` doit contenir l'ID de l'hôtel
- Le backend doit persister la liaison dans la base de données
- À la réouverture du formulaire, l'hôtel doit être pré-rempli

**Vérification**:
```javascript
// Dans la console
window.dayItemsManager.getHotel('0');
// → Doit retourner l'ID de l'hôtel
```

---

### Scénario 2: Ajouter 2 transferts à un jour

**Étapes:**
1. Aller à l'onglet **Programme**
2. Cliquer sur "Ajouter un élément" pour le Jour 2
3. Aller à l'onglet **Transferts**
4. Cocher 2 transferts (un d'arrivée, un de départ)
5. Vérifier le résumé en bas (détails des transferts sélectionnés)
6. Fermer le drawer
7. Enregistrer le formulaire

**Attentes:**
- L'input hidden `programme_days[1][transfer_ids]` doit contenir "ID1,ID2" (format CSV)
- Le badge de résumé doit montrer le nombre total d'éléments (activités + transferts + ...)
- À la réouverture, les transferts doivent être pré-cochés

**Vérification**:
```javascript
// Dans la console
window.dayItemsManager.getTransfers('1');
// → Doit retourner [ID1, ID2]
```

---

### Scénario 3: Ajouter 1 hôtel + 2 transferts + 1 vol au même jour

**Étapes:**
1. Étapes du Scénario 1 jusqu'à la sélection de l'hôtel
2. Aller à l'onglet **Transferts** → sélectionner 2 transferts
3. Aller à l'onglet **Vols** → cocher 1 vol
4. Retourner à l'onglet **Hôtels** → l'hôtel doit toujours être sélectionné ✓
5. Retourner à l'onglet **Transferts** → les 2 transferts doivent toujours être cochés ✓
6. Fermer le drawer → le badge doit montrer "4 éléments" (ou plus avec activités)
7. Enregistrer

**Attentes:**
- Aucune perte de données lors du basculement d'onglets
- L'état persiste en mémoire (window.dayItemsManager.state)
- La persistance au formulaire est correcte (3 inputs hidden remplis)

**Vérification**:
```javascript
// Dans la console
const state = window.dayItemsManager.getDay('X');
console.log(state);
// → { flights: [ID], hotel_id: ID, transfer_ids: [ID, ID], ... }
```

---

### Scénario 4: Jour X avec activités + jour Y avec hôtel/transfert

**Étapes:**
1. Jour 1: 3 activités (via le catalogue)
2. Jour 2: 1 hôtel + 2 transferts (via les managers)
3. Jour 3: rien
4. Enregistrer le formulaire

**Attentes:**
- Badge du Jour 1 → "3 éléments"
- Badge du Jour 2 → "3 éléments"
- Badge du Jour 3 → "0 élément"
- À la réouverture, les liaisons doivent être correctes

**Vérification**:
```javascript
console.log(window.dayItemsManager.state);
// → {
//   "0": {dayId: ..., flights: [], hotel_id: null, transfer_ids: []},
//   "1": {dayId: ..., flights: [], hotel_id: ID, transfer_ids: [ID, ID]},
//   "2": {dayId: ..., flights: [], hotel_id: null, transfer_ids: []}
// }
```

---

### Scénario 5: Réouverture & pré-remplissage

**Étapes:**
1. Créer un voyage avec Jour 1 = 1 hôtel + 2 transferts
2. Enregistrer
3. Fermer le formulaire
4. Rouvrir le voyage en édition
5. Cliquer sur "Ajouter un élément" pour Jour 1

**Attentes:**
- Le drawer doit afficher l'hôtel pré-sélectionné dans le `<select>`
- Les 2 transferts doivent être pré-cochés
- Les détails doivent s'afficher correctement

**Vérification**:
```javascript
// Après ouverture du drawer
const selector = document.getElementById('hotels-manager-select');
console.log(selector.value); // → ID de l'hôtel

const checks = document.querySelectorAll('.transfer-checkbox:checked');
console.log(checks.length); // → 2
```

---

### Scénario 6: Supprimer une liaison

**Étapes:**
1. Jour avec 1 hôtel + 2 transferts
2. Cliquer "Ajouter un élément"
3. Onglet Hôtels → déselectionner (—Aucun hôtel)
4. Onglet Transferts → décocher les 2
5. Fermer le drawer
6. Enregistrer

**Attentes:**
- `programme_days[0][hotel_id]` = vide
- `programme_days[0][transfer_ids]` = vide
- La base de données doit supprimer les liaisons (au besoin)

**Vérification**:
```javascript
window.dayItemsManager.getHotel('0');        // → null
window.dayItemsManager.getTransfers('0');    // → []
```

---

### Scénario 7: Navigation multi-jour

**Étapes:**
1. Jour 1: Hôtel A + Transfert 1
2. Cliquer "Ajouter un élément" pour Jour 2
3. Jour 2: Hôtel B + Transfert 2
4. Cliquer "Ajouter un élément" pour Jour 1 (revenir)
5. Vérifier que Hôtel A + Transfert 1 sont toujours pré-remplis

**Attentes:**
- Aucune confusión entre jours
- L'état persiste correctement lors de la navigation
- Les inputs hidden sont synchronisés correctement pour chaque jour

**Vérification**:
```javascript
// Jour 1
window.dayItemsManager.getHotel('0');        // → Hôtel A
// Jour 2
window.dayItemsManager.getHotel('1');        // → Hôtel B
```

---

## 🔍 Inspection visuelle

| Élément | État attendu | Où vérifier |
|---------|--------------|-------------|
| Badge du jour | "N éléments" | En haut du Jour dans l'accordéon |
| Drawer header | "Jour X — Ajouter (N éléments)" | En haut du drawer |
| Select hôtels | Contient la liste des hôtels | Onglet Hôtels, `<select id="hotels-manager-select">` |
| Checkboxes transferts | Groupe Arrivée + Groupe Départ | Onglet Transferts |
| Détails hôtel | Affiche nom, adresse, type de chambre, repas | Onglet Hôtels (si hôtel sélectionné) |
| Détails transferts | Affiche liste des transferts sélectionnés | Onglet Transferts (si transferts cochés) |

---

## 📊 Inspection du formulaire

**Après avoir rempli le drawer, avant de "Enregistrer":**

```bash
# Ouvrir la console du navigateur
# Aller à l'onglet Network
# Cliquer sur "Enregistrer toutes les modifications"
# Chercher la requête POST
# Afficher le body / payload
# Chercher les champs programme_days[X][...]
```

**Exemple de payload attendu:**
```
POST /admin/circuits/voyages/123 HTTP/1.1

programme_days[0][mode]=program
programme_days[0][day_title]=Arrivée
programme_days[0][notes]=Note...
programme_days[0][flights]=
programme_days[0][hotel_id]=5
programme_days[0][transfer_ids]=2,4
programme_days[0][activities][0][...]=...
```

---

## 🐛 Troubleshooting

### Le gestionnaire d'état n'existe pas
- **Vérifier:** La ligne `window.dayItemsManager.init()` en bas du script dans edit.blade.php ?
- **Solution:** Recharger la page (Ctrl+F5)

### Les inputs hidden ne reçoivent pas les valeurs
- **Vérifier:** La fonction `syncToForm()` est-elle appelée ?
- **Solution:** Après le changement d'un manager, vérifier les valeurs dans la console
  ```javascript
  document.querySelector('input[name="programme_days[0][hotel_id]"]').value
  ```

### Le drawer ne s'ouvre pas
- **Vérifier:** Bootstrap est-il chargé ?
- **Solution:** Vérifier la console pour les erreurs JavaScript

### Les données ne persistes pas après enregistrement
- **Vérifier:** Le backend reçoit les données ? (Network tab, payload)
- **Solution:** Implémenter le backend (voir section "Backend")

### La pré-remplissage ne fonctionne pas
- **Vérifier:** `window.programDayHotelsTransfers` contient-il les bonnes données ?
  ```javascript
  console.log(window.programDayHotelsTransfers)
  ```
- **Solution:** Vérifier que la vue passe `$programDayHotelsTransfers` au controller

---

## ✅ Checklist finale avant production

- [ ] Tests Scénarios 1-7 réussis
- [ ] Pas de console errors (F12)
- [ ] Les inputs hidden sont remplis correctement
- [ ] Le formulaire POST envoie les bonnes données
- [ ] Le backend persiste les données (migration + relations)
- [ ] La pré-remplissage fonctionne à la réouverture
- [ ] Les badges/résumés sont à jour
- [ ] Aucune perte de données lors du basculement d'onglets

