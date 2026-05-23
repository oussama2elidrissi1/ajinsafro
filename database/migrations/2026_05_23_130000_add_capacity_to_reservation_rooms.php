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
            if (! $schema->hasColumn('reservation_rooms', 'capacity')) {
                $table->unsignedSmallInteger('capacity')->nullable()->default(null)->after('shared_room_status');
                $table->index('capacity');
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
            if ($schema->hasColumn('reservation_rooms', 'capacity')) {
                $table->dropColumn('capacity');
            }
        });
    }
};
