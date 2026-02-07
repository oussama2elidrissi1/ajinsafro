<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Alter aj_tour_flights to user spec: flight_type (outbound/inbound), from_city, to_city,
     * depart_time, arrive_time, baggage_cabin_kg, baggage_checkin_kg, notes. Unique (tour_id, flight_type).
     */
    public function up(): void
    {
        $connection = 'wp';
        $schema = Schema::connection($connection);

        if (!$schema->hasTable('aj_tour_flights')) {
            $schema->create('aj_tour_flights', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tour_id')->comment('wp_posts.ID');
                $table->enum('flight_type', ['outbound', 'inbound']);
                $table->unsignedBigInteger('airline_id')->nullable();
                $table->string('cabin_class', 30)->default('economy');
                $table->string('from_city')->nullable();
                $table->string('to_city')->nullable();
                $table->date('depart_date')->nullable();
                $table->time('depart_time')->nullable();
                $table->date('arrive_date')->nullable();
                $table->time('arrive_time')->nullable();
                $table->unsignedSmallInteger('baggage_cabin_kg')->nullable();
                $table->unsignedSmallInteger('baggage_checkin_kg')->nullable();
                $table->boolean('is_tentative')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['tour_id', 'flight_type']);
                $table->index('tour_id');
            });
            return;
        }

        $prefix = DB::connection($connection)->getTablePrefix();
        $tableName = $prefix . 'aj_tour_flights';
        // Skip alter if table not reachable (e.g. missing on this connection)
        try {
            DB::connection($connection)->selectOne("SELECT 1 FROM {$tableName} LIMIT 1");
        } catch (\Throwable $e) {
            return;
        }

        if (!$schema->hasColumn('aj_tour_flights', 'flight_type')) {
            $schema->table('aj_tour_flights', function (Blueprint $table) {
                $table->enum('flight_type', ['outbound', 'inbound'])->nullable()->after('tour_id');
            });
            DB::connection($connection)->statement(
                "UPDATE {$tableName} SET flight_type = IF(segment_number = 1, 'outbound', 'inbound') WHERE flight_type IS NULL"
            );
            DB::connection($connection)->statement(
                "ALTER TABLE {$tableName} MODIFY flight_type ENUM('outbound','inbound') NOT NULL"
            );
        }

        if (!$schema->hasColumn('aj_tour_flights', 'from_city')) {
            $schema->table('aj_tour_flights', function (Blueprint $table) {
                $table->string('from_city')->nullable()->after('cabin_class');
            });
            DB::connection($connection)->statement(
                "UPDATE {$tableName} SET from_city = depart_city WHERE from_city IS NULL AND depart_city IS NOT NULL"
            );
        }
        if (!$schema->hasColumn('aj_tour_flights', 'to_city')) {
            $schema->table('aj_tour_flights', function (Blueprint $table) {
                $table->string('to_city')->nullable()->after('from_city');
            });
            DB::connection($connection)->statement(
                "UPDATE {$tableName} SET to_city = arrive_city WHERE to_city IS NULL AND arrive_city IS NOT NULL"
            );
        }
        if (!$schema->hasColumn('aj_tour_flights', 'depart_time')) {
            $schema->table('aj_tour_flights', function (Blueprint $table) {
                $table->time('depart_time')->nullable()->after('depart_date');
                $table->time('arrive_time')->nullable()->after('arrive_date');
            });
        }
        if (!$schema->hasColumn('aj_tour_flights', 'baggage_cabin_kg')) {
            $schema->table('aj_tour_flights', function (Blueprint $table) {
                $table->unsignedSmallInteger('baggage_cabin_kg')->nullable()->after('arrive_time');
                $table->unsignedSmallInteger('baggage_checkin_kg')->nullable()->after('baggage_cabin_kg');
            });
        }
        if (!$schema->hasColumn('aj_tour_flights', 'notes')) {
            $schema->table('aj_tour_flights', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('is_tentative');
            });
        }
        if ($schema->hasColumn('aj_tour_flights', 'is_tentative')) {
            DB::connection($connection)->statement(
                "ALTER TABLE {$tableName} MODIFY is_tentative TINYINT(1) NOT NULL DEFAULT 1"
            );
        }

        if ($schema->hasColumn('aj_tour_flights', 'segment_number')) {
            try {
                $schema->table('aj_tour_flights', function (Blueprint $table) {
                    $table->dropUnique(['tour_id', 'segment_number']);
                });
            } catch (\Throwable $e) {
                // Index name may include prefix
            }
        }

        $colsToDrop = ['segment_number', 'flight_number', 'depart_city', 'arrive_city', 'depart_airport', 'arrive_airport', 'cabin_baggage', 'checkin_baggage', 'is_default', 'sort_order'];
        foreach ($colsToDrop as $col) {
            if ($schema->hasColumn('aj_tour_flights', $col)) {
                $schema->table('aj_tour_flights', function (Blueprint $table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }

        try {
            $schema->table('aj_tour_flights', function (Blueprint $table) {
                $table->unique(['tour_id', 'flight_type']);
            });
        } catch (\Throwable $e) {
            // Already exists
        }
    }

    public function down(): void
    {
        Schema::connection('wp')->dropIfExists('aj_tour_flights');
    }
};
