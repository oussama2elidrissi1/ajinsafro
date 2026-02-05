# Mapping Complet - Champs WordPress Traveler → Laravel Admin

## Sections Admin Traveler (Référence)

### 1. LOCATION
| WP Meta Key | Type | Description |
|-------------|------|-------------|
| `id_location` | int | ID location principale |
| `location_id` | int | Alias location |
| `address` | text | Adresse complète |
| `multi_location` | string | on/off - Multi-localisation |
| `map_lat` | float | Latitude |
| `map_lng` | float | Longitude |
| `map_zoom` | int | Niveau zoom carte |
| `map_type` | string | Type carte (roadmap, satellite) |

### 2. GENERAL
| WP Meta Key | Type | Description |
|-------------|------|-------------|
| `is_featured` | string | on/'' - Tour à la une |
| `tour_price_by` | string | person/group/fixed |
| `st_tour_external_booking` | url | Lien réservation externe |
| `hide_adult_in_booking_form` | string | on/'' |
| `duration_day` | int | Nombre de jours |
| `max_people` | int | Nombre max personnes |
| `min_people` | int | Nombre min personnes |

### 3. CONTACT INFORMATION
| WP Meta Key | Type | Description |
|-------------|------|-------------|
| `contact_email` | email | Email contact |
| `phone` | string | Téléphone |
| `fax` | string | Fax |
| `website` | url | Site web |

### 4. PRICE SETTINGS
| WP Meta Key | Type | Description |
|-------------|------|-------------|
| `min_price` | float | Prix minimum |
| `base_price` | float | Prix de base |
| `sale_price` | float | Prix soldé |
| `adult_price` | float | Prix adulte |
| `child_price` | float | Prix enfant |
| `infant_price` | float | Prix bébé |
| `discount` | float | Réduction |
| `discount_type` | string | percent/fixed |
| `discount_by_people_type` | string | adult,child,infant |
| `calculator_discount_by_people_type` | string | Calculateur réduction |

### 5. INFORMATION
| WP Meta Key | Type | Description |
|-------------|------|-------------|
| `tours_include` | html/text | Ce qui est inclus |
| `tours_exclude` | html/text | Ce qui est exclu |
| `tours_highlight` | html/text | Points forts |
| `tours_faq` | html/text | FAQ |
| `tours_program` | serialized | Programme jour par jour |
| `tours_program_style` | string | tab/accordion/list |

### 6. AVAILABILITY
| WP Meta Key | Type | Description |
|-------------|------|-------------|
| `tours_booking_period` | string | Période réservation |
| `st_booking_option_type` | string | Type option réservation |
| `check_in` | time | Heure check-in |
| `check_out` | time | Heure check-out |

### 7. CANCEL BOOKING
| WP Meta Key | Type | Description |
|-------------|------|-------------|
| `st_allow_cancel` | string | on/off |
| `st_cancel_percent` | int | % remboursement |
| `st_cancel_number_day` | int | Nombre jours avant |

### 8. ICAL SYNC
| WP Meta Key | Type | Description |
|-------------|------|-------------|
| `ical_url` | url | URL calendrier iCal |

### 9. PAYMENT GATEWAYS
| WP Meta Key | Type | Description |
|-------------|------|-------------|
| `is_meta_payment_gateway_st_paypal` | string | on/'' |
| `is_meta_payment_gateway_st_onepay` | string | on/'' |
| `is_meta_payment_gateway_st_onepay_atm` | string | on/'' |
| `is_meta_payment_gateway_st_payu` | string | on/'' |
| `is_meta_payment_gateway_st_payulatam` | string | on/'' |
| `is_meta_payment_gateway_st_payumoney` | string | on/'' |
| `is_meta_payment_gateway_st_razor` | string | on/'' |

### 10. MEDIA
| WP Meta Key | Type | Description |
|-------------|------|-------------|
| `_thumbnail_id` | int | ID image à la une |
| `gallery` | string | IDs images (CSV) "123,456,789" |
| `video` | url | URL vidéo |

### 11. MAP
| WP Meta Key | Type | Description |
|-------------|------|-------------|
| `st_google_map` | html | iframe Google Map |

### 12. TAXONOMIES
| Taxonomy | Description |
|----------|-------------|
| `st_tour_type` | Type de tour (Adventure, Safari...) |
| `durations` | Durée (1-3 days, 4-7 days...) |
| `language` | Langue principale |
| `languages` | Langues disponibles |

## Format Données WP

### Checkboxes
- **Coché**: `'on'`
- **Décoché**: `''` (string vide)

### Prix
- Stockés en float/int
- Format: `"5000.00"` ou `"5000"`

### HTML/Text
- Tours include/exclude/highlight: HTML ou plain text multi-lignes
- Stocker tel quel

### Gallery
- Format: `"123,456,789"` (CSV sans espaces)
- Array PHP → CSV: `implode(',', $ids)`
- CSV → Array: `explode(',', $csv)`

### Tours Program
- Format: PHP serialized array
- Structure:
```php
[
    [
        'day_number' => 1,
        'title' => 'Day 1: Arrival',
        'description' => 'Check-in at hotel',
        'items' => [...]
    ],
    ...
]
```

## Priorités Implémentation

### Phase 1 (FAIT)
- ✅ Location basique (address)
- ✅ General (is_featured, max_people, tour_price_by)
- ✅ Price basique (adult_price, child_price, min_price)
- ✅ Information (tours_include, tours_exclude, tours_highlight)
- ✅ Media (_thumbnail_id, gallery)
- ✅ Payment gateways (7 checkboxes)
- ✅ Taxonomies (4 taxonomies)

### Phase 2 (À FAIRE)
- Location complète (map_lat, map_lng, multi_location)
- Contact (email, phone, fax, website)
- Price complète (sale_price, infant_price, discount)
- Availability (booking_period, check_in/out)
- Cancel booking
- Ical sync
- Tours FAQ
- Google Map iframe
- Video

## Notes Techniques

### Connexion WP
```php
\DB::connection('wp') // Préfixe automatique: cFdgeZ_
->table('terms') // Devient: cFdgeZ_terms
```

### Upsert Meta
```php
$post->setMeta($metaKey, $value);
// OU
\DB::connection('wp')->table('postmeta')
    ->updateOrInsert(
        ['post_id' => $postId, 'meta_key' => $metaKey],
        ['meta_value' => $value]
    );
```

### Set Taxonomies
```php
// 1. Delete existing
\DB::connection('wp')->table('term_relationships')
    ->where('object_id', $postId)
    ->whereIn('term_taxonomy_id', function($q) use ($taxonomy) {
        $q->select('term_taxonomy_id')
          ->from('term_taxonomy')
          ->where('taxonomy', $taxonomy);
    })->delete();

// 2. Insert new
foreach ($termIds as $termId) {
    $tt = \DB::connection('wp')->table('term_taxonomy')
        ->where('term_id', $termId)
        ->where('taxonomy', $taxonomy)
        ->first();
    
    if ($tt) {
        \DB::connection('wp')->table('term_relationships')->insert([
            'object_id' => $postId,
            'term_taxonomy_id' => $tt->term_taxonomy_id,
            'term_order' => 0,
        ]);
    }
}
```
