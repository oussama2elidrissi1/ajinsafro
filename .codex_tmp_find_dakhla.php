<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = App\Models\Voyage::query()
    ->where('name', 'like', '%Dakhla%')
    ->orWhere('slug', 'like', '%dakhla%')
    ->orderBy('id')
    ->get(['id','name','slug','wp_post_id']);
foreach ($rows as $row) {
    echo json_encode($row->toArray(), JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
