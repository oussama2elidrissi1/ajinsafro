<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'wp';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('aj_tour_hotels')) {
            if (!$schema->hasColumn('aj_tour_hotels', 'check_in_day')) {
                $schema->table('aj_tour_hotels', function (Blueprint $table) {
                    $table->unsignedSmallInteger('check_in_day')->nullable()->after('day_number');
                });
            }
            if (!$schema->hasColumn('aj_tour_hotels', 'check_out_day')) {
                $schema->table('aj_tour_hotels', function (Blueprint $table) {
                    $table->unsignedSmallInteger('check_out_day')->nullable()->after('check_in_day');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('aj_tour_hotels')) {
            if ($schema->hasColumn('aj_tour_hotels', 'check_out_day')) {
                $schema->table('aj_tour_hotels', fn (Blueprint $t) => $t->dropColumn('check_out_day'));
            }
            if ($schema->hasColumn('aj_tour_hotels', 'check_in_day')) {
                $schema->table('aj_tour_hotels', fn (Blueprint $t) => $t->dropColumn('check_in_day'));
            }
        }
    }
};
