# ⚠️ PROBLÈME PERSISTANT - Plan d'action complet

Vous avez rempli plusieurs champs dans un vol mais ils ne persistent pas après sauvegarde.

## 🎯 Hypothèses principales

1. **Les migrations n'ont pas été exécutées** → La table/colonnes n'existent pas
2. **Les inputs sont désactivés (disabled)** → Le formulaire ne les envoie pas
3. **Le formulaire n'est pas soumis** → Problème JavaScript
4. **Erreur de validation silencieuse** → Le backend rejette les données sans message
5. **Problème de base de données** → INSERT/UPDATE échoue

---

## 🚀 ACTIONS IMMÉDIATES (dans l'ordre)

### Action 1: Exécuter php artisan migrate

```powershell
cd "c:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin"
php artisan migrate --force
```

**Résultat attendu:**
```
Migrating: 2026_02_21_100000_add_departure_place_id_to_voyage_flight_options
Migrated:  2026_02_21_100000_add_departure_place_id_to_voyage_flight_options (XX.XXms)
```

**Si ça dit "Nothing to migrate":** Les migrations sont déjà appliquées. Passez à Action 2.

---

### Action 2: Vider le cache Laravel

```powershell
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

---

### Action 3: Test avec DevTools (F12)

1. **Ouvrir le voyage en édition**
2. **Appuyer sur F12** → Onglet **Console**
3. **Coller ce code dans la console:**

```javascript
// Test 1: Vérifier que le formulaire existe
console.log('Formulaire:', document.getElementById('edit-voyage-form'));

// Test 2: Intercepter la soumission
document.getElementById('edit-voyage-form').addEventListener('submit', function(e) {
    console.log('🚀 FORMULAIRE SOUMIS');
    var formData = new FormData(this);
    var flightOptions = {};
    for (var pair of formData.entries()) {
        if (pair[0].startsWith('flight_options')) {
            console.log('  ✅', pair[0], '=', pair[1]);
            flightOptions[pair[0]] = pair[1];
        }
    }
    if (Object.keys(flightOptions).length === 0) {
        console.error('❌ AUCUN flight_options dans le FormData!');
    } else {
        console.log('✅ Total flight_options:', Object.keys(flightOptions).length);
    }
}, true);
```

4. **Maintenant:**
   - Onglet **Vols** → Modifier une carte
   - Remplir au minimum: Compagnie + From + To
   - Cliquer **"Enregistrer"**

5. **Observer la console:**
   - Si vous voyez `🚀 FORMULAIRE SOUMIS` et des lignes `✅ flight_options[0][...]` → **Le formulaire est OK**
   - Si vous voyez `❌ AUCUN flight_options` → **PROBLÈME: les inputs ne sont pas dans le formulaire**

---

### Action 4: Inspecter un input en mode édition

1. **Onglet Vols** → Modifier une carte
2. **F12** → Onglet **Elements**
3. **Cliquer sur l'icône "Select element"** (en haut à gauche de DevTools)
4. **Cliquer sur le champ "From (ville)"** dans le formulaire
5. **Dans DevTools, vérifier le HTML:**

```html
<!-- BON: -->
<input type="text" 
       name="flight_options[0][from_city]"
       value="casa"
       class="form-control form-control-sm">

<!-- MAUVAIS (si vous voyez ça): -->
<input type="text"
       name="flight_options[-1][from_city]"  ← Index -1 = template
       value="casa"
       disabled>  ← Disabled = pas envoyé
```

**Si vous voyez `disabled` ou `name="...[-1]..."`:**
→ C'est le problème! Vous éditez le template au lieu d'une carte réelle.

---

### Action 5: Vérifier les logs Laravel

```powershell
# Ouvrir les logs en temps réel
Get-Content storage\logs\laravel.log -Wait -Tail 50
```

**Dans un autre onglet/fenêtre:**
- Modifier un vol et cliquer "Enregistrer"

**Dans les logs, chercher:**
- `VoyageController@update - Request keys received` → montre ce qui est reçu
- `has_flight_options: true` ou `false`
- `flight_options_count: X`

**Si `has_flight_options: false`:**
→ Le formulaire ne contient PAS `flight_options` quand il est soumis.

---

### Action 6: Test manuel en base de données

```powershell
php artisan tinker
```

Dans tinker:

```php
// Trouver un voyage
$voyage = \App\Models\Voyage::first();

// Créer une option de vol directement
$option = \App\Models\VoyageFlightOption::create([
    'voyage_id' => $voyage->id,
    'type' => 'outbound',
    'day_number' => 1,
    'from_city' => 'MANUEL_TEST',
    'to_city' => 'MANUEL_DEST',
    'airline_id' => 1,
]);

// Vérifier
echo "Créé: " . $option->id . "\n";

// Relire
$check = \App\Models\VoyageFlightOption::find($option->id);
echo "From: " . $check->from_city . ", To: " . $check->to_city . "\n";
```

**Si ça échoue avec une erreur SQL:**
→ Problème de DB/migration (colonne manquante)

**Si ça marche:**
→ Le backend fonctionne. Le problème est dans le formulaire/validation.

---

## 🔴 SCÉNARIOS ET SOLUTIONS

### Scénario A: Migration échoue

**Erreur:**
```
SQLSTATE[42S01]: Base table or view already exists
```

**Solution:**
```powershell
php artisan migrate:rollback --step=1
php artisan migrate
```

---

### Scénario B: Inputs disabled

**Symptôme:** Dans DevTools, vous voyez `disabled` sur les inputs

**Solution:** Vérifier que vous n'éditez pas accidentellement le template.

**Code à ajouter temporairement** en haut de `edit.blade.php`:

```html
<script>
// Retirer tous les disabled des flight_options (forcer)
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        document.querySelectorAll('[name^="flight_options"]').forEach(function(input) {
            input.removeAttribute('disabled');
            console.log('🔓 Retiré disabled de:', input.name);
        });
    }, 1000);
});
</script>
```

---

### Scénario C: FormData vide

**Symptôme:** `❌ AUCUN flight_options dans le FormData`

**Cause possible:**
- Les inputs sont dans un autre formulaire
- Les inputs ont des `name` incorrects
- Les inputs sont créés dynamiquement après le clonage mais pas correctement

**Solution:** Vérifier dans DevTools → Elements que les inputs sont bien DANS le formulaire `#edit-voyage-form`:

```html
<form id="edit-voyage-form" method="POST">
    <!-- ... autres onglets ... -->
    <div id="flights">
        <!-- Les cartes de vol doivent être ICI -->
        <div data-flight-opt-index="0">
            <input name="flight_options[0][from_city]" ...>
        </div>
    </div>
</form>
```

---

### Scénario D: Erreur de validation

**Symptôme:** Les logs montrent `has_flight_options: true` mais une erreur après

**Solution:** Chercher dans les logs:
```
VoyageController@update flight options failed
```

Et voir le message d'erreur.

---

## 📍 CE QUE VOUS DEVEZ M'ENVOYER

Pour que je puisse corriger définitivement:

### 1. Résultat de php artisan migrate

```powershell
php artisan migrate --force
```

Copiez TOUT ce qui s'affiche.

### 2. Sortie de la console (F12)

Après avoir exécuté le script de test (Action 3) et cliqué "Enregistrer", copiez tout ce qui apparaît dans la console.

### 3. Screenshot d'un input inspecté

F12 → Elements → Cliquer sur "From (ville)" → Screenshot du HTML dans DevTools

### 4. Extrait des logs

```powershell
Get-Content storage\logs\laravel.log -Tail 100 | Select-String "flight|VoyageController"
```

Copiez le résultat.

### 5. Test tinker

Résultat de la commande tinker (Action 6).

---

## ⚡ Si vous voulez une solution rapide MAINTENANT

**Option nucléaire:** Forcer la suppression de disabled + logs ultra-verbeux.

Ajoutez ce code **temporairement** en haut du fichier `edit.blade.php` (ligne 2, après `@extends`):

```blade
@push('scripts')
<script>
// === DIAGNOSTIC MODE ===
window.DIAGNOSTIC_MODE = true;

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 MODE DIAGNOSTIC ACTIVÉ');
    
    // 1. Retirer tous les disabled
    document.querySelectorAll('[name^="flight_options"]').forEach(function(el) {
        if (el.hasAttribute('disabled')) {
            el.removeAttribute('disabled');
            console.log('🔓 Disabled retiré:', el.name);
        }
    });
    
    // 2. Intercepter submission
    var form = document.getElementById('edit-voyage-form');
    form.addEventListener('submit', function(e) {
        console.log('🚀 FORMULAIRE SOUMIS');
        var fd = new FormData(this);
        var count = 0;
        for (var pair of fd.entries()) {
            if (pair[0].startsWith('flight_options')) {
                console.log('  📦', pair[0], '=', pair[1]);
                count++;
            }
        }
        console.log('✅ Total flight_options:', count);
        if (count === 0) {
            alert('ERREUR: Aucun flight_options détecté!');
            e.preventDefault();
        }
    }, true);
    
    console.log('✅ Intercepteurs installés');
});
</script>
@endpush
```

Rechargez la page, ouvrez la console (F12), et testez la sauvegarde.

La console va vous dire EXACTEMENT ce qui est envoyé (ou pas).
