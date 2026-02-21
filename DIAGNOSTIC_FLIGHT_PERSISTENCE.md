# 🔍 Diagnostic: Lieu de départ et Date de départ ne persistent pas

## Problème reporté

Les champs **Lieu de départ** (departure_place_id) et **Date de départ** (departure_date) dans les cartes Vol Aller / Vol Retour (onglet Vols) reviennent vides après sauvegarde et rechargement de la page.

---

## ✅ Corrections appliquées

### 1. **VoyageFlightOptionService.php**

**Changements:**
- Parsing de `departure_place_id` et `depart_at` AVANT la vérification de la condition `$filled`
- Condition `$filled` améliorée: inclut maintenant `$departurePlaceId !== null && $departurePlaceId > 0` ET `$departAt !== null`
- Ajout de logs de diagnostic détaillés pour tracer le traitement des valeurs

**Pourquoi:**
- Avant: si `departure_place_id` était envoyé comme chaîne vide `""` ou `"0"`, il n'était pas considéré dans `$filled`
- Maintenant: on parse d'abord, puis on vérifie si la valeur parsée est valide (> 0 ou non null)

### 2. **Logs de diagnostic**

Le service log maintenant chaque fois qu'un flight_option contient `departure_place_id` ou `departure_date`:
```
VoyageFlightOptionService: processing flight option [
    'index' => 0,
    'id' => 123,
    'type' => 'outbound',
    'departure_place_id_raw' => "1",
    'departure_place_id_parsed' => 1,
    'departure_date_raw' => "2026-03-15",
    'depart_at_parsed' => "2026-03-15 00:00:00",
    'filled' => true
]
```

---

## 🧪 Tests de diagnostic

### Étape 1: Vérifier que la migration a été exécutée

```powershell
cd "c:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin"
php artisan migrate:status
```

**Vérifier que ces migrations sont "Ran":**
- `2026_02_21_100000_add_departure_place_id_to_voyage_flight_options`
- `2026_02_21_100001_add_departure_place_id_to_aj_tour_flights`

**Si non exécutées:**
```powershell
php artisan migrate
```

### Étape 2: Vérifier la structure de la table

```sql
DESCRIBE voyage_flight_options;
```

**Colonnes attendues:**
- `departure_place_id` (bigint unsigned, nullable)
- `depart_at` (datetime, nullable)
- `arrive_at` (datetime, nullable)

### Étape 3: Activer les logs de debug

Éditer `config/logging.php`:
```php
'channels' => [
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'debug', // Changer de 'info' à 'debug'
    ],
],
```

### Étape 4: Tester la sauvegarde

1. **Ouvrir un voyage en édition**
2. **Onglet Vols** → Section "Vols Aller (options)"
3. **Cliquer sur "Modifier"** sur une carte vol (ou ajouter un nouveau vol)
4. **Remplir UNIQUEMENT:**
   - **Lieu de départ:** Sélectionner un lieu (ex: "Paris (CDG)")
   - **Date départ:** Sélectionner une date (ex: 15/03/2026)
   - **Laisser les autres champs vides** (from_city, to_city, airline, etc.)
5. **Cliquer sur "Enregistrer"** (bouton dans la carte)
6. **Attendre la page de rechargement**
7. **Vérifier:** Le lieu et la date doivent être toujours présents

### Étape 5: Vérifier les logs

```powershell
Get-Content storage\logs\laravel.log -Tail 50
```

**Rechercher ces entrées:**

1. **Payload reçu par le controller:**
```
[debug] VoyageController@update flight_options payload {
    "tour_id": 123,
    "voyage_id": 1,
    "count": 1,
    "first": {
        "id": "1",
        "type": "outbound",
        "departure_place_id": "1",
        "departure_date": "2026-03-15",
        ...
    }
}
```

2. **Traitement par le service:**
```
[debug] VoyageFlightOptionService: processing flight option {
    "index": 0,
    "id": "1",
    "type": "outbound",
    "departure_place_id_raw": "1",
    "departure_place_id_parsed": 1,
    "departure_date_raw": "2026-03-15",
    "depart_at_parsed": "2026-03-15 00:00:00",
    "filled": true
}
```

---

## 🚨 Scénarios de problème

### Scénario A: Les valeurs ne sont pas dans le payload

**Symptôme:** Le log `VoyageController@update flight_options payload` ne contient pas `departure_place_id` ou `departure_date`

**Cause possible:**
- Les inputs sont `disabled` dans le formulaire (vérifier que `{{ $index === -1 ? 'disabled' : '' }}` ne s'applique qu'aux templates)
- Les inputs n'ont pas les bons attributs `name=""`
- Le formulaire n'est pas soumis correctement

**Solution:**
1. Inspecter le DOM dans le navigateur (F12)
2. Vérifier que les inputs ont bien:
   - `name="flight_options[0][departure_place_id]"`
   - `name="flight_options[0][departure_date]"`
   - PAS d'attribut `disabled` (sauf si c'est le template dans `#flight-opt-templates`)

### Scénario B: Les valeurs sont envoyées mais pas parsées

**Symptôme:** Le log `processing flight option` montre:
- `"departure_place_id_raw": "1"` mais `"departure_place_id_parsed": null`
- OU `"departure_date_raw": "2026-03-15"` mais `"depart_at_parsed": null`

**Cause:** Problème de parsing (ne devrait pas arriver avec le code corrigé)

**Solution:**
1. Vérifier le format de la date envoyée (devrait être `Y-m-d`)
2. Vérifier que `departure_place_id` est un entier > 0

### Scénario C: Les valeurs sont parsées mais `filled = false`

**Symptôme:** Le log montre:
- `"departure_place_id_parsed": 1`
- `"depart_at_parsed": "2026-03-15 00:00:00"`
- `"filled": false`

**Cause:** Bug dans la condition `$filled` (ne devrait plus arriver)

**Solution:** Vérifier que le code dans `VoyageFlightOptionService.php` lignes 59-91 correspond bien à la version corrigée

### Scénario D: Les valeurs sont sauvegardées mais disparaissent au reload

**Symptôme:** 
- Les logs montrent que tout est correct
- La base de données contient les valeurs après sauvegarde
- MAIS au rechargement, les champs sont vides

**Cause possible:**
1. Un autre processus écrase les valeurs (sync WP, observer, etc.)
2. Les valeurs ne sont pas chargées correctement dans le controller `edit()`

**Solution:**
1. Vérifier la DB directement après sauvegarde:
```sql
SELECT id, voyage_id, type, departure_place_id, depart_at, from_city, to_city
FROM voyage_flight_options
WHERE voyage_id = [ID_VOYAGE];
```
2. Si les valeurs sont présentes en DB mais pas dans le formulaire, vérifier:
   - `VoyageController@edit` ligne 359: `getOptionsForVoyage()`
   - `_flight_option_card.blade.php` ligne 61: la condition `{{ ($opt && (string)($opt->departure_place_id ?? '') === (string)($place->id ?? '')) ? 'selected' : '' }}`

---

## 📋 Checklist complète

- [ ] Migrations exécutées (`php artisan migrate`)
- [ ] Colonne `departure_place_id` existe dans `voyage_flight_options`
- [ ] Logs debug activés (`config/logging.php` niveau `debug`)
- [ ] Test: remplir lieu + date dans un vol, sauvegarder
- [ ] Vérifier logs: payload contient bien les valeurs
- [ ] Vérifier logs: parsing correct (parsed values non null)
- [ ] Vérifier logs: `filled = true`
- [ ] Vérifier DB: valeurs présentes après sauvegarde
- [ ] Recharger page: lieu + date toujours affichés

---

## 📞 Si le problème persiste

**Fournir ces informations:**

1. **Extrait des logs** depuis `storage/logs/laravel.log` (rechercher "flight_options payload" et "processing flight option")
2. **Requête SQL:**
```sql
SELECT id, voyage_id, type, departure_place_id, depart_at, from_city, to_city, airline_id
FROM voyage_flight_options
WHERE voyage_id = [ID_VOYAGE]
ORDER BY type, sort_order;
```
3. **Payload du formulaire** (depuis l'onglet Network de DevTools après soumission)
4. **Version de PHP** et **version de Laravel**

---

## ✨ Comportement attendu après correction

**Avant:**
- Remplir "Lieu de départ" + "Date départ" → Sauvegarder → **Les valeurs disparaissent**

**Après:**
- Remplir "Lieu de départ" + "Date départ" → Sauvegarder → **Les valeurs restent affichées**
- Même si aucun autre champ n'est rempli (airline, from_city, to_city, etc.)

**Note:** Il est NORMAL que si vous ne remplissez AUCUN champ (ni lieu, ni date, ni compagnie, etc.), le vol ne soit pas sauvegardé. Mais dès qu'au moins UN champ significatif est rempli (lieu, date, compagnie, ville, n° vol), le vol doit persister.
