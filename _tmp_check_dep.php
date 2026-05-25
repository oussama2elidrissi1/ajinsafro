<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dep = App\Models\Departure::find(69);
if (!$dep) { echo "departure not found\n"; exit(0);} 
$voy = App\Models\Voyage::find($dep->voyage_id);
$voyByWp = App\Models\Voyage::where('wp_post_id', 59)->first();

echo "departure: id=".$dep->id." voyage_id=".$dep->voyage_id." wp_travel_date_id=".($dep->wp_travel_date_id ?? 'null')."\n";
if ($voy) {
  echo "voyage(by id): id=".$voy->id." wp_post_id=".($voy->wp_post_id ?? 'null')." status=".($voy->status ?? 'null')." name=".($voy->name ?? 'null')."\n";
} else {
  echo "voyage(by id): not found\n";
}
if ($voyByWp) {
  echo "voyage(by wp_post_id=59): id=".$voyByWp->id." wp_post_id=".$voyByWp->wp_post_id." status=".$voyByWp->status." name=".($voyByWp->name ?? 'null')."\n";
} else {
  echo "voyage(by wp_post_id=59): not found\n";
}
