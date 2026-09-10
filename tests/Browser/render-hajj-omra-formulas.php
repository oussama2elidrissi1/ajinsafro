<?php
// Standalone visual fixture. Uses only in-memory SQLite, never the configured application DB.
require dirname(__DIR__, 2).'/vendor/autoload.php';
putenv('APP_ENV=testing');
$factory = new class { use \Tests\CreatesApplication; };
$app = $factory->createApplication();
if (config('database.connections.mysql.driver') !== 'sqlite' || config('database.connections.mysql.database') !== ':memory:') {
    throw new RuntimeException('This fixture requires isolated in-memory SQLite.');
}
config(['app.url' => 'http://localhost', 'session.driver' => 'array']);
\Illuminate\Support\Facades\URL::forceRootUrl('http://localhost');
\Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
$destination = $argv[1] ?? throw new RuntimeException('Pass an output directory.');
if (!is_dir($destination)) mkdir($destination, 0777, true);
$offer = app(\App\Services\HajjOmra\HajjOmraPackageService::class)->save(new \App\Models\HajjOmraPackage, [
    'title' => 'Omra Ramadan — 14 jours', 'title_ar' => 'عمرة رمضان — 14 يوماً', 'slug' => 'omra-ramadan',
    'type' => 'omra', 'status' => 'published', 'currency' => 'DH', 'duration_days' => 14, 'duration_nights' => 13,
    'departure_city' => 'Casablanca', 'available_places' => 30,
    'description' => 'Un séjour accompagné entre Médine et Makkah.', 'description_ar' => 'رحلة مؤطرة بين المدينة المنورة ومكة المكرمة.',
    'hotels' => [
        ['client_key' => 'medina', 'city' => 'madinah', 'name' => 'Al Mokhtara', 'name_ar' => 'مجموعة المختارة', 'nights' => 4, 'haram_distance' => '500 m', 'stars' => 4],
        ['client_key' => 'makkah', 'city' => 'makkah', 'name' => 'Palestine Makkah', 'name_ar' => 'فندق فلسطين', 'nights' => 9, 'haram_distance' => '800 m'],
        ['client_key' => 'premium', 'city' => 'makkah', 'name' => 'Makkah Premium', 'name_ar' => 'فندق مكة الممتاز', 'nights' => 3],
    ],
    'room_prices' => [
        ['client_key' => 'double', 'room_type' => 'double', 'price' => 16900, 'stock' => 10, 'is_active' => true],
        ['client_key' => 'triple', 'room_type' => 'triple', 'price' => 15600, 'stock' => 10, 'is_active' => true],
        ['client_key' => 'quad', 'room_type' => 'quadruple', 'price' => 14900, 'stock' => 10, 'is_active' => true],
        ['client_key' => 'premium', 'room_type' => 'double', 'price' => 21900, 'stock' => 10, 'is_active' => true],
    ],
    'departures' => [
        ['client_key' => 'first', 'departure_date' => '2027-02-01', 'return_date' => '2027-02-14', 'status' => 'published', 'available_places' => 30],
        ['client_key' => 'second', 'departure_date' => '2027-03-01', 'return_date' => '2027-03-14', 'status' => 'published', 'available_places' => 30],
    ],
    'program_days' => [['client_key' => 'day5', 'day_number' => 5, 'title' => 'Arrivée à Makkah', 'title_ar' => 'الوصول إلى مكة']],
    'formulas' => [
        ['name_fr' => 'Programme touristique', 'name_ar' => 'البرنامج السياحي', 'is_active' => true, 'departure_id' => 'new:first',
            'tariff_ids' => ['new:double', 'new:triple', 'new:quad'], 'hotels' => [['hotel_id' => 'new:medina'], ['hotel_id' => 'new:makkah', 'program_day_id' => 'new:day5']]],
        ['name_fr' => 'Programme Premium', 'name_ar' => 'البرنامج الممتاز', 'is_active' => true, 'departure_id' => 'new:second',
            'tariff_ids' => ['new:premium'], 'hotels' => [['hotel_id' => 'new:makkah', 'nights_override' => 10], ['hotel_id' => 'new:premium']]],
    ],
]);
\Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag);
$view = app(\App\Http\Controllers\Admin\HajjOmraPackageController::class)->edit($offer);
$data = $view->getData(); $data['activeTab'] = 'tarifs';
$base = 'file:///'.str_replace('\\', '/', base_path()).'/';
$admin = view('admin.hajj-omra._form', $data)->render();
$admin = str_replace('http://localhost/', $base.'public/', $admin);
file_put_contents($destination.'/admin.html', '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="'.$base.'public/build/css/bootstrap.min.css"><style>body{padding:20px;background:#f7f8fa}.ho-editor[dir=rtl]{text-align:right}</style></head><body>'.$admin.'</body></html>');
$api = app(\App\Http\Controllers\Api\PublicHajjOmraPackageController::class)->show($offer->slug)->getData(true)['data'];
file_put_contents($destination.'/api.json', json_encode($api, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
require dirname(__DIR__).'/Fixtures/hajj-omra-detail.php';
$css = '';
foreach (['home', 'hajj-omra', 'hajj-omra-detail', 'hajj-omra-formulas'] as $file) $css .= '<link rel="stylesheet" href="'.$base.'wp-plugin/ajinsafro-traveler-home/assets/css/'.$file.'.css">';
$image = $base.'wp-plugin/ajinsafro-traveler-home/assets/images/fallback-hajj-omra.svg';
$api['gallery'] = [$image];
foreach (['fr', 'ar'] as $locale) {
    $_GET['lang'] = $locale;
    $html = HajjOmraDetailFixture::render($api);
    file_put_contents($destination.'/detail-'.$locale.'.html', '<!doctype html><html lang="'.$locale.'"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'.$css.'<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Noto+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet"><style>body{margin:0}</style></head><body class="page-hajj-omra-detail page-hajj-omra-ajinsafro">'.$html.'<script src="'.$base.'wp-plugin/ajinsafro-traveler-home/assets/js/hajj-omra-detail.js"></script></body></html>');
}
echo 'Rendered isolated admin + FR/AR fixtures in '.$destination.PHP_EOL;
