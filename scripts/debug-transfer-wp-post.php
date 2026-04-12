<?php

/**
 * Proof dump for WordPress transfer (st_cars) by wp_posts.ID.
 * Admin route: /admin/wordpress/transfers/{wp_post_id}/edit
 *
 * Usage: php scripts/debug-transfer-wp-post.php 14353
 */

use App\Models\CatalogTransfer;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$wpPostId = isset($argv[1]) ? (int) $argv[1] : 0;
if ($wpPostId < 1) {
    echo "Usage: php scripts/debug-transfer-wp-post.php <wp_post_id>\n";
    exit(1);
}

$prefix = config('database.connections.wp.prefix');
$dbName = config('database.connections.wp.database');

echo "=== CONFIG ===\n";
echo "WP database: {$dbName}\n";
echo "WP prefix: {$prefix}\n";
echo "Target wp_posts.ID: {$wpPostId}\n";
echo "Post type expected in Laravel: st_cars\n\n";

echo "=== LARAVEL catalog_transfers (wp_post_id = {$wpPostId}) ===\n";
$cat = CatalogTransfer::query()->where('wp_post_id', $wpPostId)->first();
echo $cat ? json_encode($cat->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : 'NOT FOUND (no catalog_transfers row)';
echo "\n\n";

echo "=== {$prefix}posts WHERE ID = {$wpPostId} ===\n";
$p = DB::connection('wp')->table('posts')->where('ID', $wpPostId)->first();
echo $p ? json_encode((array) $p, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : 'NOT FOUND';
echo "\n\n";

echo "=== {$prefix}st_cars WHERE post_id = {$wpPostId} ===\n";
$c = DB::connection('wp')->table('st_cars')->where('post_id', $wpPostId)->first();
echo $c ? json_encode((array) $c, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : 'NOT FOUND';
echo "\n\n";

echo "=== {$prefix}postmeta (meta_key matches title|name|car|transfer|vehicle|price|location) ===\n";
$metas = DB::connection('wp')->table('postmeta')
    ->where('post_id', $wpPostId)
    ->where(function ($q) {
        $q->where('meta_key', 'like', '%title%')
            ->orWhere('meta_key', 'like', '%name%')
            ->orWhere('meta_key', 'like', '%car%')
            ->orWhere('meta_key', 'like', '%transfer%')
            ->orWhere('meta_key', 'like', '%vehicle%')
            ->orWhere('meta_key', 'like', '%price%')
            ->orWhere('meta_key', 'like', '%location%');
    })
    ->orderBy('meta_key')
    ->get();

foreach ($metas as $m) {
    echo $m->meta_key.' => '.substr((string) $m->meta_value, 0, 800)."\n";
}

echo "\n=== WordPress siteurl / home ===\n";
foreach (DB::connection('wp')->table('options')->whereIn('option_name', ['siteurl', 'home'])->get() as $o) {
    echo $o->option_name.' => '.$o->option_value."\n";
}

echo "\n=== DONE ===\n";
