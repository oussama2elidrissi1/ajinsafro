# 🚨 DIAGNOSTIC IMMÉDIAT - Lieu de départ ne s'enregistre pas

## Étapes à suivre MAINTENANT

### ✅ Étape 1: Exécuter le script de diagnostic

```powershell
cd "c:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin"
php diagnose-flights.php
```

**Ce script va vérifier:**
- ✅ Connexion DB
- ✅ Existence de la table `voyage_flight_options`
- ✅ Existence de la colonne `departure_place_id`
- ✅ Migrations exécutées
- ✅ Test d'écriture en base

**Envoyez-moi la sortie complète de ce script.**

---

### ✅ Étape 2: Exécuter les migrations (si nécessaire)

Si le script montre des ❌ pour les migrations:

```powershell
php artisan migrate --force
```

---

### ✅ Étape 3: Activer les logs détaillés

Éditer `config/logging.php`:

```php
'channels' => [
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'debug', // ⬅️ Changer de 'info' à 'debug'
    ],
],
```

**OU** plus rapide, éditer `.env`:

```env
LOG_LEVEL=debug
```

Ensuite:
```powershell
php artisan config:clear
php artisan cache:clear
```

---

### ✅ Étape 4: Test de sauvegarde avec logs

1. **Ouvrir le fichier de logs en temps réel:**
   ```powershell
   Get-Content storage\logs\laravel.log -Wait -Tail 50
   ```

2. **Dans un autre onglet PowerShell ou navigateur:**
   - Ouvrir un voyage en édition
   - Onglet **Vols** → Vols Aller
   - **Modifier** une carte
   - Remplir au minimum:
     - ✅ Compagnie: Sélectionner n'importe laquelle
     - ✅ From: "Paris"
     - ✅ To: "Rome"
   - Cliquer **"Enregistrer"** (bouton bleu dans la carte)

3. **Observer les logs qui apparaissent en temps réel**

**Chercher ces lignes:**
- `VoyageController@update - Request keys received`
- `VoyageController@update flight_options payload FULL`
- `VoyageFlightOptionService: processing flight option`

---

### ✅ Étape 5: Analyser les logs

#### Cas A: Rien dans les logs

**Signifie:** Le formulaire n'est PAS soumis au serveur

**Solution:** Problème JavaScript. Vérifier la console du navigateur (F12).

#### Cas B: `has_flight_options: false`

**Signifie:** Le formulaire est soumis mais `flight_options` n'est pas dans le payload

**Solutions possibles:**
1. Les inputs sont `disabled` (vérifier dans DevTools que `disabled` n'est PAS présent)
2. Les inputs n'ont pas de `name` correct
3. Le formulaire n'est pas celui attendu

#### Cas C: `has_flight_options: true` mais `count: 0`

**Signifie:** Le tableau `flight_options` est vide

**Solution:** Les inputs sont peut-être dans un autre formulaire ou supprimés par JavaScript

#### Cas D: Logs OK mais erreur après

**Signifie:** Problème dans le service ou la base de données

**Chercher:** Une ligne `VoyageController@update flight options failed` avec le message d'erreur

---

## 🔍 Vérifications manuelles (DevTools)

### A. Inspecter les inputs pendant l'édition

1. Ouvrir le voyage en édition
2. Onglet Vols → Modifier une carte
3. Appuyer sur **F12** → Onglet **Elements**
4. Dans la carte en mode édition, vérifier un input:

```html
<input type="text" 
       name="flight_options[0][from_city]"  ← DOIT être présent
       value="casa"
       class="form-control form-control-sm"
       <!-- PAS de disabled ici -->
>
```

**Si vous voyez `disabled`:** C'est le problème! Ces inputs ne seront pas envoyés.

### B. Vérifier le payload au moment de la soumission

1. **F12** → Onglet **Network**
2. Cocher **"Preserve log"**
3. Remplir la carte et cliquer **"Enregistrer"**
4. Dans la liste Network, chercher une requête **PUT** vers `/circuits/voyages/[ID]`
5. Cliquer dessus → Onglet **Payload** ou **Form Data**

**Vérifier qu'il y a:**
```
flight_options[0][type]: outbound
flight_options[0][from_city]: Paris
flight_options[0][to_city]: Rome
flight_options[0][airline_id]: 1
...
```

**Si ces lignes sont absentes:** Le problème est côté frontend (JavaScript ou HTML)

---

## 🎯 Solutions rapides selon le diagnostic

### Problème: Colonne `departure_place_id` n'existe pas

```powershell
php artisan migrate --force
```

### Problème: Inputs sont `disabled`

Le template a peut-être un bug. Vérifier dans `_flight_option_card.blade.php` ligne 58:

```blade
name="flight_options[{{ $index }}][departure_place_id]" 
{{ $index === -1 ? 'disabled' : '' }}
```

**Si `$index` est `-1`:** Les inputs sont disabled et ne seront pas envoyés.

**Solution:** Ces inputs disabled sont NORMAUX dans le template (id="flight-opt-templates"). 
Mais quand vous éditez une carte RÉELLE, `$index` doit être 0, 1, 2, etc. (pas -1).

### Problème: Le formulaire n'est pas soumis

Ajouter ce code temporaire en haut du fichier `edit.blade.php` (après `<form>`):

```html
<script>
window.addEventListener('submit', function(e) {
    console.log('FORM SUBMIT CAPTURED:', e.target.id, e.target.action);
}, true);
</script>
```

Ouvrir la console (F12) et voir si "FORM SUBMIT CAPTURED" apparaît quand vous cliquez "Enregistrer".

---

## 📞 Informations à me fournir

Pour que je puisse vous aider davantage, envoyez-moi:

1. **Sortie complète de `php diagnose-flights.php`**

2. **Extrait des logs** (après avoir fait un test de sauvegarde):
   ```powershell
   Get-Content storage\logs\laravel.log -Tail 100 | Select-String "flight_options|VoyageController@update"
   ```

3. **Screenshot du payload** (DevTools → Network → Requête PUT → Payload)

4. **Screenshot de la console** du navigateur (F12 → Console) après avoir cliqué "Enregistrer"

---

## ⚡ Test ultra-rapide

Si vous voulez tester immédiatement si le code backend fonctionne:

```powershell
# Entrer dans le shell Laravel
php artisan tinker
```

Puis dans tinker:

```php
// Créer un voyage de test
$voyage = \App\Models\Voyage::first();

// Créer un flight option directement
\App\Models\VoyageFlightOption::create([
    'voyage_id' => $voyage->id,
    'type' => 'outbound',
    'day_number' => 1,
    'from_city' => 'TEST_FROM',
    'to_city' => 'TEST_TO',
    'departure_place_id' => null,
    'depart_at' => now(),
]);

// Vérifier qu'il est bien créé
\App\Models\VoyageFlightOption::where('from_city', 'TEST_FROM')->first();
```

**Si ça échoue:** Problème de DB/migration  
**Si ça marche:** Problème de formulaire/validation
