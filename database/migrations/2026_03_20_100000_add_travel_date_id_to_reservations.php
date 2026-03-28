<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql');
        if ($schema->hasTable('reservations') && !$schema->hasColumn('reservations', 'travel_date_id')) {
            $schema->table('reservations', function (Blueprint $table) {
                $table->unsignedBigInteger('travel_date_id')->nullable()->after('tour_id')->comment('Date de départ choisie (aj_travel_dates.id, connexion wp)');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('mysql');
        if ($schema->hasTable('reservations') && $schema->hasColumn('reservations', 'travel_date_id')) {
            $schema->table('reservations', function (Blueprint $table) {
                $table->dropColumn('travel_date_id');
            });
        }
    }
};
