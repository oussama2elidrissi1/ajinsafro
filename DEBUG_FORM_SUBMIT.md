# Debug Form Submit - Vérifier ce qui est envoyé

## Problème
Les données sont affichées correctement (lecture OK), mais les modifications ne sont pas sauvegardées.

## Solution de Debug

### 1. Ajouter un log temporaire dans le controller

**Fichier** : `app/Http/Controllers/Admin/VoyageController.php`

**Dans la méthode `update()`**, ajouter juste après la validation :

```php
public function update(UpdateWpTourRequest $request, int $id): RedirectResponse
{
    $validated = $request->validated();
    
    // DEBUG: Log ce qui est reçu
    \Log::info('Form data received', [
        'tour_id' => $id,
        'data_keys' => array_keys($validated),
        'locations' => $validated['locations'] ?? 'NOT SET',
        'is_featured' => $validated['is_featured'] ?? 'NOT SET',
        'adult_price' => $validated['adult_price'] ?? 'NOT SET',
    ]);

    // Convertir gallery CSV en array
    if (!empty($validated['gallery_ids'])) {
        $validated['gallery_ids'] = array_filter(array_map('trim', explode(',', $validated['gallery_ids'])));
    }

    try {
        $this->repository->updateTour($id, $validated);
        // ...
```

### 2. Vérifier les logs

```bash
tail -f storage/logs/laravel.log
```

Puis modifier un tour et sauvegarder. Vous verrez :
```
[2026-02-05 15:30:45] local.INFO: Form data received
{
    "tour_id": 14386,
    "data_keys": ["title", "slug", "content", "excerpt", "locations", "adult_price", ...],
    "locations": [54, 55, 56],
    "is_featured": 1,
    "adult_price": "5000"
}
```

### 3. Vérifier le Request Validator

**Fichier** : `app/Http/Requests/UpdateWpTourRequest.php`

Vérifier que **tous les nouveaux champs** sont dans les `rules()` :

```php
public function rules(): array
{
    return [
        'title' => 'required|string|max:255',
        'slug' => 'nullable|string|max:255',
        'content' => 'nullable|string',
        'excerpt' => 'nullable|string',
        'post_status' => 'nullable|string',
        
        // Locations
        'locations' => 'nullable|array',
        'locations.*' => 'integer',
        
        // Prix
        'adult_price' => 'nullable|numeric',
        'child_price' => 'nullable|numeric',
        'infant_price' => 'nullable|numeric',
        'min_price' => 'nullable|numeric',
        'base_price' => 'nullable|numeric',
        'sale_price' => 'nullable|numeric',
        
        // Checkboxes
        'is_featured' => 'nullable',
        'hide_adult_in_booking_form' => 'nullable',
        'st_allow_cancel' => 'nullable',
        
        // Payment gateways
        'is_meta_payment_gateway_st_paypal' => 'nullable',
        'is_meta_payment_gateway_st_onepay' => 'nullable',
        'is_meta_payment_gateway_st_onepay_atm' => 'nullable',
        'is_meta_payment_gateway_st_payu' => 'nullable',
        'is_meta_payment_gateway_st_payulatam' => 'nullable',
        'is_meta_payment_gateway_st_payumoney' => 'nullable',
        'is_meta_payment_gateway_st_razor' => 'nullable',
        
        // Autres champs
        'max_people' => 'nullable|integer',
        'min_people' => 'nullable|integer',
        'duration_day' => 'nullable|integer',
        'tour_price_by' => 'nullable|string',
        'discount_type' => 'nullable|string',
        'discount' => 'nullable|string',
        'discount_by_people_type' => 'nullable|string',
        'calculator_discount_by_people_type' => 'nullable|string',
        
        // Location/Map
        'map_lat' => 'nullable|string',
        'map_lng' => 'nullable|string',
        'map_zoom' => 'nullable|integer',
        'map_type' => 'nullable|string',
        'st_google_map' => 'nullable|string',
        
        // Contact
        'contact_email' => 'nullable|email',
        'phone' => 'nullable|string',
        'fax' => 'nullable|string',
        'website' => 'nullable|string',
        
        // Information
        'tours_include' => 'nullable|string',
        'tours_exclude' => 'nullable|string',
        'tours_highlight' => 'nullable|string',
        'tours_faq' => 'nullable|string',
        'tours_program_style' => 'nullable|string',
        
        // Availability
        'tours_booking_period' => 'nullable|string',
        'st_booking_option_type' => 'nullable|string',
        'check_in' => 'nullable|string',
        'check_out' => 'nullable|string',
        'st_cancel_percent' => 'nullable|integer',
        'st_cancel_number_day' => 'nullable|integer',
        'ical_url' => 'nullable|string',
        
        // Media
        'thumbnail_id' => 'nullable|integer',
        'gallery_ids' => 'nullable|string',
        'video' => 'nullable|string',
        
        // Taxonomies
        'st_tour_type' => 'nullable|array',
        'st_tour_type.*' => 'integer',
        'durations' => 'nullable|array',
        'durations.*' => 'integer',
        'language' => 'nullable|array',
        'language.*' => 'integer',
        'languages' => 'nullable|array',
        'languages.*' => 'integer',
    ];
}
```

### 4. Si les champs ne sont toujours pas sauvegardés

**Vérifier que `WpPost::setMeta()` fonctionne** :

```php
php artisan tinker

$post = \App\Models\Wp\WpPost::tours()->first();
$post->setMeta('test_meta', 'test_value');

// Vérifier en DB
\DB::connection('wp')
    ->table('postmeta')
    ->where('post_id', $post->ID)
    ->where('meta_key', 'test_meta')
    ->value('meta_value');
// Doit retourner: "test_value"
```

## Checklist Debug

- [ ] Log ajouté dans controller update()
- [ ] Logs montrent les données reçues
- [ ] Request validator autorise tous les champs
- [ ] WpPost::setMeta() fonctionne
- [ ] Repository updateTourMetas() est appelé
- [ ] DB WP contient les nouvelles valeurs après save

## Solution Rapide

Si le problème persiste, c'est probablement le **Request Validator** qui filtre les champs.

**Action** : Vérifier `app/Http/Requests/UpdateWpTourRequest.php` et ajouter TOUS les champs dans `rules()`.
