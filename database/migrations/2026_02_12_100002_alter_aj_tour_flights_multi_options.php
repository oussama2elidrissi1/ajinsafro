<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'wp';

    /**
     * Allow multiple flights per tour: drop unique, add sort_order, day_number, is_optional.
     * Add flight_type 'segment' for internal flights.
     */
    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if (!$schema->hasTable('aj_tour_flights')) {
            return;
        }

        try {
            $schema->table('aj_tour_flights', function (Blueprint $table) {
                if (!$schema->hasColumn('aj_tour_flights', 'sort_order')) {
                    $table->unsignedSmallInteger('sort_order')->default(0)->after('flight_type');
                }
                if (!$schema->hasColumn('aj_tour_flights', 'day_number')) {
                    $table->unsignedTinyInteger('day_number')->nullable()->after('sort_order');
                }
                if (!$schema->hasColumn('aj_tour_flights', 'is_optional')) {
                    $table->boolean('is_optional')->default(false)->after('is_tentative');
                }
                if (!$schema->hasColumn('aj_tour_flights', 'laravel_option_id')) {
                    $table->unsignedBigInteger('laravel_option_id')->nullable()->after('tour_id')->comment('VoyageFlightOption.id');
                }
            });
        } catch (\Throwable $e) {
            // ignore if columns exist
        }

        try {
            $schema->table('aj_tour_flights', function (Blueprint $table) {
                $table->dropUnique(['tour_id', 'flight_type']);
            });
        } catch (\Throwable $e) {
            // index name may vary
        }

        $prefix = DB::connection($this->connection)->getTablePrefix();
        $tableName = $prefix . 'aj_tour_flights';
        try {
            DB::connection($this->connection)->statement(
                "ALTER TABLE {$tableName} MODIFY COLUMN flight_type ENUM('outbound','inbound','segment') NOT NULL DEFAULT 'outbound'"
            );
        } catch (\Throwable $e) {
            // already segment
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        if (!$schema->hasTable('aj_tour_flights')) {
            return;
        }
        foreach (['sort_order', 'day_number', 'is_optional', 'laravel_option_id'] as $col) {
            if ($schema->hasColumn('aj_tour_flights', $col)) {
                $schema->table('aj_tour_flights', fn (Blueprint $t) => $t->dropColumn($col));
            }
        }
        try {
            $schema->table('aj_tour_flights', fn (Blueprint $t) => $t->unique(['tour_id', 'flight_type']));
        } catch (\Throwable $e) {}
    }
};
