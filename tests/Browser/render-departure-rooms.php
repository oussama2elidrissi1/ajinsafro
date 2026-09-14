<?php
// Isolated Blade fixture for the actual reservation step, modal and room loader.
require dirname(__DIR__, 2).'/vendor/autoload.php';
putenv('APP_ENV=testing');
putenv('CACHE_DRIVER=array');
$factory = new class { use \Tests\CreatesApplication; };
$app = $factory->createApplication();
if (config('database.connections.mysql.driver') !== 'sqlite' || config('database.connections.mysql.database') !== ':memory:') {
    throw new RuntimeException('In-memory SQLite is required.');
}
config(['logging.default' => 'null', 'cache.default' => 'array', 'app.url' => 'http://localhost', 'app.debug' => false, 'session.driver' => 'array']);
\Illuminate\Support\Facades\URL::forceRootUrl('http://localhost');
\Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
\Illuminate\Support\Facades\Gate::define('circuits.voyages.view', fn () => true);
auth()->setUser(new \App\Models\User(['name' => 'Test']));
\Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag);
$departure = new \App\Models\Departure(['id' => 1, 'voyage_id' => 1, 'start_date' => '2026-10-04', 'end_date' => '2026-10-15']);
$data = ['selectedDeparture' => $departure, 'selectedDepartureId' => 1, 'selectedUnitPrice' => 17950, 'preselectedTour' => new \App\Models\Voyage(['name' => 'Évasion en Égypte'])];
$step = view('admin.reservations.create.partials.step-fast-2', $data)->render();
$summary = view('admin.reservations.create.partials.summary-fast', $data)->render();
$modal = view('admin.reservations.create.partials.departure-rooms-modal')->render();
$base = 'file:///'.str_replace('\\', '/', base_path()).'/';
$head = '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
foreach (['build/css/bootstrap.min.css', 'css/reservation-create.css', 'css/departure-rooms-modal.css'] as $css) $head .= '<link rel="stylesheet" href="'.$base.'public/'.$css.'">';
$head .= '<style>body{padding:32px;background:#eaf1f8}.reservation-create{max-width:1360px;margin:auto}</style><script src="'.$base.'tests/Browser/departure-rooms-fixture.js"></script></head><body>';
$form = <<<'HTML'
<div class="reservation-create reservation-create--fast" data-fast-create="1"><form id="reservation-create-form">
<input name="_token" value="test-csrf" type="hidden"><input id="tour_id_hidden" value="1" type="hidden">
<input name="base_price" id="reservation-base-price" value="17950.00" type="hidden">
<input id="reservation-room-allocations-json" type="hidden"><input id="reservation-travelers-json" type="hidden"><input id="reservation-create-extras-json" type="hidden">
<input id="reservation-room-supplement-total-input" type="hidden"><input id="reservation-total-amount-input" type="hidden"><input id="reservation-extras-total-input" type="hidden">
<div class="reservation-create__panel" data-create-step="1" style="display:none"><input id="client_first_name" value="Omar"><input id="client_last_name" value="Test"><input id="client_gender" value="male"><input id="client_phone" value="0600000000"><input id="client_traveler_type" value="adult">
<div id="companions-container"><div class="companion-row" data-companion-id="companion_1"><input name="passengers[companion_1][first_name]" value="Sara"><input name="passengers[companion_1][last_name]" value="Test"><select name="passengers[companion_1][gender]"><option value="female">Femme</option></select><select name="passengers[companion_1][type]"><option value="adult">Adulte</option></select></div></div>
<button type="button" data-create-next>Continuer</button></div>
<div class="reservation-create__content-grid"><main class="reservation-create__main">
HTML;
$html = $head.$form.$step.'</main><aside class="reservation-create__summary">'.$summary.'</aside></div></form>'.$modal.'</div>';
$html .= '<script type="application/json" id="reservation-create-extras-map">{"1":[{"id":1,"name":"Visa Égypte","price_adult":750,"price_child":750}]}</script>';
$html .= '<script src="'.$base.'public/js/reservation-create.js"></script><script src="'.$base.'public/js/departure-rooms-modal.js"></script></body></html>';
$destination = $argv[1] ?? throw new RuntimeException('Pass a fixture directory.');
if (! is_dir($destination)) mkdir($destination, 0777, true);
file_put_contents($destination.'/rooms.html', $html);
echo 'Rendered '.$destination.'/rooms.html'.PHP_EOL;
