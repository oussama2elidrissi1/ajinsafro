<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$d = App\Models\Departure::find(50);
if ($d) {
    echo 'dep voyage_id=' . $d->voyage_id . ' wp_travel_date_id=' . $d->wp_travel_date_id;
} else {
    echo 'no dep';
}
echo PHP_EOL;

$v = App\Models\Voyage::find(55);
if ($v) {
    echo 'voyage55 wp_post_id=' . $v->wp_post_id . ' status=' . $v->status;
} else {
    echo 'no voyage55';
}
echo PHP_EOL;

$td = App\Models\TravelDate::find(280);
if ($td) {
    echo 'td travel_id=' . $td->travel_id . ' active=' . ($td->is_active ? '1' : '0');
} else {
    echo 'no td';
}
echo PHP_EOL;

