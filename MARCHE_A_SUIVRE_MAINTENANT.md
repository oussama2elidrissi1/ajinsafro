# ✅ MODE DIAGNOSTIC ACTIVÉ - Marche à suivre

## 📋 Ce qui a été fait

J'ai ajouté un **mode diagnostic** dans [resources/views/admin/circuits/voyages/edit.blade.php](resources/views/admin/circuits/voyages/edit.blade.php).

Ce mode va **automatiquement**:
1. ✅ Retirer tous les attributs `disabled` des inputs `flight_options`
2. ✅ Logger dans la console tout ce qui est soumis
3. ✅ Afficher une alerte si aucun `flight_options` n'est détecté

---

## 🚀 CE QUE VOUS DEVEZ FAIRE MAINTENANT

### Étape 1: Exécuter les migrations

```powershell
cd "c:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin"
php artisan migrate --force
```

**Attendez le résultat** et notez s'il dit "Migrated" ou "Nothing to migrate".

### Étape 2: Vider le cache

```powershell
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Étape 3: Tester avec la console ouverte

1. **Ouvrir un voyage en édition** dans le navigateur
2. **Appuyer sur F12** pour ouvrir DevTools
3. **Aller dans l'onglet Console**
4. **Vous devriez voir:**
   ```
   🔧 DIAGNOSTIC MODE - Flight Options Persistence
   ✅ Intercepteur de formulaire installé
   🔄 Re-vérification après 2s...
   ```

5. **Aller dans l'onglet Vols**
6. **Modifier une carte de vol** (cliquer "Modifier")
7. **Remplir au minimum:**
   - Compagnie: Sélectionner n'importe quoi
   - From: "Paris"
   - To: "Rome"
8. **Cliquer "Enregistrer"** (bouton bleu dans la carte)

### Étape 4: Observer la console

**Si tout va bien, vous verrez:**
```
🚀 FORMULAIRE SOUMIS (intercepté)
  📦 flight_options[0][type] = outbound
  📦 flight_options[0][from_city] = Paris
  📦 flight_options[0][to_city] = Rome
  📦 flight_options[0][airline_id] = 1
  ... (etc.)
📊 Total flight_options fields: 15
✅ Flight options detectés, soumission OK
```

**La page va se recharger, et les valeurs doivent rester affichées.**

---

### Étape 5: Si une alerte apparaît

**Alerte: "⚠️ ATTENTION: Aucun flight_options détecté!"**

Cela signifie que les inputs ne sont PAS dans le FormData quand le formulaire est soumis.

**Actions à faire:**
1. Cliquer sur **"Cancel"** pour annuler la soumission
2. **Dans la console**, noter ce qui est écrit
3. **Dans DevTools, onglet Elements:**
   - Chercher un input avec `name="flight_options[0][from_city]"`
   - Cliquer dessus avec le bouton droit → "Inspect"
   - Vérifier s'il a l'attribut `disabled`
   - Vérifier s'il est bien **DANS** le formulaire `<form id="edit-voyage-form">`

4. **M'envoyer un screenshot** de la console + du HTML de l'input inspecté

---

## 📞 Informations à me fournir

### Si ça ne fonctionne TOUJOURS pas

Envoyez-moi:

1. **Screenshot de la console** (F12) après avoir cliqué "Enregistrer"

2. **Résultat de la migration:**
   ```powershell
   php artisan migrate --force
   ```

3. **Logs du serveur:**
   ```powershell
   Get-Content storage\logs\laravel.log -Tail 50
   ```

4. **Screenshot d'un input inspecté dans DevTools** (Elements → Chercher `flight_options[0][from_city]`)

---

## ⚡ Test rapide alternative

Si vous voulez tester sans passer par le navigateur:

```powershell
php artisan tinker
```

Dans tinker:

```php
$voyage = \App\Models\Voyage::first();

$option = \App\Models\VoyageFlightOption::create([
    'voyage_id' => $voyage->id,
    'type' => 'outbound',
    'day_number' => 1,
    'from_city' => 'MANUEL_TEST',
    'to_city' => 'MANUEL_DEST',
    'departure_place_id' => null,
    'airline_id' => 1,
    'depart_at' => now(),
]);

echo "✅ Créé avec ID: " . $option->id . "\n";

// Vérifier
$check = \App\Models\VoyageFlightOption::find($option->id);
echo "From: " . $check->from_city . ", To: " . $check->to_city . "\n";
```

**Si ça échoue:** Problème de DB (migration non appliquée)  
**Si ça marche:** Le backend fonctionne, c'est le formulaire qui a un problème

---

## 🎯 Comportement attendu

**AVANT (avec le bug):**
- Remplir les champs → Enregistrer → Refresh → ❌ Les valeurs disparaissent

**APRÈS (avec le fix):**
- Remplir les champs → Enregistrer → Refresh → ✅ Les valeurs restent

**ET dans la console:**
- Vous voyez `📊 Total flight_options fields: X` avec X > 0
- Vous voyez toutes les lignes `📦 flight_options[0][...]`

---

## 🔧 Retirer le mode diagnostic (plus tard)

Une fois que tout fonctionne, vous pouvez supprimer le bloc de code suivant dans [edit.blade.php](resources/views/admin/circuits/voyages/edit.blade.php) (lignes ~3643-3701):

```javascript
// ——— MODE DIAGNOSTIC: Forcer retrait des disabled + logs détaillés (À RETIRER en production) ———
(function diagnosticMode() {
    // ... tout le bloc ...
})();
```

Mais **AVANT de le retirer**, assurez-vous que tout fonctionne correctement!

---

**📍 Vous êtes prêt! Suivez les étapes ci-dessus et observez la console (F12).**
