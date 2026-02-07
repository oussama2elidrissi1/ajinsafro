<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Align voyage_flights with spec: direction (outbound/inbound), from_city, to_city,
     * departure_date, baggage_cabin_kg, baggage_checkin_kg, cabin. Unique(voyage_id, direction).
     * No price on flights.
     */
    public function up(): void
    {
        if (!Schema::hasTable('voyage_flights')) {
            Schema::create('voyage_flights', function (Blueprint $table) {
                $table->id();
                $table->foreignId('voyage_id')->constrained('voyages')->cascadeOnDelete();
                $table->enum('direction', ['outbound', 'inbound']);
                $table->foreignId('airline_id')->nullable()->constrained('airlines')->nullOnDelete();
                $table->enum('cabin', ['economy', 'business', 'first'])->default('economy');
                $table->string('flight_number')->nullable();
                $table->string('from_city')->nullable();
                $table->string('to_city')->nullable();
                $table->date('departure_date')->nullable();
                $table->unsignedSmallInteger('baggage_cabin_kg')->nullable();
                $table->unsignedSmallInteger('baggage_checkin_kg')->nullable();
                $table->boolean('is_tentative')->default(false);
                $table->timestamps();
                $table->unique(['voyage_id', 'direction']);
            });
            return;
        }

        $table = 'voyage_flights';

        if (!Schema::hasColumn($table, 'direction')) {
            Schema::table($table, function (Blueprint $t) {
                $t->enum('direction', ['outbound', 'inbound'])->default('outbound')->after('voyage_id');
            });
            if (Schema::hasColumn($table, 'sort_order')) {
                DB::table($table)->where('sort_order', 2)->update(['direction' => 'inbound']);
            }
        }

        if (!Schema::hasColumn($table, 'from_city')) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('from_city')->nullable()->after('flight_number');
                $t->string('to_city')->nullable()->after('from_city');
                $t->date('departure_date')->nullable()->after('to_city');
                $t->unsignedSmallInteger('baggage_cabin_kg')->nullable()->after('departure_date');
                $t->unsignedSmallInteger('baggage_checkin_kg')->nullable()->after('baggage_cabin_kg');
            });
            if (Schema::hasColumn($table, 'departure_airport')) {
                DB::statement("UPDATE {$table} SET from_city = departure_airport WHERE departure_airport IS NOT NULL");
            }
            if (Schema::hasColumn($table, 'arrival_airport')) {
                DB::statement("UPDATE {$table} SET to_city = arrival_airport WHERE arrival_airport IS NOT NULL");
            }
            if (Schema::hasColumn($table, 'departure_at')) {
                DB::statement("UPDATE {$table} SET departure_date = DATE(departure_at) WHERE departure_at IS NOT NULL");
            }
        }

        if (!Schema::hasColumn($table, 'cabin')) {
            Schema::table($table, function (Blueprint $t) {
                $t->enum('cabin', ['economy', 'business', 'first'])->default('economy')->after('airline_id');
            });
            if (Schema::hasColumn($table, 'cabin_class')) {
                DB::statement("UPDATE {$table} SET cabin = CASE WHEN cabin_class IN ('business','first') THEN cabin_class ELSE 'economy' END");
            }
        }

        Schema::table($table, function (Blueprint $t) use ($table) {
            if (Schema::hasColumn($table, 'voyage_id') && Schema::hasColumn($table, 'sort_order')) {
                try {
                    $t->dropUnique(['voyage_id', 'sort_order']);
                } catch (\Throwable $e) {}
            }
        });

        $drops = ['sort_order', 'cabin_class', 'departure_airport', 'arrival_airport', 'departure_at', 'arrival_at', 'baggage', 'cabin_baggage', 'checkin_baggage', 'price', 'currency', 'is_default'];
        foreach ($drops as $col) {
            if (Schema::hasColumn($table, $col)) {
                Schema::table($table, function (Blueprint $t) use ($col) {
                    $t->dropColumn($col);
                });
            }
        }

        Schema::table($table, function (Blueprint $t) {
            $t->unique(['voyage_id', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voyage_flights');
    }
};
