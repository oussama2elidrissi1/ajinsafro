# 📖 Index: Gestion Vols/Hôtels/Transferts par Jour

## 🎯 Objectif réalisé

✅ **Implémentation cohérente et par-jour** pour **Hôtels (0..1) / Transferts (0..n) / Vols (0..n)** dans le drawer "Ajouter un élément" du Programme.

**Statut**: 
- ✅ **Frontend**: COMPLÉTÉ
- 🟡 **Backend**: À IMPLÉMENTER (bien documenté)

---

## 📚 Documentation

Lisez dans cet ordre:

### 1️⃣ **START HERE** → [DAY_ITEMS_QUICK_START.md](./DAY_ITEMS_QUICK_START.md)
   - **Durée**: 5 minutes
   - **Contenu**: Test rapide du frontend
   - **Audience**: Tous
   - **Vérifications**: Console, formulaire, Network tab

### 2️⃣ **Architecture** → [DAY_ITEMS_IMPLEMENTATION.md](./DAY_ITEMS_IMPLEMENTATION.md)
   - **Durée**: 15 minutes
   - **Contenu**: Vue d'ensemble complète, flux de données, événements, helpers
   - **Audience**: Développeurs
   - **Comprendre**: Comment tout fonctionne

### 3️⃣ **Tester** → [DAY_ITEMS_CHECKLIST.md](./DAY_ITEMS_CHECKLIST.md)
   - **Durée**: 30 minutes
   - **Contenu**: 7 scénarios de test détaillés + troubleshooting
   - **Audience**: QA, Testeurs, Développeurs
   - **Valider**: Tous les cas d'usage

### 4️⃣ **Backend** → [DAY_ITEMS_BACKEND_GUIDE.md](./DAY_ITEMS_BACKEND_GUIDE.md)
   - **Durée**: 1 heure
   - **Contenu**: Migrations, modèles, controller, pré-remplissage
   - **Audience**: Backend
   - **Implémenter**: La persistance en base de données

### 5️⃣ **Détails** → [CHANGEMENTS.md](./CHANGEMENTS.md)
   - **Durée**: 10 minutes
   - **Contenu**: Fichiers modifiés, structure de données, flux
   - **Audience**: Développeurs, tech leads
   - **Comprendre**: Ce qui a changé exactement

---

## 🔧 Fichiers modifiés (Frontend)

| Fichier | Modification | Ligne |
|---------|-------------|-------|
| **edit.blade.php** | Gestionnaire d'état `dayItemsManager` | ~3290 |
| **edit.blade.php** | Inputs hidden (3 par jour) | ~2219 |
| **HotelsManager.blade.php** | Listeners + gestionnaire d'état | ~36, ~115 |
| **TransfersManager.blade.php** | Listeners + gestionnaire d'état | ~30, ~130 |
| **FlightsManager.blade.php** | Listeners + synchronisation | +35 lines |

### Impact
- ✅ Aucun fichier supprimé
- ✅ Aucun fichier créé (only modifié)
- ✅ Backend non modifié (à faire)
- ✅ Onglets globaux inchangés
- ✅ Backward compatible

---

## 🚀 Quick steps (20 minutes)

### Phase 1: Tester le Frontend ✅

```bash
# 1. Ouvrir l'admin, charger un voyage
# 2. Onglet Programme > "Ajouter un élément"
# 3. Sélectionner: 1 hôtel + 2 transferts + 1 vol
# 4. Console: window.dayItemsManager.state
# 5. Enregistrer > Network tab > Vérifier le payload
```

✅ **Frontend validé ?** → Continuer au backend

---

### Phase 2: Implémenter le Backend (1-2 heures)

**Fichiers à créer/modifier**:

```php
// 1. Migration
database/migrations/2024_02_18_add_hotel_transfers_flights_to_program_days.php

// 2. Modèles
app/Models/TravelProgramDay.php          // Ajouter relations
app/Models/TourHotel.php                 // Ajouter relation inverse
app/Models/VoyageFlight.php              // Ajouter relation inverse
app/Models/TourTransfer.php              // Ajouter relation inverse

// 3. Controller
app/Http/Controllers/Admin/VoyageController.php   // Adapter update() + syncProgrammeDaysWithItems()

// 4. Vue
resources/views/admin/circuits/voyages/edit.blade.php  // Ajouter pré-remplissage (si nécessaire)
```

**Étapes**:
1. Exécuter migration → `php artisan migrate`
2. Ajouter relations Eloquent → Modèles
3. Adapter VoyageController → update() method
4. Tester persistance → php tinker

---

### Phase 3: Valider (30 minutes)

Exécuter les 7 scénarios du [DAY_ITEMS_CHECKLIST.md](./DAY_ITEMS_CHECKLIST.md)

---

## 📊 État actuel

### ✅ Terminé (Frontend)
- Gestionnaire d'état Global `window.dayItemsManager`
- Inputs hidden par jour (flights, hotel_id, transfer_ids)
- HotelsManager adapté avec gestionnaire
- TransfersManager adapté avec gestionnaire
- FlightsManager adapter avec gestionnaire
- Événement `day-builder:context-changed` utilisé
- Pré-remplissage depuis inputs hidden (frontside)
- Pas de breaking changes

### 🟡 À faire (Backend)
- Migrations (tables + colonnes)
- Modèles Eloquent (relations)
- VoyageController (persistance)
- Vue edit (pré-remplissage depuis DB)
- Tests unitaires

### ❌ Pas prévu (optionnel)
- API endpoints séparées
- Affichage frontend du résumé Programme (déjà fait via badge)
- Validation advanced (au-delà de l'existence des IDs)

---

## 🎓 Architecture simplifiée

```
FRONTEND:
┌─────────────────────────────────────┐
│ Formulaire (programme_days[X][...]) │
│                                     │
│  ├─ INPUT hidden [flights]          │
│  ├─ INPUT hidden [hotel_id]         │
│  ├─ INPUT hidden [transfer_ids]     │
│  └─ INPUT hidden [activities][...]  │
│                                     │
│  ↕  (sync)                          │
│                                     │
│  window.dayItemsManager             │
│  ({state, getDay, setHotel, ...})   │
│                                     │
│  ↕  (listeners)                     │
│                                     │
│  Drawer Managers                    │
│  ├─ HotelsManager (<select>)        │
│  ├─ TransfersManager (<checkboxes>) │
│  ├─ FlightsManager (<checkboxes>)   │
│  └─ ActivitiesManager (<buttons>)   │
└─────────────────────────────────────┘
           ↓ POST
           
BACKEND:
┌─────────────────────────────────────┐
│ VoyageController::update()          │
│                                     │
│  → syncProgrammeDaysWithItems()    │
│     - Parse CSV (flights, transfers)│
│     - Update hotel_id              │
│     - Sync pivots                  │
│                                     │
│  → Database                         │
│     travel_program_days             │
│     ├─ hotel_id (FK)                │
│     travel_program_day_flights      │
│     travel_program_day_transfers    │
└─────────────────────────────────────┘
```

---

## 🔍 Vérification rapide

Copier/coller dans la console du navigateur:

```javascript
// Frontend OK?
window.dayItemsManager && console.log("✅ Frontend OK")

// Inputs hidden existent?
document.querySelectorAll('input[name$="[hotel_id]"]').length > 0 && console.log("✅ Inputs OK")

// Drawer accessible?
document.getElementById('day-builder-drawer') && console.log("✅ Drawer OK")

// État chargé?
window.dayItemsManager.state && console.log("✅ State OK:", window.dayItemsManager.state)
```

---

## ⏰ Chronométrage estimé

| Phase | Tâche | Durée | Status |
|-------|-------|-------|--------|
| 1 | Lire la documentation | 30 min | ℹ️ À faire |
| 2 | Tester le frontend | 10 min | ✅ Easy |
| 3 | Migrations | 15 min | ⚠️ À faire |
| 4 | Modèles Eloquent | 20 min | ⚠️ À faire |
| 5 | Controller | 20 min | ⚠️ À faire |
| 6 | Tester la persistance | 15 min | ⚠️ À faire |
| 7 | Tests unitaires (optionnel) | 30 min | ⚠️ Optional |
| **TOTAL** | | **2 h 20 min** | |

---

## 📝 Notes importantes

1. **Format CSV**: Les vols et transferts sont envoyés en format CSV (ex: "1,3,5"), pas JSON
2. **Hôtel unique par jour**: Chaque jour peut avoir 0..1 hôtel (select unique)
3. **Backward compatible**: Les voyages existants auront des liaisons vides jusqu'à modification
4. **Relations Eloquent**: Required pour la pré-remplissage et l'affichage
5. **Validations**: Le backend doit valider que les IDs existent réellement

---

## 🆘 Besoin d'aide?

- **Erreur console** → Voir [DAY_ITEMS_CHECKLIST.md#troubleshooting](./DAY_ITEMS_CHECKLIST.md)
- **Comprendre le flux** → Voir [DAY_ITEMS_IMPLEMENTATION.md#flux-de-données](./DAY_ITEMS_IMPLEMENTATION.md)
- **Backend ne fonctionne pas** → Voir [DAY_ITEMS_BACKEND_GUIDE.md#étape-5-tester](./DAY_ITEMS_BACKEND_GUIDE.md)
- **Quoi modifier exactement?** → Voir [CHANGEMENTS.md](./CHANGEMENTS.md)

---

## ✅ Checklist de déploiement

- [ ] Frontend testé (console OK, formulaire OK)
- [ ] Documentation lue (au moins DAY_ITEMS_QUICK_START.md)
- [ ] Migrations créées et exécutées
- [ ] Modèles Eloquent mis à jour
- [ ] VoyageController adapté
- [ ] 7 scénarios du checklist exécutés
- [ ] Pas d'erreur console
- [ ] Persistance en base validée
- [ ] Pré-remplissage fonctionne
- [ ] Tests unitaires réussis (optionnel)

---

## 📞 Questions fréquentes

**Q: Les onglets globaux Vols/Hôtels/Transferts sont modifiés?**  
A: Non, ils restent inchangés. Seul le drawer a été adapté.

**Q: Peut-on ajouter plusieurs hôtels par jour?**  
A: Non, c'est 0..1 par jour (design choice). Pour 0..n, adapter la logique.

**Q: Les données existantes sont perdues?**  
A: Non, backward compatible. Les liaisons vides jusqu'à modification.

**Q: Comment contourner les validations d'ID?**  
A: Voir la méthode `filterValidFlightIds()` dans [DAY_ITEMS_BACKEND_GUIDE.md](./DAY_ITEMS_BACKEND_GUIDE.md)

---

## 🎉 Prêt?

1. ➡️ Lire → [DAY_ITEMS_QUICK_START.md](./DAY_ITEMS_QUICK_START.md)
2. ➡️ Tester → Drawer "Ajouter un élément"
3. ➡️ Implémenter → Backend (migrations + modèles + controller)
4. ➡️ Valider → 7 scénarios

**Good luck! 🚀**

