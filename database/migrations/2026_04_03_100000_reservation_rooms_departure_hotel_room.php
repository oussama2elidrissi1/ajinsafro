<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql');
        if (! $schema->hasTable('reservation_rooms')) {
            return;
        }

        $schema->table('reservation_rooms', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('reservation_rooms', 'departure_hotel_room_id')) {
                $table->unsignedBigInteger('departure_hotel_room_id')->nullable()->after('reservation_id');
                $table->index('departure_hotel_room_id');
            }
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql' && $schema->hasColumn('reservation_rooms', 'tour_hotel_id')) {
            try {
                DB::connection('mysql')->statement('ALTER TABLE reservation_rooms MODIFY tour_hotel_id BIGINT UNSIGNED NULL');
            } catch (\Throwable $e) {
                // ignore if already nullable or permissions
            }
        }
        if ($driver === 'mysql' && $schema->hasColumn('reservation_rooms', 'tour_hotel_room_id')) {
            try {
                DB::connection('mysql')->statement('ALTER TABLE reservation_rooms MODIFY tour_hotel_room_id BIGINT UNSIGNED NULL');
            } catch (\Throwable $e) {
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');
        if (! $schema->hasTable('reservation_rooms')) {
            return;
        }

        $schema->table('reservation_rooms', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('reservation_rooms', 'departure_hotel_room_id')) {
                $table->dropColumn('departure_hotel_room_id');
            }
        });
    }
};
