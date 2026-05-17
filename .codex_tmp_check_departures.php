<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$voyage = App\Models\Voyage::find(49);
if (! $voyage) { echo "voyage_not_found\n"; exit(0); }
$departures = App\Models\Departure::query()->where('voyage_id', 49)->orderBy('start_date')->get(['id','voyage_id','wp_travel_date_id','start_date','end_date','available_capacity']);
foreach ($departures as $d) {
    echo json_encode([
        'id' => $d->id,
        'travel_date_id' => $d->wp_travel_date_id,
        'start_date' => optional($d->start_date)->format('Y-m-d'),
        'end_date' => optional($d->end_date)->format('Y-m-d'),
        'available_capacity' => $d->available_capacity,
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
