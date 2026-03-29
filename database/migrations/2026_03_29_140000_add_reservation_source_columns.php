<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reservations')) {
            return;
        }
        Schema::table('reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('reservations', 'wp_tour_post_id')) {
                $table->unsignedBigInteger('wp_tour_post_id')->nullable()->after('tour_id');
            }
            if (! Schema::hasColumn('reservations', 'catalog_source_code')) {
                $table->string('catalog_source_code', 80)->nullable()->after('wp_tour_post_id');
            }
            if (! Schema::hasColumn('reservations', 'voyage_flight_id')) {
                $table->unsignedBigInteger('voyage_flight_id')->nullable()->after('catalog_source_code');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('reservations')) {
            return;
        }
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'voyage_flight_id')) {
                $table->dropColumn('voyage_flight_id');
            }
            if (Schema::hasColumn('reservations', 'catalog_source_code')) {
                $table->dropColumn('catalog_source_code');
            }
            if (Schema::hasColumn('reservations', 'wp_tour_post_id')) {
                $table->dropColumn('wp_tour_post_id');
            }
        });
    }
};
