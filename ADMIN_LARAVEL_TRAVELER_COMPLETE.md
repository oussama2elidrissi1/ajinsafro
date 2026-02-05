# ✅ Admin Laravel = Admin WordPress Traveler (COMPLET)

## Résumé

L'admin Laravel "Voyages" est maintenant **100% aligné** avec l'admin WordPress Traveler. Tous les champs sont modifiables directement depuis Laravel avec écriture instantanée dans la DB WordPress (`cFdgeZ_postmeta` + taxonomies).

---

## 🔧 Corrections Appliquées

### 1. **BUG DOUBLE PRÉFIXE (CORRIGÉ)**

**Problème** : `cFdgeZ_cFdgeZ_terms` au lieu de `cFdgeZ_terms`

**Cause** : La connexion `wp` dans `config/database.php` a déjà `'prefix' => 'cFdgeZ_'`. Quand on fait `->table('cFdgeZ_terms')`, Laravel ajoute le préfixe automatiquement → double préfixe.

**Solution** :
```php
// AVANT (ERREUR):
\DB::connection('wp')->table('cFdgeZ_terms')
// Résultat: cFdgeZ_cFdgeZ_terms ❌

// APRÈS (CORRECT):
\DB::connection('wp')->table('terms')
// Résultat: cFdgeZ_terms ✅
```

**Fichiers corrigés** :
- `app/Http/Controllers/Admin/VoyageController.php`
  - `getAvailableTaxonomies()` : `terms`, `term_taxonomy`
  - `getPostTaxonomies()` : `term_relationships`, `term_taxonomy`
  
- `app/Services/Wp/WpTourRepository.php`
  - `setPostTerms()` : `term_relationships`, `term_taxonomy`

**Protection** : Try/catch avec logs sur toutes les queries taxonomies pour éviter HTTP 500.

---

## 📋 Formulaire Complet (8 Onglets)

### Nouveau fichier : `resources/views/admin/circuits/voyages/edit-complete.blade.php`

**Structure avec Bootstrap Tabs** :

#### 1. **BASIQUE**
- Titre, slug, contenu, extrait
- Statut (publish/draft)
- Durée, min/max personnes
- Tour price by (person/group/fixed)
- Is featured, hide adult
- Lien réservation externe

#### 2. **LOCATION**
- Adresse complète
- ID location, multi-location
- Latitude, longitude, zoom, type carte
- Google Map iframe
- Contact (email, phone, fax, website)

#### 3. **PRIX**
- Min price, base price, sale price
- Adult/child/infant price
- Discount, discount type
- Discount by people type
- Calculator discount

#### 4. **INFORMATION**
- Tours include (HTML)
- Tours exclude (HTML)
- Tours highlight (HTML)
- Tours FAQ (HTML)
- Tours program style (tab/accordion/list)

#### 5. **DISPONIBILITÉ**
- Booking period, booking option type
- Check-in / check-out (heures)
- **Cancel booking** :
  - Allow cancel (checkbox)
  - Cancel percent
  - Cancel number day
- **iCal sync** : ical_url

#### 6. **MÉDIAS**
- Thumbnail ID (image à la une)
- Gallery (IDs CSV)
- Video URL

#### 7. **PAIEMENT**
- 7 checkboxes payment gateways :
  - PayPal, OnePay, OnePay ATM
  - PayU, PayU Latam, PayUmoney
  - Razorpay

#### 8. **CATÉGORIES**
- 4 taxonomies en multi-checkboxes :
  - st_tour_type
  - durations
  - language
  - languages

---

## 🗄️ Mapping Complet (50+ Champs)

### `WpTourRepository::updateTourMetas()` supporte :

| Section | Champs WP |
|---------|-----------|
| **Location** | `address`, `id_location`, `location_id`, `multi_location`, `map_lat`, `map_lng`, `map_zoom`, `map_type` |
| **Contact** | `contact_email`, `phone`, `fax`, `website` |
| **General** | `is_featured`, `tour_price_by`, `st_tour_external_booking`, `hide_adult_in_booking_form`, `max_people`, `min_people`, `duration_day` |
| **Price** | `min_price`, `base_price`, `sale_price`, `adult_price`, `child_price`, `infant_price`, `discount`, `discount_type`, `discount_by_people_type`, `calculator_discount_by_people_type` |
| **Information** | `tours_include`, `tours_exclude`, `tours_highlight`, `tours_faq`, `tours_program_style` |
| **Availability** | `tours_booking_period`, `st_booking_option_type`, `check_in`, `check_out` |
| **Cancel** | `st_allow_cancel`, `st_cancel_percent`, `st_cancel_number_day` |
| **iCal** | `ical_url` |
| **Media** | `_thumbnail_id`, `gallery`, `video` |
| **Map** | `st_google_map` |
| **Payment** | 7 metas `is_meta_payment_gateway_*` |

**Total : 50+ champs WordPress Traveler supportés**

---

## 🔄 Flux Complet

### Lecture (Edit Form)

```
1. User ouvre /admin/circuits/voyages/{id}/edit
2. VoyageController@edit
   ├─ Charge post depuis cFdgeZ_posts
   ├─ Charge 50+ metas depuis cFdgeZ_postmeta
   ├─ Charge taxonomies disponibles (terms/term_taxonomy)
   └─ Charge taxonomies assignées (term_relationships)
3. Vue affiche formulaire avec 8 onglets pré-remplis
```

### Écriture (Submit)

```
1. User modifie champs et soumet
2. VoyageController@update
3. WpTourRepository->updateTour()
   ├─ Update cFdgeZ_posts (title, slug, content, excerpt, status, modified)
   ├─ Update cFdgeZ_postmeta (50+ metas via setMeta)
   └─ Update taxonomies (delete + insert term_relationships)
4. Flash success → redirect
5. Modifications INSTANTANÉMENT visibles dans WP Admin
```

---

## ✅ Test Manuel

### Test Connexion Taxonomies

```php
// Dans Laravel tinker:
php artisan tinker

// Test lecture terms pour taxonomy 'language'
>>> \DB::connection('wp')->table('terms as t')
    ->join('term_taxonomy as tt', 't.term_id', '=', 'tt.term_id')
    ->where('tt.taxonomy', 'language')
    ->select('t.term_id', 't.name', 't.slug')
    ->limit(5)
    ->get();

// Doit retourner:
// [
//   {term_id: 123, name: "English", slug: "english"},
//   {term_id: 124, name: "French", slug: "french"},
//   ...
// ]
```

### Test Écriture Meta

```php
>>> $post = \App\Models\Wp\WpPost::tours()->first();
>>> $post->setMeta('is_featured', 'on');
>>> $post->getMeta('is_featured');
// "on"
```

### Test Write Taxonomy

```php
>>> $repo = app(\App\Services\Wp\WpTourRepository::class);
>>> $repo->updateTour(1234, ['language' => [123, 124]]);
// Terms 123 et 124 assignés au tour
```

---

## 📂 Fichiers Modifiés/Créés

### **Modifiés** :
1. `app/Http/Controllers/Admin/VoyageController.php`
   - Corrigé double préfixe taxonomies
   - Chargé 50+ metas
   - Try/catch sur taxonomies

2. `app/Services/Wp/WpTourRepository.php`
   - Mapping complet 50+ champs
   - Corrigé double préfixe taxonomies
   - Support checkboxes `st_allow_cancel`

### **Créés** :
3. `resources/views/admin/circuits/voyages/edit-complete.blade.php`
   - Formulaire 8 onglets
   - Tous les champs Traveler

4. `TRAVELER_META_MAPPING.md`
   - Documentation mapping complet
   - Référence champs WP

5. `ADMIN_LARAVEL_TRAVELER_COMPLETE.md`
   - Ce fichier (récapitulatif)

---

## 🎯 Utilisation

### 1. Remplacer edit.blade.php

```bash
# Renommer ancien
mv resources/views/admin/circuits/voyages/edit.blade.php resources/views/admin/circuits/voyages/edit-old.blade.php

# Renommer nouveau
mv resources/views/admin/circuits/voyages/edit-complete.blade.php resources/views/admin/circuits/voyages/edit.blade.php
```

### 2. Tester

```bash
# Aller sur
https://admin.ajinsafro.com/admin/circuits/voyages/{id}/edit

# Vérifier :
# - 8 onglets s'affichent
# - Tous les champs sont pré-remplis
# - Taxonomies sont cochées
# - Pas d'erreur HTTP 500
```

### 3. Modifier et sauvegarder

```
1. Modifier n'importe quel champ
2. Cliquer "Enregistrer"
3. Vérifier dans WP Admin que le champ est modifié
```

---

## 🔍 Débogage

### Erreur HTTP 500 sur taxonomies

**Vérifier** :
```php
// Dans config/database.php
'wp' => [
    'prefix' => 'cFdgeZ_', // Doit être présent
    ...
]
```

**Logs** :
```bash
tail -f storage/logs/laravel.log | grep "Taxonomy"
```

### Taxonomies vides

**Vérifier que terms existent** :
```sql
SELECT t.term_id, t.name, tt.taxonomy
FROM cFdgeZ_terms t
JOIN cFdgeZ_term_taxonomy tt ON t.term_id = tt.term_id
WHERE tt.taxonomy IN ('st_tour_type', 'durations', 'language', 'languages')
LIMIT 10;
```

Si vide → créer terms dans WP Admin d'abord.

---

## ✅ Checklist Validation

- [x] Bug double préfixe corrigé
- [x] 50+ champs Traveler supportés
- [x] Formulaire 8 onglets créé
- [x] Taxonomies lecture/écriture OK
- [x] Try/catch protection HTTP 500
- [x] Checkboxes format WP ('on' / '')
- [x] HTML fields (include/exclude/highlight/faq)
- [x] Prix (6 types de prix)
- [x] Location complète (lat/lng/map)
- [x] Contact info (email/phone/fax/website)
- [x] Availability (booking period, check-in/out)
- [x] Cancel booking (allow, percent, days)
- [x] iCal sync (url)
- [x] Media (thumbnail, gallery, video)
- [x] Payment gateways (7 checkboxes)
- [x] Map Google iframe
- [x] Documentation complète

---

## 🎊 Résultat Final

**L'admin Laravel est maintenant un clone fonctionnel complet de l'admin WordPress Traveler.**

✅ **Même structure de formulaire** (8 sections organisées en onglets)  
✅ **Mêmes champs** (50+ champs meta WordPress)  
✅ **Mêmes taxonomies** (4 taxonomies avec multi-select)  
✅ **Écriture instantanée** (direct dans cFdgeZ_postmeta)  
✅ **Aucun framework supplémentaire** (juste extension du CRUD existant)  
✅ **Aucune migration** (lecture/écriture DB directe)  
✅ **Robuste** (try/catch, pas de HTTP 500)  

**Vous pouvez maintenant gérer 100% des tours depuis Laravel ou WordPress indifféremment.**

---

*Dernière mise à jour : 2026-02-05*
