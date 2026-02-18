# Quick Start: Gestion Vols/Hôtels/Transferts par Jour

## 🚀 En 5 minutes

### Étape 1: Vérifier l'implémentation Frontend ✅

Ouvrez votre navigateur, aller à l'admin et chargez un voyage en édition.

**Tester dans la console (F12)**:
```javascript
// Doit afficher l'objet du gestionnaire
window.dayItemsManager
```

**Résultat attendu**: Objet avec méthodes `getDay`, `setFlights`, `getHotel`, `setTransfers`, etc.

---

### Étape 2: Tester le Drawer (Ajouter un élément)

1. Allez à l'onglet **Programme**
2. Cliquez sur **"Ajouter un élément"** pour un jour (bouton jaune)
3. Le drawer s'ouvre avec **4 onglets**:
   - ✅ **Activités**
   - ✅ **Hôtels** (sélect simple)
   - ✅ **Transferts** (multi-checkboxes)
   - ✅ **Vols** (multi-checkboxes)

---

### Étape 3: Tester la sélection

**Exemple - Jour 1:**

1. Onglet **Hôtels** → Sélectionner un hôtel
   - L'input hidden doit se remplir: `programme_days[0][hotel_id] = "5"`
   - Vérifier dans la console: `window.dayItemsManager.getHotel('0')` → `5`

2. Onglet **Transferts** → Cocher 2 transferts
   - L'input hidden doit se remplir: `programme_days[0][transfer_ids] = "2,4"`
   - Vérifier dans la console: `window.dayItemsManager.getTransfers('0')` → `[2, 4]`

3. Onglet **Vols** → Cocher 1 vol
   - L'input hidden doit se remplir: `programme_days[0][flights] = "1"`
   - Vérifier dans la console: `window.dayItemsManager.getFlights('0')` → `[1]`

---

### Étape 4: Vérifier les inputs hidden

**Dans la console**:
```javascript
// Trouver un jour spécifique
const dayCard = document.querySelector('.programme-day-card[data-day-index="0"]');

// Lire les inputs hidden
console.log(dayCard.querySelector('input[name$="[hotel_id]"]').value);
console.log(dayCard.querySelector('input[name$="[transfer_ids]"]').value);
console.log(dayCard.querySelector('input[name$="[flights]"]').value);
```

**Résultats attendus**:
```
hotel_id: "5"
transfer_ids: "2,4"
flights: "1"
```

---

### Étape 5: Enregistrer & Vérifier

1. Fermer le drawer
2. Cliquer sur **"Enregistrer toutes les modifications"**
3. Aller à l'onglet **Network** (F12)
4. Chercher la requête **POST**
5. Regarder le **Payload** (body)

**Exemple de payload attendu**:
```
programme_days[0][mode]=program
programme_days[0][day_title]=Jour 1
programme_days[0][flights]=1
programme_days[0][hotel_id]=5
programme_days[0][transfer_ids]=2,4
programme_days[0][activities][0][...]=...
```

✅ Si vous voyez les 3 nouveaux champs (`flights`, `hotel_id`, `transfer_ids`), **le frontend fonctionne !**

---

## 🔧 Étapes suivantes: Backend

Pour que les données **persistes en base**, vous devez:

### 1. Créer les migrations

**Fichier**: `database/migrations/2024_02_18_add_hotel_transfers_flights_to_program_days.php`

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFlightsHotelTransfersToTravelProgramDays extends Migration
{
    public function up()
    {
        Schema::table('travel_program_days', function (Blueprint $table) {
            if (!Schema::hasColumn('travel_program_days', 'hotel_id')) {
                $table->unsignedBigInteger('hotel_id')->nullable()->after('id');
            }
        });

        if (!Schema::hasTable('travel_program_day_flights')) {
            Schema::create('travel_program_day_flights', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('program_day_id');
                $table->unsignedBigInteger('flight_id');
                $table->foreign('program_day_id')->references('id')->on('travel_program_days')->onDelete('cascade');
                $table->unique(['program_day_id', 'flight_id']);
                $table->index('flight_id');
            });
        }

        if (!Schema::hasTable('travel_program_day_transfers')) {
            Schema::create('travel_program_day_transfers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('program_day_id');
                $table->unsignedBigInteger('transfer_id');
                $table->foreign('program_day_id')->references('id')->on('travel_program_days')->onDelete('cascade');
                $table->unique(['program_day_id', 'transfer_id']);
                $table->index('transfer_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('travel_program_day_transfers');
        Schema::dropIfExists('travel_program_day_flights');
        
        Schema::table('travel_program_days', function (Blueprint $table) {
            if (Schema::hasColumn('travel_program_days', 'hotel_id')) {
                $table->dropColumn('hotel_id');
            }
        });
    }
}
```

**Exécuter**:
```bash
php artisan migrate
```

---

### 2. Mettre à jour les modèles

**Fichier**: `app/Models/TravelProgramDay.php`

```php
public function hotel()
{
    return $this->belongsTo(\App\Models\TourHotel::class, 'hotel_id');
}

public function flights()
{
    return $this->belongsToMany(
        \App\Models\VoyageFlight::class,
        'travel_program_day_flights',
        'program_day_id',
        'flight_id'
    );
}

public function transfers()
{
    return $this->belongsToMany(
        \App\Models\TourTransfer::class,
        'travel_program_day_transfers',
        'program_day_id',
        'transfer_id'
    );
}
```

---

### 3. Adapter le Controller

**Fichier**: `app/Http/Controllers/Admin/VoyageController.php` (méthode `update`)

```php
// DANS LA MÉTHODE update():

$this->syncProgrammeDaysWithItems($voyage, $request);

// PUIS AJOUTER CETTE MÉTHODE:

protected function syncProgrammeDaysWithItems($voyage, Request $request)
{
    $programmeDays = $request->input('programme_days', []);

    foreach ($programmeDays as $dayIndex => $dayData) {
        $day = $voyage->programDays()
                      ->orderBy('day_number')
                      ->skip($dayIndex)
                      ->first();
        if (!$day) continue;

        // Synchroniser hôtel
        $hotelId = intval($dayData['hotel_id'] ?? 0) ?: null;
        $day->update(['hotel_id' => $hotelId]);

        // Synchroniser vols
        $flightIds = array_filter(
            array_map('intval', explode(',', $dayData['flights'] ?? '')),
            fn($x) => $x > 0
        );
        $day->flights()->sync($flightIds);

        // Synchroniser transferts
        $transferIds = array_filter(
            array_map('intval', explode(',', $dayData['transfer_ids'] ?? '')),
            fn($x) => $x > 0
        );
        $day->transfers()->sync($transferIds);
    }
}
```

---

### 4. Tester la persistance

```bash
# Vérifier que les migrations sont exécutées
php artisan migrate:status

# Vérifier les relations
php tinker
>>> $day = App\Models\TravelProgramDay::find(1);
>>> $day->hotel;
>>> $day->flights;
>>> $day->transfers;
```

---

## 📊 Vérification rapide (Checklist)

| ✅ Point | Vérification | Où? |
|---------|-----------|-----|
| Frontend init | `window.dayItemsManager` existe | Console |
| Inputs hidden | 3 inputs par jour | Code source |
| Drawer | 4 onglets ouverts | Page |
| Sélection hôtel | Input `hotel_id` rempli | Console |
| Sélection transferts | Input `transfer_ids` rempli | Console |
| Sélection vols | Input `flights` rempli | Console |
| Form POST | Données envoyées | Network tab |
| Migrations | Exécutées sans erreur | `php artisan migrate:status` |
| Relations | Eloquent fonctionne | `php tinker` |
| Persistance | Données en DB | MySQL/DB |

---

## 🐛 Troubleshooting rapide

| Problème | Solution |
|----------|----------|
| `window.dayItemsManager` undefined | Recharger la page (Ctrl+F5) |
| Inputs hidden vides | Vérifier les listeners de `change` dans la console |
| Drawer ne s'ouvre pas | Vérifier que Bootstrap est chargé |
| Migration échoue | Vérifier que les tables existent [voir DAY_ITEMS_BACKEND_GUIDE.md] |
| Relations non trouvées | Vérifier imports + noms de tables dans `app/Models/` |

---

## 📚 Docs complètes

Pour plus de détails:
- 📖 **DAY_ITEMS_IMPLEMENTATION.md** → Architecture générale
- 🧪 **DAY_ITEMS_CHECKLIST.md** → 7 scénarios de test détaillés
- 🛠️ **DAY_ITEMS_BACKEND_GUIDE.md** → Guide complet backend
- 📝 **CHANGEMENTS.md** → Fichiers modifiés

---

## 🎉 C'est prêt !

Dès que le backend est implémenté, vous aurez:
- ✅ Hôtels par jour (0..1)
- ✅ Transferts par jour (0..n)
- ✅ Vols par jour (0..n)
- ✅ Persistance en base de données
- ✅ Pré-remplissage à la réouverture
- ✅ Zéro breaking changes

