<?php

/**
 * One-off debug: Greenbar activity — Laravel catalog + WordPress tables.
 * Run: php scripts/debug-greenbar-activity.php
 */

use App\Models\CatalogActivity;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$needle = 'Greenbar';

$prefix = config('database.connections.wp.prefix');
$dbName = config('database.connections.wp.database');

echo "=== CONFIG ===\n";
echo "WP database: {$dbName}\n";
echo "WP table prefix: {$prefix}\n";
echo "posts table: {$prefix}posts\n";
echo "postmeta table: {$prefix}postmeta\n";
echo "st_activity table: {$prefix}st_activity\n\n";

echo "=== LARAVEL catalog_activities (match title LIKE %{$needle}%) ===\n";
$row = CatalogActivity::query()
    ->where('title', 'like', '%'.$needle.'%')
    ->first();

if (! $row) {
    echo "NOT FOUND in catalog_activities.\n";
    exit(1);
}

echo json_encode($row->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n\n";

$wpId = (int) $row->wp_post_id;
echo "=== Laravel id={$row->id} wp_post_id={$wpId} ===\n\n";

if ($wpId < 1) {
    echo "wp_post_id is missing; cannot query WordPress.\n";
    exit(1);
}

echo "=== {$prefix}posts row ID={$wpId} ===\n";
$p = DB::connection('wp')->table('posts')->where('ID', $wpId)->first();
echo $p ? json_encode((array) $p, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : 'NOT FOUND';
echo "\n\n";

echo "=== {$prefix}st_activity row post_id={$wpId} ===\n";
$s = DB::connection('wp')->table('st_activity')->where('post_id', $wpId)->first();
echo $s ? json_encode((array) $s, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : 'NOT FOUND';
echo "\n\n";

echo "=== {$prefix}postmeta (keys containing title|name|activity|price|location|duration) ===\n";
$metas = DB::connection('wp')->table('postmeta')
    ->where('post_id', $wpId)
    ->where(function ($q) {
        $q->where('meta_key', 'like', '%title%')
            ->orWhere('meta_key', 'like', '%name%')
            ->orWhere('meta_key', 'like', '%activity%')
            ->orWhere('meta_key', 'like', '%price%')
            ->orWhere('meta_key', 'like', '%location%')
            ->orWhere('meta_key', 'like', '%duration%');
    })
    ->orderBy('meta_key')
    ->get();

foreach ($metas as $m) {
    echo $m->meta_key.' => '.substr((string) $m->meta_value, 0, 500)."\n";
}

echo "\n=== WordPress options (siteurl / home) ===\n";
$opts = DB::connection('wp')->table('options')
    ->whereIn('option_name', ['siteurl', 'home'])
    ->get();
foreach ($opts as $o) {
    echo $o->option_name.' => '.$o->option_value."\n";
}

echo "\n=== DONE ===\n";
