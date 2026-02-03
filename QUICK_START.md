# 🚀 Quick Start - Package Builder

## Installation rapide (5 minutes)

### 1. Migrations
```bash
php artisan migrate
```

### 2. Storage link
```bash
php artisan storage:link
```

### 3. (Optionnel) Seed données de démo
```bash
php artisan db:seed --class=TravelDayItemsSeeder
```

## ✅ C'est tout ! Le système est prêt.

---

## Test rapide Admin

1. Connectez-vous à l'admin : `/admin/dashboard`
2. Allez à **Circuits > Voyages**
3. Éditez un voyage existant
4. Scrollez jusqu'à la section **"Package Builder - Items par jour"**
5. Cliquez sur un jour pour voir l'accordéon
6. Cliquez **"Ajouter un item"**
7. Remplissez le formulaire :
   - Type : `flight`
   - Titre : `Vol Paris - Dubai`
   - Inclus : ✅
   - Prix delta : `0`
8. Enregistrez

✅ **Votre premier item est créé !**

---

## Test rapide API

### Obtenir le package d'un voyage

```bash
curl http://localhost/api/public/tours/1/package-state
```

**Vous devriez recevoir** :
```json
{
  "success": true,
  "data": {
    "tour": {...},
    "session": {...},
    "pricing": {...},
    "days": [...]
  }
}
```

---

## Prochaines étapes

### Pour l'admin :
- Ajoutez des items pour chaque jour (vols, hôtels, activités)
- Marquez certains items comme "optionnels" (non inclus)
- Ajoutez des options alternatives (upgrade hôtel, etc.)
- Téléversez des images dans la galerie

### Pour le frontend :
- Créez une interface client qui appelle l'API
- Implémentez les boutons "Ajouter option", "Upgrade", etc.
- Affichez le prix en temps réel
- Redirigez vers `/booking/checkout/{token}` après création

### WordPress :
- Créez un shortcode qui appelle l'API
- Affichez le configurateur de package
- Gérez la synchronisation avec `wp_post_id`

---

## Commandes utiles

```bash
# Voir toutes les routes package
php artisan route:list | grep -E "(package|checkout|items)"

# Voir les tables créées
php artisan tinker
>>> \Schema::hasTable('travel_day_items')
>>> \Schema::hasTable('package_sessions')

# Nettoyer les sessions expirées manuellement
php artisan tinker
>>> \App\Models\PackageSession::where('expires_at', '<', now())->delete()

# Voir un package state
php artisan tinker
>>> $voyage = \App\Models\Voyage::first()
>>> $session = \App\Models\PackageSession::create(['voyage_id' => $voyage->id])
>>> $builder = app(\App\Services\Package\PackageStateBuilder::class)
>>> $state = $builder->build($voyage, $session)
>>> echo $state->toJson()
```

---

## Documentation complète

📖 **Documentation principale** : `PACKAGE_BUILDER_README.md`  
🔧 **Installation détaillée** : `INSTALLATION_PACKAGE_BUILDER.md`  
📝 **Exemples API** : `PACKAGE_BUILDER_API_EXAMPLES.json`  
📊 **Résumé complet** : `PACKAGE_BUILDER_SUMMARY.md`

---

## Structure des prix (Important !)

⚠️ **Tous les prix sont en centimes** (integers)

```
Affichage : 150.00 MAD
Stocké DB : 15000 (cents)

Affichage : 8500.00 MAD
Stocké DB : 850000 (cents)
```

**Dans les formulaires admin** : saisissez `150.00` → le système convertit en `15000`  
**Dans l'API** : envoyez directement `15000` (cents)

---

## Endpoints API essentiels

| Méthode | URL | Description |
|---------|-----|-------------|
| GET | `/api/public/tours/{id}/package-state` | Obtenir état package |
| POST | `/api/public/package/session/{id}/action` | Ajouter/Retirer/Modifier items |
| POST | `/api/public/checkout/create` | Créer token checkout |
| GET | `/booking/checkout/{token}` | Page checkout (web) |

---

## Types d'items disponibles

- 🛫 **flight** - Vols (aller, retour, internes)
- 🏨 **hotel_stay** - Hébergements (support multi-jours)
- 🚗 **transfer** - Transferts (aéroport, inter-villes)
- 🎯 **activity** - Activités (visites, excursions)
- 🍽️ **meal** - Repas (petit-déj, déjeuner, dîner)
- ➕ **addon** - Options supplémentaires (assurance, photos, etc.)

---

## Aide & Support

**Problème ?** Consultez la section **Troubleshooting** dans `INSTALLATION_PACKAGE_BUILDER.md`

**Questions courantes :**
- Images ne s'affichent pas → `php artisan storage:link`
- Session expirée trop vite → Modifier `PackageSession::boot()`
- Prix erronés → Vérifier format centimes vs décimal
- Erreur 500 API → Vérifier `storage/logs/laravel.log`

---

**Bon développement ! 🎉**
