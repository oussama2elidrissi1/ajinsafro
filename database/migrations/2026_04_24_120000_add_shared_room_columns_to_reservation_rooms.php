<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            if (! $schema->hasColumn('reservation_rooms', 'source_room_id')) {
                $table->unsignedBigInteger('source_room_id')->nullable()->after('departure_hotel_id');
                $table->index('source_room_id');
            }
            if (! $schema->hasColumn('reservation_rooms', 'source_room_type')) {
                $table->string('source_room_type', 40)->nullable()->after('source_room_id');
                $table->index('source_room_type');
            }
            if (! $schema->hasColumn('reservation_rooms', 'room_mode')) {
                $table->string('room_mode', 30)->nullable()->after('source_room_type');
                $table->index('room_mode');
            }
            if (! $schema->hasColumn('reservation_rooms', 'shared_room_status')) {
                $table->string('shared_room_status', 30)->nullable()->after('room_mode');
                $table->index('shared_room_status');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');
        if (! $schema->hasTable('reservation_rooms')) {
            return;
        }

        $schema->table('reservation_rooms', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('reservation_rooms', 'shared_room_status')) {
                $table->dropColumn('shared_room_status');
            }
            if ($schema->hasColumn('reservation_rooms', 'room_mode')) {
                $table->dropColumn('room_mode');
            }
            if ($schema->hasColumn('reservation_rooms', 'source_room_type')) {
                $table->dropColumn('source_room_type');
            }
            if ($schema->hasColumn('reservation_rooms', 'source_room_id')) {
                $table->dropColumn('source_room_id');
            }
        });
    }
};
