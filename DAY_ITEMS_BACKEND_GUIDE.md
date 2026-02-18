# Guide: Implémentation Backend

## Overview

Le backend doit recevoir et persister les liaisons **Vols/Hôtel/Transferts par jour** depuis le formulaire Frontend.

## Structure des données reçues

**POST /admin/circuits/voyages/{id}**

```
programme_days[0][flights] = "1,3"           # CSV or empty
programme_days[0][hotel_id] = "5"             # Single ID or empty
programme_days[0][transfer_ids] = "2,4"       # CSV or empty
```

Chaque jour (index X) reçoit 3 champs additionnels en plus des activités.

---

## Étape 1: Migrations

### A. Colonne `hotel_id` sur `travel_program_days`

Si non existante, créer:

```php
// database/migrations/2024_02_18_add_hotel_transfers_to_program_days.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFlightsHotelTransfersToTravelProgramDays extends Migration
{
    public function up()
    {
        Schema::table('travel_program_days', function (Blueprint $table) {
            // Colonne hôtel
            if (!Schema::hasColumn('travel_program_days', 'hotel_id')) {
                $table->unsignedBigInteger('hotel_id')->nullable()->after('id');
            }
        });
    }

    public function down()
    {
        Schema::table('travel_program_days', function (Blueprint $table) {
            if (Schema::hasColumn('travel_program_days', 'hotel_id')) {
                $table->dropColumn('hotel_id');
            }
        });
    }
}
```

**Exécuter:**
```bash
php artisan migrate
```

### B. Table pivot `travel_program_day_flights`

```php
// database/migrations/2024_02_18_create_travel_program_day_flights.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTravelProgramDayFlights extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('travel_program_day_flights')) {
            Schema::create('travel_program_day_flights', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('program_day_id');
                $table->unsignedBigInteger('flight_id');
                $table->timestamps();

                // Foreign keys
                $table->foreign('program_day_id')
                    ->references('id')
                    ->on('travel_program_days')
                    ->onDelete('cascade');

                // Unique constraint
                $table->unique(['program_day_id', 'flight_id']);
                $table->index('flight_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('travel_program_day_flights');
    }
}
```

### C. Table pivot `travel_program_day_transfers`

```php
// database/migrations/2024_02_18_create_travel_program_day_transfers.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTravelProgramDayTransfers extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('travel_program_day_transfers')) {
            Schema::create('travel_program_day_transfers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('program_day_id');
                $table->unsignedBigInteger('transfer_id');
                $table->timestamps();

                // Foreign keys (only program_day_id est en même DB)
                $table->foreign('program_day_id')
                    ->references('id')
                    ->on('travel_program_days')
                    ->onDelete('cascade');

                // Unique constraint
                $table->unique(['program_day_id', 'transfer_id']);
                $table->index('transfer_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('travel_program_day_transfers');
    }
}
```

---

## Étape 2: Modèles Eloquent

### A. Mettre à jour `TravelProgramDay`

```php
// app/Models/TravelProgramDay.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TravelProgramDay extends Model
{
    protected $table = 'travel_program_days';
    protected $guarded = [];

    // Relations existantes
    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class, 'voyage_id');
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(
            Activity::class,
            'travel_program_day_activities',
            'program_day_id',
            'activity_id'
        )->withPivot('is_included', 'is_mandatory', 'custom_title', 'custom_description', 'sort_order');
    }

    // NOUVELLES RELATIONS
    
    /**
     * Hôtel (0..1 par jour)
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(TourHotel::class, 'hotel_id');
    }

    /**
     * Vols (0..n par jour)
     */
    public function flights(): BelongsToMany
    {
        return $this->belongsToMany(
            VoyageFlight::class,
            'travel_program_day_flights',
            'program_day_id',
            'flight_id'
        );
    }

    /**
     * Transferts (0..n par jour)
     */
    public function transfers(): BelongsToMany
    {
        return $this->belongsToMany(
            TourTransfer::class,
            'travel_program_day_transfers',
            'program_day_id',
            'transfer_id'
        );
    }
}
```

### B. Vérifier/ajouter les modèles parents

```php
// app/Models/TourHotel.php
class TourHotel extends Model
{
    // ...
    public function programDays(): HasMany
    {
        return $this->hasMany(TravelProgramDay::class, 'hotel_id');
    }
}

// app/Models/VoyageFlight.php (ou Flight)
class VoyageFlight extends Model
{
    // ...
    public function programDays(): BelongsToMany
    {
        return $this->belongsToMany(
            TravelProgramDay::class,
            'travel_program_day_flights',
            'flight_id',
            'program_day_id'
        );
    }
}

// app/Models/TourTransfer.php
class TourTransfer extends Model
{
    // ...
    public function programDays(): BelongsToMany
    {
        return $this->belongsToMany(
            TravelProgramDay::class,
            'travel_program_day_transfers',
            'transfer_id',
            'program_day_id'
        );
    }
}
```

---

## Étape 3: Controller

### Adapter `VoyageController@update`

```php
// app/Http/Controllers/Admin/VoyageController.php

namespace App\Http\Controllers\Admin;

use App\Models\Voyage;
use App\Models\TravelProgramDay;
use Illuminate\Http\Request;

class VoyageController extends Controller
{
    public function update(Request $request, $id)
    {
        $voyage = Voyage::findOrFail($id);

        // ... code existant pour les autres onglets ...

        // NOUVELLE LOGIQUE: Synchroniser les jours du programme avec Vols/Hôtel/Transferts
        $this->syncProgrammeDaysWithItems($voyage, $request);

        return redirect()->route('admin.circuits.voyages.edit', $id)
                        ->with('success', 'Voyage modifié');
    }

    /**
     * Synchroniser les liaisons par jour: Vols/Hôtel/Transferts
     */
    protected function syncProgrammeDaysWithItems(Voyage $voyage, Request $request)
    {
        $programmeDays = $request->input('programme_days', []);

        foreach ($programmeDays as $dayIndex => $dayData) {
            // Obtenir le jour de la base (order by day_number, skip nth)
            $day = $voyage->programDays()
                          ->orderBy('day_number')
                          ->skip($dayIndex)
                          ->first();

            if (!$day) {
                continue; // Jour inexistant, passer
            }

            // 1. Synchroniser l'hôtel (0..1)
            $hotelId = intval($dayData['hotel_id'] ?? 0) ?: null;
            if ($hotelId && !$this->hotelExists($hotelId)) {
                $hotelId = null; // Invalide, ignorer
            }
            $day->update(['hotel_id' => $hotelId]);

            // 2. Synchroniser les vols (0..n)
            $flightStr = trim($dayData['flights'] ?? '');
            $flightIds = [];
            if (!empty($flightStr)) {
                $flightIds = array_filter(
                    array_map('intval', explode(',', $flightStr)),
                    function ($id) { return $id > 0; }
                );
                // Optionnel: valider que les vols existent
                $flightIds = $this->filterValidFlightIds($flightIds);
            }
            $day->flights()->sync($flightIds);

            // 3. Synchroniser les transferts (0..n)
            $transferStr = trim($dayData['transfer_ids'] ?? '');
            $transferIds = [];
            if (!empty($transferStr)) {
                $transferIds = array_filter(
                    array_map('intval', explode(',', $transferStr)),
                    function ($id) { return $id > 0; }
                );
                // Optionnel: valider que les transferts existent
                $transferIds = $this->filterValidTransferIds($transferIds);
            }
            $day->transfers()->sync($transferIds);
        }
    }

    /**
     * Vérifier qu'un hôtel existe
     */
    protected function hotelExists($hotelId)
    {
        // Adapter selon votre modèle (TourHotel, wp_posts, etc.)
        return \App\Models\TourHotel::exists()
            ? \App\Models\TourHotel::where('id', $hotelId)->exists()
            : false;
    }

    /**
     * Filtrer les IDs de vols valides
     */
    protected function filterValidFlightIds($ids)
    {
        if (empty($ids)) return [];
        // Adapter selon votre modèle de vols
        return \App\Models\VoyageFlight::whereIn('id', $ids)
                                        ->pluck('id')
                                        ->toArray();
    }

    /**
     * Filtrer les IDs de transferts valides
     */
    protected function filterValidTransferIds($ids)
    {
        if (empty($ids)) return [];
        // Adapter selon votre modèle de transferts
        return \App\Models\TourTransfer::whereIn('id', $ids)
                                       ->pluck('id')
                                       ->toArray();
    }
}
```

---

## Étape 4: Vue (edit.blade.php) - Pré-remplissage

Pour que la pré-remplissage fonctionne au chargement, passer les données depuis le controller:

```php
// Dans VoyageController@edit

public function edit($id)
{
    $voyage = Voyage::findOrFail($id);

    // Charger les jours avec leurs relations
    $programDays = $voyage->programDays()
                          ->with(['flights', 'transfers', 'hotel'])
                          ->orderBy('day_number')
                          ->get();

    // Construire la structure pour JavaScript
    $programDayFlightsTransfersHotels = [];
    foreach ($programDays as $day) {
        $programDayFlightsTransfersHotels[$day->id] = [
            'flights' => $day->flights->pluck('id')->values()->toArray(),
            'hotel_id' => $day->hotel_id,
            'transfer_ids' => $day->transfers->pluck('id')->values()->toArray(),
        ];
    }

    return view('admin.circuits.voyages.edit', [
        'voyage' => $voyage,
        'programDays' => $programDays,
        'programDayFlightsTransfersHotels' => $programDayFlightsTransfersHotels,
        // ... autres variables ...
    ]);
}
```

Puis dans la vue:
```blade
<script>
window.programDayFlightsTransfersHotels = @json($programDayFlightsTransfersHotels ?? []);
</script>
```

Et dans HotelsManager/TransfersManager/FlightsManager, utiliser cette donnée:
```javascript
// Dans HotelsManager
if (window.programDayFlightsTransfersHotels && detail.dayId) {
    const hotelId = window.programDayFlightsTransfersHotels[detail.dayId]?.hotel_id;
    if (hotelId) {
        hotelsSelect.value = hotelId;
        // ...
    }
}
```

---

## Étape 5: Tester

### A. Test unitaire

```php
// tests/Feature/VoyageControllerTest.php

public function test_update_voyage_with_day_hotels_transfers_flights()
{
    $voyage = Voyage::factory()->create();
    $day = $voyage->programDays()->first();
    $hotel = TourHotel::factory()->create();
    $transfer = TourTransfer::factory()->create();
    $flight = VoyageFlight::factory()->create();

    $response = $this->post(route('admin.circuits.voyages.update', $voyage->id), [
        'programme_days' => [
            0 => [
                'id' => $day->id,
                'mode' => 'program',
                'day_title' => 'Jour 1',
                'notes' => 'Test',
                'hotel_id' => $hotel->id,
                'transfer_ids' => $transfer->id,
                'flights' => $flight->id,
            ]
        ]
    ]);

    $response->assertRedirect();

    // Vérifier que la liaison existe
    $day->refresh();
    $this->assertEquals($hotel->id, $day->hotel_id);
    $this->assertContains($transfer->id, $day->transfers->pluck('id')->toArray());
    $this->assertContains($flight->id, $day->flights->pluck('id')->toArray());
}
```

### B. Test manuel

1. Créer un voyage avec 2 jours
2. Aller à edit, cliquer "Ajouter un élément" pour Jour 1
3. Ajouter 1 hôtel + 2 transferts + 1 vol
4. Enregistrer
5. Recharger la page et vérifier la pré-remplissage
6. Modifier (retirer l'hôtel)
7. Enregistrer
8. Vérifier en DB que la liaison est supprimée

---

## Checklist Backend

- [ ] Migrations créées et exécutées
- [ ] Colonnes/tables dans la base de données
- [ ] Modèles Eloquent mis à jour (relations)
- [ ] Controller adapté (syncProgrammeDaysWithItems)
- [ ] Vue mise à jour pour passer les données
- [ ] Pré-remplissage fonctionne
- [ ] Tests unitaires réussis
- [ ] Pas de erreurs SQL
- [ ] Liaison en cascade supprimées si jour deleted

---

## Notes / Points d'attention

1. **Multi-base de données**: Si hôtels/transferts sont dans une autre DB (ex: WordPress), adapter les migrations et relations
2. **Validation**: Ajouter une validation robuste des IDs (vérifier qu'ils existent)
3. **Permissions**: Assurez-vous que l'utilisateur peut modifier les voyages
4. **Backward compatibility**: Les voyages existants sans liaison resteront vides (graceful degradation)
5. **Logs**: Logger les changements pour l'audit

