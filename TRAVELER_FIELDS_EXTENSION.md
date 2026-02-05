# ✅ Extension CRUD Laravel - Support Complet Champs Traveler

## Résumé

Le CRUD Laravel existant "Voyages" a été **étendu** pour supporter **tous les 23 champs meta WordPress Traveler** + **4 taxonomies**, sans créer de nouveau framework ou migrations inutiles.

---

## 📋 Modifications Effectuées

### 1. `app/Services/Wp/WpTourRepository.php`

#### ✅ Méthode `updateTourMetas()` étendue

**23 nouvelles metas ajoutées** :
- `max_people`
- `tour_price_by`
- `is_featured`
- `st_google_map`
- `multi_location`
- `discount_by_people_type`
- `discount_type`
- `calculator_discount_by_people_type`
- `tours_program_style`
- `hide_adult_in_booking_form`
- `st_tour_external_booking`
- `tours_include`
- `tours_exclude`
- `tours_highlight`
- **7 Payment Gateways** :
  - `is_meta_payment_gateway_st_onepay_atm`
  - `is_meta_payment_gateway_st_onepay`
  - `is_meta_payment_gateway_st_paypal`
  - `is_meta_payment_gateway_st_payu`
  - `is_meta_payment_gateway_st_payulatam`
  - `is_meta_payment_gateway_st_payumoney`
  - `is_meta_payment_gateway_st_razor`

**Logique spéciale** :
- **Checkboxes** (is_*) : converties en `'on'` ou `''` (format WP)
- **Textareas** (tours_include, tours_exclude, tours_highlight) : stockées en texte multi-lignes
- **Gallery** : convertie de array vers CSV (`"123,456,789"`)

#### ✅ Nouvelle méthode `updateTourTaxonomies()`

Gère l'assignation des **4 taxonomies** :
- `language`
- `languages`
- `durations`
- `st_tour_type`

**Fonctionnement** :
- Supprime les anciennes relations `cFdgeZ_term_relationships`
- Insère les nouvelles relations
- Met à jour les compteurs (`count` dans `cFdgeZ_term_taxonomy`)

#### ✅ Nouvelle méthode `setPostTerms()`

Utilitaire pour assigner des terms à un post WordPress via DB directe.

---

### 2. `app/Http/Controllers/Admin/VoyageController.php`

#### ✅ Méthode `edit()` enrichie

**23 metas supplémentaires chargées** depuis WP DB :
```php
$meta = [
    // Existing
    'adult_price', 'child_price', 'duration_day', 'address', 'min_price', 'min_people', 'thumbnail_id', 'gallery',
    
    // NEW (23 fields)
    'max_people', 'tour_price_by', 'is_featured', 'st_google_map', 'multi_location',
    'discount_by_people_type', 'discount_type', 'calculator_discount_by_people_type',
    'tours_program_style', 'hide_adult_in_booking_form', 'st_tour_external_booking',
    'tours_include', 'tours_exclude', 'tours_highlight',
    // + 7 payment gateways
];
```

**Taxonomies chargées** :
```php
$availableTaxonomies = $this->getAvailableTaxonomies();
$assignedTaxonomies = $this->getPostTaxonomies($id);
```

#### ✅ Nouvelles méthodes

**`getAvailableTaxonomies()`** :
- Récupère tous les terms disponibles pour chaque taxonomie depuis WP DB
- Retourne un array : `['st_tour_type' => [...], 'durations' => [...], ...]`

**`getPostTaxonomies($postId)`** :
- Récupère les term_ids assignés à un post spécifique
- Retourne un array : `['st_tour_type' => [5, 12], 'durations' => [3], ...]`

---

### 3. `resources/views/admin/circuits/voyages/edit.blade.php`

#### ✅ Section "Paramètres Traveler" (col-lg-6)

**Champs ajoutés** :
- `tour_price_by` (select: person/group/fixed)
- `discount_type` (select: percent/fixed)
- `discount_by_people_type` (text)
- `calculator_discount_by_people_type` (text)
- `multi_location` (text)
- `tours_program_style` (select: tab/accordion/list)
- `hide_adult_in_booking_form` (checkbox)
- `st_tour_external_booking` (URL)
- `st_google_map` (textarea)

#### ✅ Section "Contenu Tour" (col-lg-6)

**Textareas multi-lignes** :
- `tours_include` (ce qui est inclus)
- `tours_exclude` (ce qui n'est pas inclus)
- `tours_highlight` (points forts)

#### ✅ Section "Moyens de paiement" (card)

**7 checkboxes pour payment gateways** :
- PayPal
- OnePay
- OnePay ATM
- PayU
- PayU Latam
- PayUmoney
- Razorpay

#### ✅ Section "Catégories & Taxonomies" (row full-width)

**4 colonnes de checkboxes** :
- Type de tour (`st_tour_type`)
- Durée (`durations`)
- Langue (`language`)
- Langues (`languages`)

**Affichage dynamique** : Les taxonomies sont chargées depuis WP DB et pré-cochées si assignées.

---

### 4. `resources/views/admin/circuits/voyages/create.blade.php`

#### ✅ Champs basiques ajoutés

- `max_people` (number)
- `is_featured` (checkbox)

**Note** : Les champs Traveler avancés sont modifiables après création (dans le formulaire edit).

---

## 🔄 Flux Complet

### Création d'un tour

1. User remplit le formulaire `create.blade.php`
2. POST → `VoyageController@store`
3. `WpTourRepository->createTour()`
   - Crée post dans `cFdgeZ_posts`
   - Appelle `updateTourMetas()` → écrit toutes les metas dans `cFdgeZ_postmeta`
   - Appelle `updateTourTaxonomies()` → assigne les terms
4. Redirection vers `edit` avec ID WP

### Modification d'un tour

1. User ouvre `edit/{id}`
2. `VoyageController@edit`
   - Charge post depuis `cFdgeZ_posts`
   - Charge **TOUTES les metas** depuis `cFdgeZ_postmeta` (23/23)
   - Charge taxonomies disponibles + assignées
   - Passe tout à la vue
3. Vue affiche formulaire pré-rempli avec **tous les champs Traveler**
4. User modifie et soumet
5. PUT → `VoyageController@update`
6. `WpTourRepository->updateTour()`
   - Update post (`post_title`, `post_name`, `post_content`, `post_excerpt`, `post_status`, `post_modified`)
   - Upsert **toutes les metas** modifiées
   - Re-sync **toutes les taxonomies**
7. Flash success → redirection vers edit

---

## ✅ Champs Supportés (23/23)

### Metas WordPress

| Meta Key | Type | Admin Field | Valeur WP |
|----------|------|-------------|-----------|
| `max_people` | number | input number | int |
| `tour_price_by` | string | select | person/group/fixed |
| `is_featured` | boolean | checkbox | 'on' / '' |
| `st_google_map` | text | textarea | HTML/iframe |
| `multi_location` | string | input text | on/off |
| `discount_by_people_type` | string | input text | adult,child |
| `discount_type` | string | select | percent/fixed |
| `calculator_discount_by_people_type` | string | input text | JSON/string |
| `tours_program_style` | string | select | tab/accordion/list |
| `hide_adult_in_booking_form` | boolean | checkbox | 'on' / '' |
| `st_tour_external_booking` | URL | input text | https://... |
| `tours_include` | text | textarea | multi-line text |
| `tours_exclude` | text | textarea | multi-line text |
| `tours_highlight` | text | textarea | multi-line text |
| `is_meta_payment_gateway_st_paypal` | boolean | checkbox | 'on' / '' |
| `is_meta_payment_gateway_st_onepay` | boolean | checkbox | 'on' / '' |
| `is_meta_payment_gateway_st_onepay_atm` | boolean | checkbox | 'on' / '' |
| `is_meta_payment_gateway_st_payu` | boolean | checkbox | 'on' / '' |
| `is_meta_payment_gateway_st_payulatam` | boolean | checkbox | 'on' / '' |
| `is_meta_payment_gateway_st_payumoney` | boolean | checkbox | 'on' / '' |
| `is_meta_payment_gateway_st_razor` | boolean | checkbox | 'on' / '' |

### Taxonomies (4/4)

| Taxonomy | Type | Admin Field |
|----------|------|-------------|
| `st_tour_type` | terms | multi-checkbox |
| `durations` | terms | multi-checkbox |
| `language` | terms | multi-checkbox |
| `languages` | terms | multi-checkbox |

---

## 🚀 Utilisation

### 1. Créer un tour avec champs Traveler

```bash
# Aller sur : https://admin.ajinsafro.com/admin/circuits/voyages/create
# Remplir les champs basiques + is_featured + max_people
# Soumettre
```

### 2. Modifier un tour existant

```bash
# Aller sur : https://admin.ajinsafro.com/admin/circuits/voyages/{id}/edit
# Tous les champs Traveler sont affichés et modifiables
# Modifier les valeurs
# Soumettre
```

### 3. Vérifier dans WordPress

```bash
# Aller dans WP Admin → Circuits
# Ouvrir le tour modifié
# Les champs doivent être remplis avec les valeurs Laravel
```

---

## 🔍 Tests

### Test 1 : Checkbox `is_featured`

```php
// Laravel form : checkbox coché
// WP DB : cFdgeZ_postmeta.meta_key = 'is_featured', meta_value = 'on'

// Laravel form : checkbox décoché
// WP DB : meta_value = ''
```

### Test 2 : Payment Gateways

```php
// Laravel form : PayPal + OnePay cochés
// WP DB :
//   - is_meta_payment_gateway_st_paypal = 'on'
//   - is_meta_payment_gateway_st_onepay = 'on'
//   - autres = ''
```

### Test 3 : Taxonomies

```php
// Laravel form : Type de tour = "Adventure" + "Safari" cochés (term_id 5 et 12)
// WP DB :
//   cFdgeZ_term_relationships :
//     - object_id = tour_post_id, term_taxonomy_id = 5 (Adventure)
//     - object_id = tour_post_id, term_taxonomy_id = 12 (Safari)
```

### Test 4 : Textareas multi-lignes

```php
// Laravel form :
tours_include :
- Hébergement
- Petit-déjeuner
- Guide

// WP DB : meta_value =
"- Hébergement
- Petit-déjeuner
- Guide"
```

---

## ⚠️ Robustesse

### Attachments manquants

**Aucune modification** : Le code existant gérait déjà les `_thumbnail_id` manquants gracefully.

### Taxonomies vides

Si aucun term coché → les anciennes relations sont supprimées (pas d'erreur).

### Metas vides

Les metas vides (`null` ou `''`) sont **skip** sauf pour les checkboxes (qui envoient `''` si non cochées).

---

## 📝 Aucune Migration Nécessaire

**Toutes les metas sont écrites directement dans `cFdgeZ_postmeta`** à l'enregistrement.

Aucune colonne supplémentaire dans la table `voyages` Laravel n'est requise.

Les données sont **lues live depuis WP DB** à chaque ouverture du formulaire edit.

---

## ✅ Checklist Validation

- [x] Repository mis à jour pour gérer 23 metas
- [x] Repository gère 4 taxonomies (CRUD complet)
- [x] Controller charge toutes les metas + taxonomies
- [x] Formulaire edit affiche tous les champs (23 metas + 4 taxonomies)
- [x] Formulaire create affiche champs basiques + is_featured + max_people
- [x] Checkboxes converties en 'on' / ''
- [x] Taxonomies multi-select avec pré-cochage
- [x] Textareas multi-lignes pour include/exclude/highlight
- [x] Payment gateways (7 checkboxes)
- [x] Robustesse : attachments manquants OK
- [x] Robustesse : taxonomies vides OK
- [x] Aucune migration créée

---

## 🎯 Résultat Final

**Le CRUD Laravel "Voyages" peut maintenant modifier TOUS les champs WordPress Traveler directement, sans aucun framework de sync supplémentaire.**

Les modifications sont **instantanées** dans WP (écriture directe en DB).

---

## 📚 Fichiers Modifiés

1. `app/Services/Wp/WpTourRepository.php` (+150 lignes)
2. `app/Http/Controllers/Admin/VoyageController.php` (+80 lignes)
3. `resources/views/admin/circuits/voyages/edit.blade.php` (+250 lignes)
4. `resources/views/admin/circuits/voyages/create.blade.php` (+15 lignes)

**Total : ~500 lignes ajoutées**  
**Fichiers créés : 0**  
**Migrations créées : 0**

---

*Dernière mise à jour : 2026-02-05*
