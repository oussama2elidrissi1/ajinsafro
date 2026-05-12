<?php
// Load Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Voyage;
use App\Models\Departure;
use App\Models\DepartureRoomAllocation;
use App\Services\Reservations\ReservationPricingService;

echo "========== TESTING ENDPOINT ==========\n\n";

// Check the data
$voyage = Voyage::find(47);
$departure = Departure::find(15);
$allocations = DepartureRoomAllocation::where('departure_id', 15)->get();

echo "VOYAGE ID 47:\n";
var_dump($voyage ? $voyage->only(['id', 'name', 'wp_post_id']) : null);

echo "\nDEPARTURE ID 15:\n";
var_dump($departure ? $departure->only(['id', 'voyage_id', 'start_date', 'wp_travel_date_id', 'available_capacity']) : null);

echo "\nDEPARTURE ROOM ALLOCATIONS (COUNT: " . $allocations->count() . "):\n";
foreach ($allocations as $alloc) {
    echo "- Hotel ID: {$alloc->hotel_id}, Room Type: {$alloc->room_type}, Qty: {$alloc->quantity}, Capacity: {$alloc->capacity_per_room}, Supp: {$alloc->supplement}\n";
}

echo "\n========== TESTING SERVICE ==========\n\n";

$service = new ReservationPricingService();

$payload = [
    'tour_id' => 47,
    'travel_date_id' => 252,
    'departure_id' => 15,
    'passengers' => [
        ['first_name' => 'Test', 'last_name' => 'User']
    ]
];

try {
    $result = $service->previewDepartureSelection($payload);
    echo "SUCCESS!\n\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>
