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
            if (! $schema->hasColumn('reservation_rooms', 'paired_reservation_id')) {
                $table->unsignedBigInteger('paired_reservation_id')->nullable()->after('shared_room_status');
                $table->index('paired_reservation_id');
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
            if ($schema->hasColumn('reservation_rooms', 'paired_reservation_id')) {
                $table->dropColumn('paired_reservation_id');
            }
        });
    }
};
