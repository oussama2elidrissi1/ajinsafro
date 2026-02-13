<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'wp';

    /**
     * Allow multiple hotels per tour.
     * Drop unique(tour_id), add sort_order.
     */
    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if (!$schema->hasTable('aj_tour_hotels')) {
            return;
        }

        try {
            $schema->table('aj_tour_hotels', function (Blueprint $table) {
                $table->dropUnique(['tour_id']);
            });
        } catch (\Throwable $e) {
            $prefix = DB::connection($this->connection)->getTablePrefix();
            $fullTable = $prefix . 'aj_tour_hotels';
            foreach (['aj_tour_hotels_tour_id_unique', 'tour_id_unique'] as $name) {
                try {
                    DB::connection($this->connection)->statement("ALTER TABLE `{$fullTable}` DROP INDEX `{$name}`");
                    break;
                } catch (\Throwable $e2) {
                    //
                }
            }
        }

        if (!$schema->hasColumn('aj_tour_hotels', 'sort_order')) {
            $schema->table('aj_tour_hotels', function (Blueprint $table) {
                $table->unsignedSmallInteger('sort_order')->default(0)->after('tour_id');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        if (!$schema->hasTable('aj_tour_hotels')) {
            return;
        }
        if ($schema->hasColumn('aj_tour_hotels', 'sort_order')) {
            $schema->table('aj_tour_hotels', fn (Blueprint $t) => $t->dropColumn('sort_order'));
        }
        try {
            $schema->table('aj_tour_hotels', fn (Blueprint $t) => $t->unique('tour_id'));
        } catch (\Throwable $e) {
            //
        }
    }
};
